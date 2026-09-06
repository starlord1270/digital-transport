<?php
/**
 * DIGITAL TRANSPORT - CHANGE PASSWORD CHOFER (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';

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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
