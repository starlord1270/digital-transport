<?php
/**
 * DIGITAL TRANSPORT - PROCESAR VALIDACIÓN DE PASAJERO (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Verificación de Permisos (Solo Admin de Línea o Sistema)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

$validacion_id = (int)($_POST['validacion_id'] ?? 0);
$estado = $_POST['estado'] ?? ''; // APROBADA o RECHAZADA

if ($validacion_id === 0 || !in_array($estado, ['APROBADA', 'RECHAZADA'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE VALIDACION_ESPECIAL SET estado_validacion = ? WHERE validacion_id = ?");
    $stmt->execute([$estado, $validacion_id]);

    echo json_encode(['success' => true, 'message' => "Solicitud marcada como $estado exitosamente."]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>
