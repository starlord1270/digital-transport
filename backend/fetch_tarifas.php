<?php
/**
 * DIGITAL TRANSPORT - FETCH TARIFA APLICABLE (PDO)
 * Este script determina automáticamente la tarifa que le corresponde al usuario
 * basándose en sus validaciones especiales (Estudiante, 3ra Edad, etc.)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Si no hay usuario en sesión, devolvemos la tarifa estándar por defecto
$usuario_id = $_SESSION['usuario_id'] ?? 0;

try {
    $tipo_desc_id = 1; // Tarifa Estándar por defecto

    if ($usuario_id > 0) {
        // Verificar si tiene una validación aprobada para cualquier tipo de descuento
        $stmt = $pdo->prepare("
            SELECT tipo_desc_id 
            FROM VALIDACION_ESPECIAL 
            WHERE usuario_id = ? AND estado_validacion = 'APROBADA' 
            LIMIT 1
        ");
        $stmt->execute([$usuario_id]);
        $validacion = $stmt->fetchColumn();
        
        if ($validacion) {
            $tipo_desc_id = $validacion;
        }
    }

    // Obtener la tarifa correspondiente al tipo de descuento encontrado
    $stmt = $pdo->prepare("
        SELECT tarifa_id, nombre, monto 
        FROM TARIFA 
        WHERE tipo_desc_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$tipo_desc_id]);
    $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si por alguna razón no hay tarifa para ese descuento, devolver la estándar
    if (!$tarifa) {
        $stmt = $pdo->query("SELECT tarifa_id, nombre, monto FROM TARIFA WHERE tipo_desc_id = 1 LIMIT 1");
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true, 
        'tarifa' => $tarifa,
        'es_especial' => ($tipo_desc_id != 1)
    ]);

} catch (PDOException $e) {
    error_log("Error en fetch_tarifas: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener tarifas.']);
}
?>