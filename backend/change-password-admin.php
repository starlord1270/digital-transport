<?php
// backend/change-password-admin.php

header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// 1. Verificación de Sesión y Rol (Debe ser ADMIN_LINEA = 4)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['tipo_usuario_id'] != 4) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
// Asegúrate de que 'bd.php' contenga tu conexión MySQL
require_once 'bd.php'; 

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos: ' . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// 2. Recepción y Validación de Datos (POST)
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $response['message'] = 'Todos los campos de contraseña son requeridos.';
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

if (strlen($new_password) < 6) { // Requiere mínimo 6 caracteres
    $response['message'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 3. Verificar Contraseña Actual
$sql_fetch_hash = "SELECT password FROM USUARIO WHERE usuario_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_hash);
$stmt_fetch->bind_param("i", $usuario_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data || !password_verify($current_password, $user_data['password'])) {
    $response['message'] = 'La contraseña actual es incorrecta.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 4. Actualizar Contraseña (con hash seguro)
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$sql_update_password = "UPDATE USUARIO SET password = ? WHERE usuario_id = ?";
$stmt_update = $conn->prepare($sql_update_password);

if ($stmt_update === false) {
    $response['message'] = 'Error al preparar la consulta de actualización: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

$stmt_update->bind_param("si", $new_password_hash, $usuario_id);

if ($stmt_update->execute()) {
    $response['success'] = true;
    $response['message'] = 'Contraseña actualizada con éxito. Por favor, vuelva a iniciar sesión si encuentra problemas.';
} else {
    $response['message'] = 'Error al actualizar la contraseña: ' . $stmt_update->error;
}

$stmt_update->close();
$conn->close();
echo json_encode($response);
?>