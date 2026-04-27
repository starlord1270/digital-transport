<?php
/**
 * DIGITAL TRANSPORT - RESOLVER TICKET (SUPER ADMIN)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
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

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'RESOLUCION_TICKET', "Se resolvió el ticket #$ticket_id");

    echo json_encode(['success' => true, 'message' => 'Ticket resuelto y notificado al usuario.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
