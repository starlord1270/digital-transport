<?php
/**
 * DIGITAL TRANSPORT - RESOLVER TICKET (SUPER ADMIN)
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

$ticket_id = (int)($_POST['ticket_id'] ?? 0);
$respuesta = trim($_POST['respuesta'] ?? '');

if ($ticket_id <= 0 || empty($respuesta)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE TICKET_SOPORTE SET respuesta_admin = ?, estado = 'RESUELTO', fecha_resolucion = NOW() WHERE ticket_id = ?");
    $stmt->execute([$respuesta, $ticket_id]);

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'RESOLUCION_TICKET', "Se resolvió el ticket #$ticket_id");

    echo json_encode(['success' => true, 'message' => 'Ticket resuelto y notificado al usuario.']);

} catch (PDOException $e) {
    error_log("Error en resolver_ticket: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el ticket de soporte.']);
}
?>
