<?php
/**
 * DIGITAL TRANSPORT - ELIMINAR PUNTO DE RECARGA (SUPER ADMIN)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente.']);
    exit;
}

$punto_id = (int)($_POST['punto_id'] ?? 0);

if ($punto_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT nombre FROM PUNTO_RECARGA WHERE punto_id = ?");
    $stmt->execute([$punto_id]);
    $nombre = $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM PUNTO_RECARGA WHERE punto_id = ?");
    $stmt->execute([$punto_id]);

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'ELIMINAR_PUNTO', "Se eliminó definitivamente el punto '$nombre'");

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Error en eliminar_punto: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'No se puede eliminar (puede tener transacciones vinculadas).']);
}
?>
