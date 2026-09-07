<?php
// backend/change-password-admin.php

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

$response = ['success' => false, 'message' => ''];

// 1. Verificación de Sesión y Rol (ADMIN_LINEA = 4 o SUPER_ADMIN = 5)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [4, 5], true)) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    echo json_encode($response);
    exit;
}

// CSRF Protection
if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

// 2. Recepción y Validación de Datos (POST)
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $response['message'] = 'Todos los campos de contraseña son requeridos.';
    echo json_encode($response);
    exit;
}

if ($new_password !== $confirm_password) {
    $response['message'] = 'La nueva contraseña y la confirmación no coinciden.';
    echo json_encode($response);
    exit;
}

if (strlen($new_password) < 8) {
    $response['message'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
    echo json_encode($response);
    exit;
}

try {
    // 3. Verificar Contraseña Actual
    $stmt_fetch = $pdo->prepare("SELECT password_hash FROM USUARIO WHERE usuario_id = :uid");
    $stmt_fetch->execute([':uid' => $usuario_id]);
    $user_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
        $response['message'] = 'La contraseña actual es incorrecta.';
        echo json_encode($response);
        exit;
    }

    // 4. Actualizar Contraseña con hash seguro
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt_update = $pdo->prepare("UPDATE USUARIO SET password_hash = :hash WHERE usuario_id = :uid");
    $stmt_update->execute([':hash' => $new_hash, ':uid' => $usuario_id]);

    $response['success'] = true;
    $response['message'] = 'Contraseña actualizada con éxito.';
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Error al actualizar la contraseña en la base de datos.';
}

echo json_encode($response);
?>