<?php
// backend/change-password-pasajero.php

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

// 3. Recepción y validación de datos (Vienen del formulario POST)
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $response['message'] = 'Todos los campos de contraseña son obligatorios.';
    $conn->close();
    echo json_encode($response);
    exit;
}

if ($new_password !== $confirm_password) {
    $response['message'] = 'La nueva contraseña y la confirmación no coinciden.';
    $conn->close();
    echo json_encode($response);
    exit;
}

if (strlen($new_password) < 6) {
    $response['message'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 4. Verificar contraseña actual (MySQLi)
$sql_fetch_pass = "SELECT password_hash FROM USUARIO WHERE usuario_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_pass);

if (!$stmt_fetch) {
    $response['message'] = 'Error de preparación de consulta (fetch): ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

$stmt_fetch->bind_param("i", $usuario_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
$user_data = $result->fetch_assoc();
$stmt_fetch->close();

// Verificar si el usuario existe y si la contraseña actual coincide
if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
    $response['message'] = 'La contraseña actual es incorrecta.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 5. Hashear y actualizar la nueva contraseña (MySQLi)
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$sql_update_pass = "UPDATE USUARIO SET password_hash = ? WHERE usuario_id = ?";
$stmt_update = $conn->prepare($sql_update_pass);

if (!$stmt_update) {
    $response['message'] = 'Error de preparación de consulta (update): ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

$stmt_update->bind_param("si", $new_password_hash, $usuario_id);

if ($stmt_update->execute()) {
    $response['success'] = true;
    $response['message'] = 'Contraseña actualizada con éxito.';
} else {
    $response['message'] = 'Error al actualizar la contraseña: ' . $stmt_update->error;
}

$stmt_update->close();
$conn->close();
echo json_encode($response);
?>