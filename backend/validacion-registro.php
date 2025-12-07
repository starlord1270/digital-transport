<?php
// backend/validacion-registro.php

// 1. GESTIÓN DE ERRORES Y CONFIGURACIÓN INICIAL
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// Incluir la conexión a la Base de Datos compartida
require_once 'bd.php';

// Función de respuesta centralizada
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Verificar conexión heredada de bd.php
if ($conn->connect_error) {
    sendResponse(false, 'Error de conexión a BD: ' . $conn->connect_error);
}

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
$conn->begin_transaction();

try {
    
    // 4. VERIFICACIÓN CRÍTICA: La existencia del ID de Línea (si aplica)
    if ($linea_id > 0) {
        $sql_linea_check = "SELECT linea_id FROM LINEA WHERE linea_id = ?";
        $stmt_linea_check = $conn->prepare($sql_linea_check);
        $stmt_linea_check->bind_param("i", $linea_id);
        $stmt_linea_check->execute();
        $stmt_linea_check->store_result();
        
        if ($stmt_linea_check->num_rows === 0) {
            throw new Exception('❌ ERROR CRÍTICO DE LÍNEA: El ID ' . $linea_id . ' no existe en la tabla LINEA.');
        }
        $stmt_linea_check->close();
    }
    
    // 5. VERIFICAR QUE EL EMAIL NO EXISTA YA
    $sql_check = "SELECT usuario_id FROM USUARIO WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        throw new Exception('El email ya se encuentra registrado en el sistema.');
    }
    $stmt_check->close();

    // 6. INSERCIÓN EN LA TABLA USUARIO
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Asumimos fecha_registro NOW() en BD o lo pasamos explícito si es necesario, 
    // pero el script original no lo pasaba explícito en el insert de PDO, aunque sí había un insert comentado en frontend.
    // Vamos a añadir fecha_registro = NOW() para consistencia.
    $sql_usuario = "INSERT INTO USUARIO (nombre_completo, documento_identidad, email, password_hash, tipo_usuario_id, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssssi", $nombre_completo, $documento_identidad, $email, $password_hash, $tipo_usuario_id);
    
    if (!$stmt_usuario->execute()) {
        throw new Exception("Error al insertar usuario: " . $stmt_usuario->error);
    }
    
    $usuario_id = $conn->insert_id;
    $stmt_usuario->close();
    
    $rol_texto = 'Usuario';

    // 7. INSERCIÓN EN LA TABLA ESPECÍFICA (ADMIN o CHOFER)
    if ($tipo_usuario_id === 4) {
        // ADMIN_LINEA
        $sql_admin = "INSERT INTO ADMIN_LINEA (usuario_id, linea_id, cargo) VALUES (?, ?, ?)";
        $stmt_admin = $conn->prepare($sql_admin);
        $stmt_admin->bind_param("iis", $usuario_id, $linea_id, $cargo);
        
        if (!$stmt_admin->execute()) {
             throw new Exception("Error al insertar Admin Linea: " . $stmt_admin->error);
        }
        $stmt_admin->close();
        $rol_texto = 'Administrador de Línea';

    } elseif ($tipo_usuario_id === 3) {
        // CHOFER
        
        // ⭐ LÓGICA DE CREACIÓN DINÁMICA DE VEHÍCULO SI ES NUEVO
        $sql_check_vehiculo = "SELECT placa FROM VEHICULO WHERE placa = ?";
        $stmt_check_vehiculo = $conn->prepare($sql_check_vehiculo);
        $stmt_check_vehiculo->bind_param("s", $vehiculo_placa);
        $stmt_check_vehiculo->execute();
        $stmt_check_vehiculo->store_result();
        
        if ($stmt_check_vehiculo->num_rows === 0) {
            // El vehículo no existe, lo insertamos primero
            $stmt_check_vehiculo->close();
            
            $sql_insert_vehiculo = "INSERT INTO VEHICULO (placa, modelo, capacidad, linea_id) 
                                    VALUES (?, ?, ?, ?)";
            $stmt_insert_vehiculo = $conn->prepare($sql_insert_vehiculo);
            
            $modelo_defecto = 'Registrado por Chofer (' . $vehiculo_placa . ')';
            $capacidad_defecto = 40; 
            
            $stmt_insert_vehiculo->bind_param("ssii", $vehiculo_placa, $modelo_defecto, $capacidad_defecto, $linea_id);
            if (!$stmt_insert_vehiculo->execute()) {
                throw new Exception("Error al crear vehículo automático: " . $stmt_insert_vehiculo->error);
            }
            $stmt_insert_vehiculo->close();
        } else {
             $stmt_check_vehiculo->close();
        }
        
        // Inserción del Chofer
        $estado_servicio = 'INACTIVO'; 
        $sql_chofer = "INSERT INTO CHOFER (usuario_id, linea_id, licencia, vehiculo_placa, estado_servicio) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_chofer = $conn->prepare($sql_chofer);
        $stmt_chofer->bind_param("iisss", $usuario_id, $linea_id, $licencia, $vehiculo_placa, $estado_servicio);
        
        if (!$stmt_chofer->execute()) {
            throw new Exception("Error al insertar Chofer: " . $stmt_chofer->error);
        }
        $stmt_chofer->close();
        $rol_texto = 'Chofer';
        
    } elseif ($tipo_usuario_id == 1 || $tipo_usuario_id == 2) {
        // Pasajeros, no requieren tabla extra
        $rol_texto = 'Pasajero';
    } else {
        // Otros roles no soportados en este formulario
        throw new Exception('Error interno: ID de rol no reconocido completamente para inserción.');
    }

    // 8. COMMIT y RESPUESTA FINAL
    $conn->commit();
    sendResponse(true, "✅ Registro exitoso como $rol_texto. Serás redirigido al login.");

} catch (Exception $e) {
    $conn->rollback();
    sendResponse(false, '❌ Error al procesar el registro: ' . $e->getMessage()); 
} finally {
    $conn->close();
}
?>