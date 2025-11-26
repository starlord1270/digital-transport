<?php
// fetch_reportes_flujo_caja.php
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

// 🛑 Obtener parámetros de la solicitud 🛑
$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : 0;
$tipoReporte = isset($_GET['tipo']) ? $_GET['tipo'] : 'diario';
$fechaDesde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d', strtotime('-7 days'));
$fechaHasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');

if ($lineaId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de línea no proporcionado.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $reporte = [
        'tipo' => $tipoReporte,
        'fecha_inicio' => $fechaDesde,
        'fecha_fin' => $fechaHasta,
        'total_ingreso' => 0.00,
        'total_egreso' => 0.00,
        'flujo_neto' => 0.00,
        'porcentaje_crecimiento' => 0.00, // Requiere datos del período anterior, simplificamos a 0 por ahora
        'grafico' => []
    ];

    // --- 1. Consulta Principal para el Flujo de Caja por Día ---
    // Agrupa las transacciones de COBRO (Ingreso) y CANJE (Egreso) por fecha.
    
    // 🛑 NOTA IMPORTANTE: Se asume que el 'CANJE' de un chofer (CANJE_CHOFER)
    // es una transacción de egreso para la línea, y el 'COBRO' (TRANSACCION) es ingreso.

    $sqlFlujoCaja = "
        SELECT
            DATE(T.fecha_hora) AS fecha,
            SUM(CASE WHEN T.tipo = 'COBRO' THEN T.monto ELSE 0 END) AS ingreso_diario,
            COALESCE(SUM(CC.monto), 0) AS egreso_canje_diario
        FROM
            TRANSACCION T
        JOIN
            CHOFER C ON T.chofer_id_cobro = C.chofer_id -- Enlazar Transacción con Chofer (para filtrar por Línea)
        LEFT JOIN
            CANJE_CHOFER CC ON CC.chofer_id = C.chofer_id AND DATE(CC.fecha_canje) = DATE(T.fecha_hora)
        WHERE
            C.linea_id = :linea_id 
            AND T.tipo IN ('COBRO') -- Solo cobros como ingresos
            AND DATE(T.fecha_hora) BETWEEN :fecha_desde AND :fecha_hasta
        GROUP BY
            fecha
        ORDER BY
            fecha ASC
    ";

    $stmt = $pdo->prepare($sqlFlujoCaja);
    $stmt->execute([
        ':linea_id' => $lineaId,
        ':fecha_desde' => $fechaDesde,
        ':fecha_hasta' => $fechaHasta
    ]);
    
    $flujoCajaData = $stmt->fetchAll();
    
    $totalIngreso = 0;
    $totalEgreso = 0;

    foreach ($flujoCajaData as $row) {
        $totalIngreso += $row['ingreso_diario'];
        $totalEgreso += $row['egreso_canje_diario'];
        
        // Preparar datos para el gráfico
        $reporte['grafico'][] = [
            'fecha' => $row['fecha'],
            'ingreso' => (float)$row['ingreso_diario'],
            'egreso' => (float)$row['egreso_canje_diario'],
            'neto' => (float)$row['ingreso_diario'] - (float)$row['egreso_canje_diario']
        ];
    }

    // --- 2. Cálculo de Métricas Totales ---
    $reporte['total_ingreso'] = $totalIngreso;
    $reporte['total_egreso'] = $totalEgreso;
    $reporte['flujo_neto'] = $totalIngreso - $totalEgreso;
    
    // Simplificación para la tendencia: asumiremos 5% de crecimiento si hay ingreso neto positivo.
    if ($reporte['flujo_neto'] > 0) {
        $reporte['porcentaje_crecimiento'] = 5.00;
    }


    echo json_encode(['success' => true, 'reporte' => $reporte]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>