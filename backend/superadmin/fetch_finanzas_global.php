<?php
/**
 * DIGITAL TRANSPORT - FETCH FINANZAS GLOBAL (SUPER ADMIN)
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
    $stats = [];

    // 1. Totales Generales
    $sqlGral = "SELECT SUM(monto) as total, COUNT(*) as boletos FROM TRANSACCION WHERE tipo = 'COBRO'";
    $resGral = $pdo->query($sqlGral)->fetch();
    $stats['total_general'] = $resGral['total'] ?? 0.00;
    $stats['total_boletos'] = $resGral['boletos'] ?? 0;

    // 2. Detalle por Línea
    $sqlLineas = "
        SELECT 
            L.nombre,
            COUNT(T.transaccion_id) as boletos,
            COALESCE(SUM(T.monto), 0) as recaudacion
        FROM 
            LINEA L
        LEFT JOIN 
            CHOFER C ON L.linea_id = C.linea_id
        LEFT JOIN 
            TRANSACCION T ON C.chofer_id = T.chofer_id_cobro AND T.tipo = 'COBRO'
        GROUP BY 
            L.linea_id, L.nombre
        ORDER BY 
            recaudacion DESC
    ";
    $stats['detalle_lineas'] = $pdo->query($sqlLineas)->fetchAll(PDO::FETCH_ASSOC);
    $stats['linea_lider'] = !empty($stats['detalle_lineas']) ? $stats['detalle_lineas'][0]['nombre'] : '---';

    // 3. Pasajeros Activos
    $sqlPasajeros = "SELECT COUNT(*) FROM USUARIO WHERE tipo_usuario_id = 1";
    $stats['total_pasajeros'] = $pdo->query($sqlPasajeros)->fetchColumn();

    // 4. Distribución por Tarifa (Basado en el monto cobrado)
    // Asumiendo montos fijos para simplificar el gráfico
    $sqlTarifas = "
        SELECT 
            SUM(CASE WHEN monto = 2.50 THEN 1 ELSE 0 END) as estandar,
            SUM(CASE WHEN monto = 1.00 THEN 1 ELSE 0 END) as reducida
        FROM 
            TRANSACCION 
        WHERE 
            tipo = 'COBRO'
    ";
    $resTarifas = $pdo->query($sqlTarifas)->fetch();
    $stats['tarifas'] = [
        'estandar' => (int)$resTarifas['estandar'],
        'estudiante' => (int)$resTarifas['reducida'], // Compartido por ahora
        'tercera_edad' => 0 // Lógica para separar si se desea
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
