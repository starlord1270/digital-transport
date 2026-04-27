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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'El formato del email no es válido.');
}

try {
    $pdo->beginTransaction();

    // 3. VERIFICAR EMAIL EXISTENTE
    $stmt = $pdo->prepare("SELECT usuario_id FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('El email ya se encuentra registrado.');
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
    if ($tipo_usuario_id === 4) { // ADMIN_LINEA
        $stmt = $pdo->prepare("INSERT INTO ADMIN_LINEA (usuario_id, linea_id, cargo) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $linea_id, $cargo]);
        $rol_texto = 'Administrador de Línea';

    } elseif ($tipo_usuario_id === 3) { // CHOFER
        // Verificar vehículo
        $stmt = $pdo->prepare("SELECT placa FROM VEHICULO WHERE placa = ?");
        $stmt->execute([$vehiculo_placa]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO VEHICULO (placa, modelo, capacidad, linea_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vehiculo_placa, "Registrado por Chofer ($vehiculo_placa)", 40, $linea_id]);
        }
        $stmt = $pdo->prepare("INSERT INTO CHOFER (usuario_id, linea_id, licencia, vehiculo_placa, estado_servicio) VALUES (?, ?, ?, ?, 'PENDIENTE')");
        $stmt->execute([$usuario_id, $linea_id, $licencia, $vehiculo_placa]);
        $rol_texto = 'Chofer (En espera de validación)';
        
    } else {
        // LÓGICA DE PASAJERO CON PERFILES
        $rol_texto = 'Pasajero';
        
        if ($perfil_tipo === 'estudiante' || $perfil_tipo === 'tercera_edad') {
            $tipo_desc_id = ($perfil_tipo === 'estudiante') ? 2 : 3; // 2=Estudiante, 3=Tercera Edad
            
            // Procesar Archivo
            if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Es obligatorio subir un comprobante para el perfil seleccionado.');
            }
            
            $file_tmp = $_FILES['comprobante']['tmp_name'];
            $file_name = time() . "_" . $_FILES['comprobante']['name'];
            $target_path = "../uploads/documentos/" . $file_name;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Registrar solicitud de validación especial con la URL del archivo
                $stmt = $pdo->prepare("INSERT INTO VALIDACION_ESPECIAL (usuario_id, tipo_desc_id, comprobante_url, estado_validacion, fecha_solicitud) VALUES (?, ?, ?, 'PENDIENTE', NOW())");
                $stmt->execute([$usuario_id, $tipo_desc_id, $file_name]);
                $rol_texto .= " (Solicitud de tarifa reducida en revisión)";
            } else {
                throw new Exception('Error al guardar el comprobante en el servidor.');
            }
        }
    }

    $pdo->commit();
    sendResponse(true, "✅ Registro exitoso como $rol_texto. Ahora puedes iniciar sesión.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    sendResponse(false, 'Error: ' . $e->getMessage()); 
}
?>