<?php
/**
 * DIGITAL TRANSPORT - GUARDAR NUEVA LÍNEA (SUPER ADMIN)
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

$nombre = trim($_POST['nombre'] ?? '');

if (empty($nombre)) {
    echo json_encode(['success' => false, 'error' => 'El nombre de la línea es obligatorio.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO LINEA (nombre) VALUES (?)");
    $stmt->execute([$nombre]);

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CREAR_LINEA', "Se creó la línea: $nombre");

    echo json_encode(['success' => true, 'message' => 'Línea creada exitosamente.']);

} catch (PDOException $e) {
    error_log("Error en guardar_linea: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar la línea en la base de datos.']);
}
?>
