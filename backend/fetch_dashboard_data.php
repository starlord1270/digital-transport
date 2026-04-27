<?php
/**
 * DIGITAL TRANSPORT - FETCH DASHBOARD DATA (ADMIN LÍNEA) - PDO
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// 1. Verificación de permisos
$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : ($_SESSION['linea_id'] ?? 0);

if ($lineaId === 0 || !isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

try {
    $results = [];

    // 1. Total Recaudado Hoy y Boletos Pendientes
    $sqlRecaudado = "
        SELECT 
            SUM(T.monto) AS total_recaudado,
            COUNT(T.transaccion_id) AS boletos_pendientes
        FROM 
            TRANSACCION T
        JOIN 
            CHOFER C ON T.chofer_id_cobro = C.chofer_id
        WHERE 
            T.tipo = 'COBRO' 
            AND C.linea_id = ? 
            AND DATE(T.fecha_hora) = CURDATE()
    ";
    $stmt = $pdo->prepare($sqlRecaudado);
    $stmt->execute([$lineaId]);
    $recaudadoData = $stmt->fetch();

    $results['total_recaudado'] = $recaudadoData['total_recaudado'] ?? 0.00;
    $results['boletos_pendientes'] = $recaudadoData['boletos_pendientes'] ?? 0;
    
    // 2. Choferes Activos
    $sqlChoferesActivos = "
        SELECT 
            COUNT(DISTINCT C.chofer_id) AS choferes_activos
        FROM 
            CHOFER C
        JOIN 
            TRANSACCION T ON T.chofer_id_cobro = C.chofer_id
        WHERE 
            C.linea_id = ? 
            AND T.tipo = 'COBRO'
            AND DATE(T.fecha_hora) = CURDATE()
    ";
    $stmt = $pdo->prepare($sqlChoferesActivos);
    $stmt->execute([$lineaId]);
    $results['choferes_activos'] = $stmt->fetchColumn() ?? 0;

    // 2.5 Choferes Pendientes de Validación
    $sqlPendientes = "SELECT COUNT(*) FROM CHOFER WHERE linea_id = ? AND estado_servicio = 'PENDIENTE'";
    $stmt = $pdo->prepare($sqlPendientes);
    $stmt->execute([$lineaId]);
    $results['choferes_pendientes_count'] = $stmt->fetchColumn() ?? 0;

    // 2.7 Solicitudes de Canje/Liquidación Pendientes
    $sqlCanjes = "
        SELECT 
            CC.canje_id,
            U.nombre_completo,
            CC.monto,
            DATE_FORMAT(CC.fecha_canje, '%d/%m %H:%i') as fecha
        FROM 
            CANJE_CHOFER CC
        JOIN 
            CHOFER C ON CC.chofer_id = C.chofer_id
        JOIN 
            USUARIO U ON C.usuario_id = U.usuario_id
        WHERE 
            C.linea_id = ? AND CC.estado = 'PENDIENTE'
        ORDER BY 
            CC.fecha_canje ASC
    ";
    $stmt = $pdo->prepare($sqlCanjes);
    $stmt->execute([$lineaId]);
    $results['canjes_pendientes'] = $stmt->fetchAll();
    $results['canjes_pendientes_count'] = count($results['canjes_pendientes']);

    // 3. Resumen de Choferes
    $sqlResumenChoferes = "
        SELECT 
            U.nombre_completo,
            COUNT(T.transaccion_id) AS boletos_cobrados,
            COALESCE(SUM(T.monto), 0) AS monto_canje
        FROM 
            CHOFER C
        JOIN 
            USUARIO U ON C.usuario_id = U.usuario_id
        LEFT JOIN 
            TRANSACCION T ON C.chofer_id = T.chofer_id_cobro AND T.tipo = 'COBRO' AND DATE(T.fecha_hora) = CURDATE()
        WHERE 
            C.linea_id = ? 
        GROUP BY 
            U.nombre_completo
        ORDER BY 
            monto_canje DESC
    ";
    $stmt = $pdo->prepare($sqlResumenChoferes);
    $stmt->execute([$lineaId]);
    $results['resumen_choferes'] = $stmt->fetchAll();
    
    // 4. Validaciones Pendientes
    $sqlValidaciones = "
        SELECT 
            V.validacion_id,
            U.nombre_completo,
            TD.nombre AS tipo_descuento,
            V.comprobante_url,
            DATE_FORMAT(V.fecha_solicitud, '%d-%m-%Y %H:%i') AS fecha_solicitud
        FROM 
            VALIDACION_ESPECIAL V
        JOIN 
            USUARIO U ON V.usuario_id = U.usuario_id
        JOIN 
            TIPO_DESCUENTO TD ON V.tipo_desc_id = TD.tipo_desc_id
        WHERE 
            V.estado_validacion = 'PENDIENTE'
        ORDER BY 
            V.fecha_solicitud ASC
        LIMIT 5
    ";
    $stmt = $pdo->query($sqlValidaciones);
    $results['validaciones_pendientes'] = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $results]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>