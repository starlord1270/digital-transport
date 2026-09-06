<?php
/**
 * DIGITAL TRANSPORT - TOGGLE ESTADO PUNTO RECARGA (SUPER ADMIN)
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

$punto_id = (int)($_POST['punto_id'] ?? 0);
$nuevo_estado = $_POST['estado'] ?? '';

if ($punto_id <= 0 || !in_array($nuevo_estado, ['ACTIVO', 'INACTIVO'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE PUNTO_RECARGA SET estado = ? WHERE punto_id = ?");
    $stmt->execute([$nuevo_estado, $punto_id]);

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CAMBIO_ESTADO_PUNTO', "Se cambió el estado del punto #$punto_id a $nuevo_estado");

    echo json_encode(['success' => true, 'message' => "Punto marcado como $nuevo_estado"]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
