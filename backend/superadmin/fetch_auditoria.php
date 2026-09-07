<?php
/**
 * DIGITAL TRANSPORT - FETCH AUDITORIA (SUPER ADMIN)
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
            A.log_id,
            U.nombre_completo as admin,
            U.email,
            A.accion,
            A.detalles,
            DATE_FORMAT(A.fecha_hora, '%d/%m/%Y') as fecha,
            DATE_FORMAT(A.fecha_hora, '%H:%i:%s') as hora
        FROM 
            AUDITORIA A
        JOIN 
            USUARIO U ON A.usuario_id = U.usuario_id
        ORDER BY 
            A.fecha_hora DESC
        LIMIT 100
    ";

    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'logs' => $logs]);

} catch (PDOException $e) {
    error_log("Error en fetch_auditoria: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener registros de auditoría.']);
}
?>
