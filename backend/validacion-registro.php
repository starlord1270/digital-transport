<?php
// backend/validacion-registro.php

// 1. GESTIÓN DE ERRORES Y CONFIGURACIÓN INICIAL
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// Función de respuesta centralizada
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// --- CONFIGURACIÓN DE LA BASE DE DATOS (PDO) ---
$dbHost = 'localhost';
$dbName = 'digital-transport'; 
$dbUser = 'root'; 
$dbPass = ''; 

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    sendResponse(false, 'Error de conexión a BD. Revise la configuración ($dbUser, $dbPass).');
}
// --- FIN CONFIGURACIÓN DE LA BASE DE DATOS ---

// 2. RECEPCIÓN Y ASIGNACIÓN DE DATOS DEL FORMULARIO
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$documento_identidad = trim($_POST['documento_identidad'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$tipo_usuario_id = (int)($_POST['tipo_usuario_id'] ?? 0);
$linea_id = (int)($_POST['linea_id'] ?? 0); 

$cargo = trim($_POST['cargo'] ?? ''); 
$licencia = trim($_POST['licencia'] ?? ''); 
$vehiculo_placa = trim($_POST['vehiculo_placa'] ?? ''); 

// 3. VALIDACIÓN BÁSICA DE CAMPOS
if (empty($nombre_completo) || empty($documento_identidad) || empty($email) || empty($password) || $tipo_usuario_id <= 0) {
    sendResponse(false, 'Por favor, complete todos los campos de usuario.');
}
if ($tipo_usuario_id == 4 && (empty($cargo) || $linea_id <= 0)) {
    sendResponse(false, 'Para Administrador, el Cargo y el ID de Línea son obligatorios.');
}
// La placa y la licencia son obligatorias para el Chofer.
if ($tipo_usuario_id == 3 && (empty($licencia) || empty($vehiculo_placa) || $linea_id <= 0)) {
    sendResponse(false, 'Para Chofer, la Licencia, la Placa del Vehículo y el ID de Línea son obligatorios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'El formato del email no es válido.');
}


// --- INICIO DE LA TRANSACCIÓN ---
try {
    $pdo->beginTransaction();
    
    // 4. VERIFICACIÓN CRÍTICA: La existencia del ID de Línea
    $sql_linea_check = "SELECT linea_id FROM LINEA WHERE linea_id = ?";
    $stmt_linea_check = $pdo->prepare($sql_linea_check);
    $stmt_linea_check->execute([$linea_id]);
    
    if (!$stmt_linea_check->fetch()) {
        $pdo->rollBack();
        // Mensaje de error específico para tu caso:
        sendResponse(false, '❌ ERROR CRÍTICO DE LÍNEA: El ID ' . $linea_id . ' no existe en la tabla LINEA. Verifique la BD o el formulario.');
    }
    
    // 5. VERIFICAR QUE EL EMAIL NO EXISTA YA
    $sql_check = "SELECT usuario_id FROM USUARIO WHERE email = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$email]);
    if ($stmt_check->fetch()) {
        $pdo->rollBack(); 
        sendResponse(false, 'El email ya se encuentra registrado en el sistema.');
    }

    // 6. INSERCIÓN EN LA TABLA USUARIO
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql_usuario = "INSERT INTO USUARIO (nombre_completo, documento_identidad, email, password_hash, tipo_usuario_id) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_usuario = $pdo->prepare($sql_usuario);
    $stmt_usuario->execute([$nombre_completo, $documento_identidad, $email, $password_hash, $tipo_usuario_id]);
    
    $usuario_id = $pdo->lastInsertId(); 

    // 7. INSERCIÓN EN LA TABLA ESPECÍFICA (ADMIN o CHOFER)
    if ($tipo_usuario_id === 4) {
        // ADMIN_LINEA
        $sql_admin = "INSERT INTO ADMIN_LINEA (usuario_id, linea_id, cargo) VALUES (?, ?, ?)";
        $stmt_admin = $pdo->prepare($sql_admin);
        $stmt_admin->execute([$usuario_id, $linea_id, $cargo]);
        $rol_texto = 'Administrador de Línea';

    } elseif ($tipo_usuario_id === 3) {
        // CHOFER
        
        // ⭐ LÓGICA DE CREACIÓN DINÁMICA DE VEHÍCULO SI ES NUEVO
        $sql_check_vehiculo = "SELECT placa FROM VEHICULO WHERE placa = ?";
        $stmt_check_vehiculo = $pdo->prepare($sql_check_vehiculo);
        $stmt_check_vehiculo->execute([$vehiculo_placa]);

        if (!$stmt_check_vehiculo->fetch()) {
            // El vehículo no existe, lo insertamos primero
            $sql_insert_vehiculo = "INSERT INTO VEHICULO (placa, modelo, capacidad, linea_id) 
                                    VALUES (?, ?, ?, ?)";
            $stmt_insert_vehiculo = $pdo->prepare($sql_insert_vehiculo);
            
            $modelo_defecto = 'Registrado por Chofer (' . $vehiculo_placa . ')';
            $capacidad_defecto = 40; // Asignar una capacidad estándar
            
            // Se inserta usando la linea_id que el Chofer especificó
            $stmt_insert_vehiculo->execute([$vehiculo_placa, $modelo_defecto, $capacidad_defecto, $linea_id]);
        }
        
        // Inserción del Chofer
        $estado_servicio = 'INACTIVO'; 
        $sql_chofer = "INSERT INTO CHOFER (usuario_id, linea_id, licencia, vehiculo_placa, estado_servicio) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_chofer = $pdo->prepare($sql_chofer);
        $stmt_chofer->execute([$usuario_id, $linea_id, $licencia, $vehiculo_placa, $estado_servicio]);
        $rol_texto = 'Chofer';
        
    } else {
        $pdo->rollBack();
        sendResponse(false, 'Error interno: ID de rol no reconocido.');
    }

    // 8. COMMIT y RESPUESTA FINAL
    $pdo->commit();
    sendResponse(true, "✅ Registro exitoso como $rol_texto. Serás redirigido al login.");

} catch (PDOException $e) {
    $pdo->rollBack();
    
    // Este bloque CATCH ahora es menos probable que capture errores de FK,
    // pero sigue siendo importante para otros errores críticos de BD.
    sendResponse(false, '❌ Error al procesar el registro (DB): ' . $e->getMessage()); 
}
?>