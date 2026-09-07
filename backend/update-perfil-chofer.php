<?php
/**
 * DIGITAL TRANSPORT - UPDATE PERFIL CHOFER (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

try {
    $usuario_id = (int)$_SESSION['usuario_id'];
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $licencia = trim($_POST['licencia'] ?? '');

    if (empty($nombre) || empty($email) || empty($licencia)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Actualizar Usuario
    $stmt = $pdo->prepare("UPDATE USUARIO SET nombre_completo = ?, email = ? WHERE usuario_id = ?");
    $stmt->execute([$nombre, $email, $usuario_id]);

    // 2. Actualizar Chofer
    $stmt = $pdo->prepare("UPDATE CHOFER SET licencia = ? WHERE usuario_id = ?");
    $stmt->execute([$licencia, $usuario_id]);

    $pdo->commit();
    
    $_SESSION['nombre_completo'] = $nombre;

    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil en la base de datos.']);
}
?>