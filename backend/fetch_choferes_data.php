<?php
/**
 * DIGITAL TRANSPORT - FETCH CHOFERES DATA (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

$userRole = (int)($_SESSION['tipo_usuario_id'] ?? 0);
$usuarioId = (int)$_SESSION['usuario_id'];

if (!in_array($userRole, [4, 5], true)) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

try {
    if ($userRole === 4) {
        $stmtLinea = $pdo->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
        $stmtLinea->execute([$usuarioId]);
        $lineaId = (int)$stmtLinea->fetchColumn();
    } else {
        $lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : (int)($_SESSION['linea_id'] ?? 0);
    }

    if ($lineaId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Línea no asignada o inválida.']);
        exit;
    }

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
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
?>