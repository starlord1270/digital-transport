<?php
/**
 * DIGITAL TRANSPORT - ACTUALIZAR TARIFA GLOBAL (SUPER ADMIN)
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

$tarifa_id = (int)($_POST['tarifa_id'] ?? 0);
$monto = floatval($_POST['monto'] ?? 0);

if ($tarifa_id <= 0 || $monto <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos de tarifa inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE TARIFA SET monto = ? WHERE tarifa_id = ?");
    $stmt->execute([$monto, $tarifa_id]);

    // Registrar en auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CAMBIO_TARIFA', "Se actualizó la tarifa ID $tarifa_id a Bs. $monto");

    echo json_encode(['success' => true, 'message' => 'Tarifa actualizada globalmente.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
