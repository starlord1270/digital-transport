<?php
/**
 * DIGITAL TRANSPORT - FETCH PUNTOS DE RECARGA (SUPER ADMIN)
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
            P.punto_id,
            P.nombre,
            P.ubicacion,
            P.estado,
            U.nombre_completo as operador,
            U.email
        FROM 
            PUNTO_RECARGA P
        JOIN 
            USUARIO U ON P.usuario_id = U.usuario_id
        ORDER BY 
            P.nombre ASC
    ";

    $stmt = $pdo->query($sql);
    $puntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'puntos' => $puntos]);

} catch (PDOException $e) {
    error_log("Error en fetch_puntos_recarga: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener puntos de recarga.']);
}
?>
