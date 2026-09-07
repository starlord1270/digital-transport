<?php
/**
 * DIGITAL TRANSPORT - ENVIAR NOTIFICACIÓN GLOBAL (SUPER ADMIN)
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

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'COMUNICADO_GLOBAL', "Se emitió un anuncio a $tipo_objetivo: $titulo");

    echo json_encode(['success' => true, 'message' => 'Comunicado enviado.']);

} catch (PDOException $e) {
    error_log("Error en enviar_notificacion_global: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar la notificación global.']);
}
?>
