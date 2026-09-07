<?php
/**
 * DIGITAL TRANSPORT - FETCH TARIFAS GLOBAL (SUPER ADMIN)
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
    $stmt = $pdo->query("SELECT tarifa_id, nombre, monto FROM TARIFA ORDER BY tarifa_id ASC");
    $tarifas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'tarifas' => $tarifas]);

} catch (PDOException $e) {
    error_log("Error en fetch_tarifas_global: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener tarifas globales.']);
}
?>
