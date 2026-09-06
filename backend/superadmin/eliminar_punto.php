<?php
/**
 * DIGITAL TRANSPORT - ELIMINAR PUNTO DE RECARGA (SUPER ADMIN)
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

if ($punto_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

try {
    // Obtenemos el nombre antes de borrar para el log
    $stmt = $pdo->prepare("SELECT nombre FROM PUNTO_RECARGA WHERE punto_id = ?");
    $stmt->execute([$punto_id]);
    $nombre = $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM PUNTO_RECARGA WHERE punto_id = ?");
    $stmt->execute([$punto_id]);

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'ELIMINAR_PUNTO', "Se eliminó definitivamente el punto '$nombre'");

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'No se puede eliminar (puede tener transacciones vinculadas).']);
}
?>
