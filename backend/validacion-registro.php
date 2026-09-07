<?php
/**
 * DIGITAL TRANSPORT - VALIDACIÓN REGISTRO (REFACTORIZADA A PDO)
 * Soporta registro de: Pasajeros (Estándar, Estudiante, 3ra Edad), Choferes y Admins.
 */
header('Content-Type: application/json');
require_once 'includes/db.php';

function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// 1. RECEPCIÓN DE DATOS GENERALES
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$documento_identidad = trim($_POST['documento_identidad'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$tipo_usuario_id = (int)($_POST['tipo_usuario_id'] ?? 1); // Por defecto Pasajero
$linea_id = (int)($_POST['linea_id'] ?? 0); 

// Perfil de Pasajero (Nuevo)
$perfil_tipo = $_POST['perfil_tipo'] ?? 'estandar'; 

// Datos de Chofer/Admin
$cargo = trim($_POST['cargo'] ?? ''); 
$licencia = trim($_POST['licencia'] ?? ''); 
$vehiculo_placa = trim($_POST['vehiculo_placa'] ?? ''); 

// 2. VALIDACIÓN BÁSICA
if (empty($nombre_completo) || empty($documento_identidad) || empty($email) || empty($password)) {
    sendResponse(false, 'Por favor, complete todos los campos obligatorios.');
}

if (strlen($password) < 8) {
    sendResponse(false, 'La contraseña debe tener al menos 8 caracteres.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'El formato del email no es válido.');
}

// Control estricto de roles en registro público: No se permite auto-registrarse como Admin de Línea (4) ni SuperAdmin (5)
if ($tipo_usuario_id === 4 || $tipo_usuario_id === 5) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario_id'] ?? 0) != 5) {
        sendResponse(false, 'Acceso restringido: El registro de administradores debe realizarlo un SuperAdmin autenticado.');
    }
}

try {
    $pdo->beginTransaction();

    // 3. VERIFICAR EMAIL EXISTENTE
    $stmt = $pdo->prepare("SELECT usuario_id FROM USUARIO WHERE email = ? OR documento_identidad = ?");
    $stmt->execute([$email, $documento_identidad]);
    if ($stmt->fetch()) {
        throw new Exception('El email o documento de identidad ya se encuentra registrado.');
    }

    // 4. INSERTAR USUARIO
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql_usuario = "INSERT INTO USUARIO (nombre_completo, documento_identidad, email, password_hash, tipo_usuario_id, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute([$nombre_completo, $documento_identidad, $email, $password_hash, $tipo_usuario_id]);
    
    $usuario_id = $pdo->lastInsertId();
    $rol_texto = 'Usuario';

    // 5. LÓGICA POR ROL
    if ($tipo_usuario_id === 4) { // ADMIN_LINEA (Solo por SuperAdmin)
        $stmt = $pdo->prepare("INSERT INTO ADMIN_LINEA (usuario_id, linea_id, cargo) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $linea_id, $cargo]);
        $rol_texto = 'Administrador de Línea';

    } elseif ($tipo_usuario_id === 3) { // CHOFER
        $stmt = $pdo->prepare("SELECT placa FROM VEHICULO WHERE placa = ?");
        $stmt->execute([$vehiculo_placa]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO VEHICULO (placa, modelo, capacidad, linea_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vehiculo_placa, "Registrado por Chofer ($vehiculo_placa)", 40, $linea_id]);
        }
        $stmt = $pdo->prepare("INSERT INTO CHOFER (usuario_id, linea_id, licencia, vehiculo_placa, estado_servicio) VALUES (?, ?, ?, ?, 'PENDIENTE')");
        $stmt->execute([$usuario_id, $linea_id, $licencia, $vehiculo_placa]);
        $rol_texto = 'Chofer (En espera de validación por Admin de Línea)';
        
    } else {
        // LÓGICA DE PASAJERO CON PERFILES
        $rol_texto = 'Pasajero';
        
        if ($perfil_tipo === 'estudiante' || $perfil_tipo === 'tercera_edad') {
            $tipo_desc_id = ($perfil_tipo === 'estudiante') ? 2 : 3;
            
            if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Es obligatorio subir un comprobante válido para el perfil seleccionado.');
            }

            // Validar extensión de archivo
            $file_info = pathinfo($_FILES['comprobante']['name']);
            $ext = strtolower($file_info['extension'] ?? '');
            $allowed_exts = ['png', 'jpg', 'jpeg', 'pdf'];
            if (!in_array($ext, $allowed_exts)) {
                throw new Exception('Formato de archivo no permitido. Solo se aceptan PNG, JPG, JPEG o PDF.');
            }

            // Validar MIME real
            $tmp_path = $_FILES['comprobante']['tmp_name'];
            $mime_type = mime_content_type($tmp_path);
            $allowed_mimes = ['image/png', 'image/jpeg', 'application/pdf'];
            if (!in_array($mime_type, $allowed_mimes)) {
                throw new Exception('El contenido del archivo no coincide con un formato permitido.');
            }

            // Validar tamaño máximo (5MB)
            if ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
                throw new Exception('El archivo excede el tamaño máximo permitido de 5 MB.');
            }

            // Generar nombre aleatorio seguro en servidor
            $secure_filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $target_dir = __DIR__ . "/../uploads/documentos/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $target_path = $target_dir . $secure_filename;
            
            if (move_uploaded_file($tmp_path, $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO VALIDACION_ESPECIAL (usuario_id, tipo_desc_id, comprobante_url, estado_validacion, fecha_solicitud) VALUES (?, ?, ?, 'PENDIENTE', NOW())");
                $stmt->execute([$usuario_id, $tipo_desc_id, $secure_filename]);
                $rol_texto .= " (Solicitud de tarifa reducida enviada)";
            } else {
                throw new Exception('Error al guardar el comprobante en el servidor.');
            }
        }
    }

    $pdo->commit();
    sendResponse(true, "✅ Registro exitoso como $rol_texto. Ahora puedes iniciar sesión.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en validacion-registro: " . $e->getMessage());
    sendResponse(false, 'Error en el servidor al procesar el registro.'); 
}

?>