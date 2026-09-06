<?php
/**
 * DIGITAL TRANSPORT - FETCH SOPORTE (SUPER ADMIN)
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
            T.ticket_id,
            U.nombre_completo as nombre,
            T.asunto,
            T.mensaje,
            T.prioridad,
            T.estado,
            DATE_FORMAT(T.fecha_creacion, '%d/%m/%Y %H:%i') as fecha
        FROM 
            TICKET_SOPORTE T
        JOIN 
            USUARIO U ON T.usuario_id = U.usuario_id
        ORDER BY 
            CASE WHEN T.estado = 'ABIERTO' THEN 1 WHEN T.estado = 'EN_PROCESO' THEN 2 ELSE 3 END,
            T.fecha_creacion DESC
    ";

    $stmt = $pdo->query($sql);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'tickets' => $tickets]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
