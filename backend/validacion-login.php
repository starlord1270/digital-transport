<?php
// backend/validacion-login.php

// 1. Establecer la cabecera JSON
header('Content-Type: application/json');

// 2. Incluir el archivo de conexión (bd.php)
require_once 'bd.php'; 

// Inicialización de la respuesta
$response = [
    'success' => false,
    'message' => 'Error de autenticación.',
    'redirect' => ''
];

// Comprobación de la conexión a la base de datos
if ($conn->connect_error) {
    $response['message'] = 'Error grave de conexión a la base de datos. Verifique el servidor MySQL.';
    @$conn->close();
    echo json_encode($response);
    exit;
}

// 3. Recoger y Sanear los datos
if (!isset($_POST['email']) || !isset($_POST['password'])) {
    $response['message'] = 'Faltan campos obligatorios.';
    $conn->close(); 
    echo json_encode($response);
    exit;
}

$email = $conn->real_escape_string(trim($_POST['email']));
$password = $_POST['password'];

// 4. Preparar la consulta SQL
$sql = "SELECT usuario_id, password_hash, tipo_usuario_id, nombre_completo FROM USUARIO WHERE email = ?"; 
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $response['message'] = 'Error interno del servidor. No se pudo preparar la consulta.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// Enlazar parámetro y ejecutar
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close(); 

if ($user) {
    // 5. Verificar la contraseña
    if (password_verify($password, $user['password_hash'])) {
        
        // Credenciales correctas. Iniciar sesión.
        session_start();
        
        // ⭐ CORRECCIÓN CLAVE 1: Limpiar y asegurar la sesión
        // Limpia completamente la sesión anterior y genera un nuevo ID para prevenir ataques de fijación de sesión.
        $_SESSION = array(); 
        session_regenerate_id(true); // <--- AÑADIDO: Regenera el ID de sesión por seguridad.
        
        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['tipo_usuario_id'] = $user['tipo_usuario_id'];
        $_SESSION['logged_in'] = true;
        
        // Guardar el nombre completo en la sesión 
        $_SESSION['nombre_completo'] = $user['nombre_completo'];

        $tipo_id = $user['tipo_usuario_id'];

        // 6. Determinar la redirección y obtener datos adicionales
        
        if ($tipo_id == 4) { // ADMIN_LINEA
            
            $stmt_linea = $conn->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
            
            if ($stmt_linea === false) {
                // Manejar error si la consulta del admin de línea falla.
                $response['success'] = false;
                $response['message'] = "Error interno al verificar la línea. Contacte a soporte.";
            } else {
                $stmt_linea->bind_param("i", $user['usuario_id']);
                $stmt_linea->execute();
                $result_linea = $stmt_linea->get_result();
                
                if ($admin_data = $result_linea->fetch_assoc()) {
                    $_SESSION['linea_id'] = $admin_data['linea_id']; 
                    
                    $response['success'] = true;
                    $response['message'] = '¡Bienvenido Administrador! Redirigiendo...';
                    $response['redirect'] = '/Competencia-Analisis/digital-transport/frontend/dashboard-admin-linea/dashboard-admin.php'; 
                } else {
                    $response['success'] = false;
                    $response['message'] = "Error: Administrador de Línea sin asignación. Contacte a soporte.";
                }
                
                $stmt_linea->close(); // <--- CORREGIDO: Cerrar el statement
            }

        } elseif ($tipo_id == 3) { // CHOFER
            
            // ACCIÓN CLAVE 1: ACTUALIZAR ESTADO A 'ACTIVO'
            $chofer_usuario_id = $user['usuario_id'];
            
            $sql_update = "UPDATE CHOFER SET estado_servicio = 'ACTIVO' WHERE usuario_id = ?";
            $stmt_update = $conn->prepare($sql_update);

            if ($stmt_update) {
                $stmt_update->bind_param("i", $chofer_usuario_id);
                $stmt_update->execute();
                $stmt_update->close();
            } 
            // Nota: Se asume que el update fue exitoso para el flujo de login del chofer.
            
            $response['success'] = true;
            $response['message'] = '¡Bienvenido Chofer! Redirigiendo a Cobro...';
            $response['redirect'] = '/Competencia-Analisis/digital-transport/frontend/choferes/cobro-chofer.php'; 
        
        } else {
            // OTROS USUARIOS (Pasajero, Admin Central, etc.)
            $response['success'] = true;
            $response['message'] = '¡Bienvenido! Redirigiendo...';
            $response['redirect'] = '/Competencia-Analisis/digital-transport/frontend/cliente/panel-principal.php'; 
        }
        
    } else {
        $response['message'] = 'Email o Contraseña incorrectos.';
    }
} else {
    $response['message'] = 'Email o Contraseña incorrectos.';
}

// 7. Cerrar la conexión y enviar la respuesta
$conn->close();
echo json_encode($response);
exit;