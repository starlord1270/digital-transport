<?php
/**
 * DIGITAL TRANSPORT - PROCESAR VALIDACIÓN DE PASAJERO (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

// Verificación de Permisos (Admin de Línea 4 o SuperAdmin 5)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [4, 5], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// Check CSRF Token
if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente.']);
    exit;
}

$validacion_id = (int)($_POST['validacion_id'] ?? 0);
$estado = $_POST['estado'] ?? ''; // APROBADA o RECHAZADA

if ($validacion_id === 0 || !in_array($estado, ['APROBADA', 'RECHAZADA'], true)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE VALIDACION_ESPECIAL SET estado_validacion = ? WHERE validacion_id = ?");
    $stmt->execute([$estado, $validacion_id]);

    echo json_encode(['success' => true, 'message' => "Solicitud marcada como $estado exitosamente."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
?>
