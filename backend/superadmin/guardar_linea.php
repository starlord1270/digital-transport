<?php
/**
 * DIGITAL TRANSPORT - GUARDAR NUEVA LÍNEA (SUPER ADMIN)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

// Seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
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

    echo json_encode(['success' => true, 'message' => 'Línea creada exitosamente.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
