<?php
/**
 * DIGITAL TRANSPORT - FETCH NOTIFICACIONES (SUPER ADMIN)
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

try {
    $stmt = $pdo->query("SELECT *, DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha FROM NOTIFICACION_GLOBAL ORDER BY fecha_creacion DESC LIMIT 20");
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'notificaciones' => $notificaciones]);

} catch (PDOException $e) {
    error_log("Error en fetch_notificaciones_globales: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener notificaciones globales.']);
}
?>
