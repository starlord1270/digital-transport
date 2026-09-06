<?php
/**
 * DIGITAL TRANSPORT - ENVIAR NOTIFICACIÓN GLOBAL (SUPER ADMIN)
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

$titulo = trim($_POST['titulo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');
$tipo_objetivo = $_POST['tipo_objetivo'] ?? 'TODOS';

if (empty($titulo) || empty($mensaje)) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO NOTIFICACION_GLOBAL (usuario_id_emisor, titulo, mensaje, tipo_objetivo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $titulo, $mensaje, $tipo_objetivo]);

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'COMUNICADO_GLOBAL', "Se emitió un anuncio a $tipo_objetivo: $titulo");

    echo json_encode(['success' => true, 'message' => 'Comunicado enviado.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
