<?php
/**
 * DIGITAL TRANSPORT - FETCH REPORTES FLUJO CAJA (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : ($_SESSION['linea_id'] ?? 0);
$fechaDesde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaHasta = $_GET['hasta'] ?? date('Y-m-d');

if ($lineaId === 0 || !isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

try {
    $reporte = [
        'fecha_inicio' => $fechaDesde,
        'fecha_fin' => $fechaHasta,
        'total_ingreso' => 0.00,
        'total_egreso' => 0.00,
        'flujo_neto' => 0.00,
        'grafico' => []
    ];

    // Consulta de ingresos por cobros
    $sqlIngresos = "
        SELECT DATE(fecha_hora) as fecha, SUM(monto) as total 
        FROM TRANSACCION T
        JOIN CHOFER C ON T.chofer_id_cobro = C.chofer_id
        WHERE C.linea_id = ? AND T.tipo = 'COBRO' AND DATE(T.fecha_hora) BETWEEN ? AND ?
        GROUP BY DATE(fecha_hora)
        ORDER BY fecha ASC
    ";
    
    $stmt = $pdo->prepare($sqlIngresos);
    $stmt->execute([$lineaId, $fechaDesde, $fechaHasta]);
    $ingresos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Consulta de egresos por canjes (Si existe la tabla CANJE_CHOFER)
    $egresos = [];
    try {
        $sqlEgresos = "
            SELECT DATE(fecha_canje) as fecha, SUM(monto) as total 
            FROM CANJE_CHOFER CC
            JOIN CHOFER C ON CC.chofer_id = C.chofer_id
            WHERE C.linea_id = ? AND DATE(CC.fecha_canje) BETWEEN ? AND ?
            GROUP BY DATE(fecha_canje)
        ";
        $stmt = $pdo->prepare($sqlEgresos);
        $stmt->execute([$lineaId, $fechaDesde, $fechaHasta]);
        $egresos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        // Si la tabla no existe aún, ignoramos
    }

    // Unificar datos para el gráfico
    $period = new DatePeriod(
        new DateTime($fechaDesde),
        new DateInterval('P1D'),
        (new DateTime($fechaHasta))->modify('+1 day')
    );

    foreach ($period as $date) {
        $d = $date->format('Y-m-d');
        $ing = (float)($ingresos[$d] ?? 0);
        $egr = (float)($egresos[$d] ?? 0);
        
        $reporte['total_ingreso'] += $ing;
        $reporte['total_egreso'] += $egr;
        
        $reporte['grafico'][] = [
            'fecha' => $d,
            'ingreso' => $ing,
            'egreso' => $egr,
            'neto' => $ing - $egr
        ];
    }

    $reporte['flujo_neto'] = $reporte['total_ingreso'] - $reporte['total_egreso'];

    echo json_encode(['success' => true, 'reporte' => $reporte]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>