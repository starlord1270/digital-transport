<?php
// backend/update-perfil-pasajero.php

header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// 1. Incluir la conexión MySQLi (usa el objeto $conn)
require_once 'bd.php'; 

// 2. Verificación de Sesión y Rol (Pasajero)
$allowed_pasajero_types = [1, 2, 5, 6]; 
$tipo_usuario_id = $_SESSION['tipo_usuario_id'] ?? 0;

if (!isset($_SESSION['usuario_id']) || !in_array($tipo_usuario_id, $allowed_pasajero_types)) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    $conn->close();
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// 3. Recepción y Validación de Datos (Asumiendo que el frontend usa FormData o POST)
$nombre_completo = $_POST['nombre_completo'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($nombre_completo) || empty($email)) {
    $response['message'] = 'Todos los campos requeridos deben estar llenos.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 4. Actualizar USUARIO (Usando consultas preparadas MySQLi)
$sql_update_user = "
    UPDATE USUARIO 
    SET 
        nombre_completo = ?, 
        email = ?
    WHERE 
        usuario_id = ?
";

// 🛑 USANDO MySQLi con $conn
$stmt_user = $conn->prepare($sql_update_user);

if (!$stmt_user) {
    $response['message'] = 'Error de preparación de consulta: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

// "ssi" = dos strings (nombre, email) y un integer (usuario_id)
$stmt_user->bind_param("ssi", $nombre_completo, $email, $usuario_id);

if ($stmt_user->execute()) {
    
    // Verificar si se afectó alguna fila (el cambio fue real)
    if ($stmt_user->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Perfil actualizado con éxito.';
        // Actualizar la sesión con el nuevo nombre
        $_SESSION['nombre_completo'] = $nombre_completo;
    } else {
        $response['success'] = true;
        $response['message'] = 'Perfil actualizado, pero no se detectaron cambios en los valores.';
    }
} else {
    $response['message'] = 'Error al actualizar el perfil: ' . $stmt_user->error;
}

$stmt_user->close();
$conn->close();
echo json_encode($response);
?>