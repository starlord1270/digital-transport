<?php
/**
 * DIGITAL TRANSPORT - UPDATE PERFIL CHOFER (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $licencia = trim($_POST['licencia'] ?? '');

    if (empty($nombre) || empty($email) || empty($licencia)) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    $pdo->beginTransaction();

    // 1. Actualizar Usuario
    $stmt = $pdo->prepare("UPDATE USUARIO SET nombre_completo = ?, email = ? WHERE usuario_id = ?");
    $stmt->execute([$nombre, $email, $usuario_id]);

    // 2. Actualizar Chofer
    $stmt = $pdo->prepare("UPDATE CHOFER SET licencia = ? WHERE usuario_id = ?");
    $stmt->execute([$licencia, $usuario_id]);

    $pdo->commit();
    
    $_SESSION['nombre_completo'] = $nombre; // Actualizar sesión

    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>