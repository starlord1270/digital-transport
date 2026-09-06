<?php
/**
 * DIGITAL TRANSPORT - UPDATE PERFIL (REFACTORIZADA)
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
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($nombre_completo) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE USUARIO SET nombre_completo = ?, email = ? WHERE usuario_id = ?");
    $stmt->execute([$nombre_completo, $email, $user_id]);

    if ($stmt->rowCount() >= 0) {
        $_SESSION['nombre_completo'] = $nombre_completo;
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
?>