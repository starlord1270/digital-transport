<?php
/**
 * DIGITAL TRANSPORT - FETCH CHOFERES DATA (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : ($_SESSION['linea_id'] ?? 0);

if ($lineaId === 0 || !isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

try {
    // Consulta para listar choferes con su estado y estadísticas
    $sql = "
        SELECT 
            C.chofer_id,
            U.nombre_completo,
            U.email,
            C.vehiculo_placa,
            C.estado_servicio,
            C.licencia,
            COALESCE(SUM(CASE WHEN T.tipo = 'COBRO' AND DATE(T.fecha_hora) = CURDATE() THEN 1 ELSE 0 END), 0) AS boletos_hoy,
            COALESCE(SUM(CASE WHEN T.tipo = 'COBRO' THEN T.monto ELSE 0 END), 0) AS total_recaudado
        FROM 
            CHOFER C
        JOIN 
            USUARIO U ON C.usuario_id = U.usuario_id
        LEFT JOIN 
            TRANSACCION T ON C.chofer_id = T.chofer_id_cobro
        WHERE 
            C.linea_id = ? 
        GROUP BY 
            C.chofer_id, U.nombre_completo, U.email, C.vehiculo_placa, C.estado_servicio, C.licencia
        ORDER BY 
            FIELD(C.estado_servicio, 'PENDIENTE', 'ACTIVO', 'INACTIVO', 'LICENCIA'), U.nombre_completo ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lineaId]);
    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'choferes' => $choferes]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>