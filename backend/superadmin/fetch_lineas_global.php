<?php
/**
 * DIGITAL TRANSPORT - FETCH LINEAS GLOBAL (SUPER ADMIN)
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
    $sql = "
        SELECT 
            L.linea_id,
            L.nombre,
            (SELECT COUNT(*) FROM RUTA R WHERE R.linea_id = L.linea_id) as total_rutas
        FROM 
            LINEA L
        ORDER BY 
            L.linea_id ASC
    ";

    $stmt = $pdo->query($sql);
    $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'lineas' => $lineas]);

} catch (PDOException $e) {
    error_log("Error en fetch_lineas_global: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener lista de líneas.']);
}
?>
