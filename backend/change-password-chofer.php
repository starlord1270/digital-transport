<?php
/**
 * DIGITAL TRANSPORT - CHANGE PASSWORD CHOFER (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

try {
    $usuario_id = (int)$_SESSION['usuario_id'];
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if ($new_pass !== $confirm_pass) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
        exit;
    }

    if (strlen($new_pass) < 8) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM USUARIO WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pass, $user['password_hash'])) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE USUARIO SET password_hash = ? WHERE usuario_id = ?");
        $stmt->execute([$hash, $usuario_id]);
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña en la base de datos.']);
}
?>
