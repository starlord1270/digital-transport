<?php
/**
 * DIGITAL TRANSPORT - ACTUALIZAR TARIFA GLOBAL (SUPER ADMIN)
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

$tarifa_id = (int)($_POST['tarifa_id'] ?? 0);
$monto = floatval($_POST['monto'] ?? 0);

if ($tarifa_id <= 0 || $monto <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos de tarifa inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE TARIFA SET monto = ? WHERE tarifa_id = ?");
    $stmt->execute([$monto, $tarifa_id]);

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CAMBIO_TARIFA', "Se actualizó la tarifa ID $tarifa_id a Bs. $monto");

    echo json_encode(['success' => true, 'message' => 'Tarifa actualizada globalmente.']);

} catch (PDOException $e) {
    error_log("Error en actualizar_tarifa: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar tarifa en la base de datos.']);
}
?>
