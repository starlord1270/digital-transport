<?php
/**
 * DIGITAL TRANSPORT - CHANGE PASSWORD (REFACTORIZADA)
 */
header('Content-Type: application/json');
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$user_id = $_SESSION['usuario_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password_hash FROM USUARIO WHERE usuario_id = ?");
    $stmt->execute([$user_id]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current_password, $hash)) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE USUARIO SET password_hash = ? WHERE usuario_id = ?");
    $stmt->execute([$new_hash, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada con éxito.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de seguridad: ' . $e->getMessage()]);
}
?>