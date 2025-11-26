<?php
// fetch_dashboard_data.php
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

// 🛑 Obtener el ID de la línea desde la solicitud (Debería venir de la sesión del administrador) 🛑
$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : 0;

if ($lineaId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de línea no proporcionado.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $results = [];

    // 1. Total Recaudado Hoy y Boletos Pendientes
    // Se asume que el cobro (TRANSACCION.tipo = 'COBRO') usa el chofer_id, y que el canje se registra en CANJE_CHOFER
    // Recaudación y boletos pendientes se calculan con las transacciones de hoy que NO han sido canjeadas.
    // NOTA: Para un cálculo preciso, necesitarías una columna en TRANSACCION o un estado para marcar si fue canjeada.
    // Aquí, lo calcularemos con la información disponible.
    
    // Consulta para Total Recaudado (de todos los cobros de la línea hoy)
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
            AND C.linea_id = :linea_id 
            AND DATE(T.fecha_hora) = CURDATE()
            -- AND T.estado_canje = 'PENDIENTE' (Necesitarías esta columna para precisión)
    ";
    $stmt = $pdo->prepare($sqlRecaudado);
    $stmt->execute([':linea_id' => $lineaId]);
    $recaudadoData = $stmt->fetch();

    $results['total_recaudado'] = $recaudadoData['total_recaudado'] ?? 0.00;
    $results['boletos_pendientes'] = $recaudadoData['boletos_pendientes'] ?? 0;
    
    // 2. Choferes Activos (Basado en si tuvieron alguna transacción de COBRO hoy)
    $sqlChoferesActivos = "
        SELECT 
            COUNT(DISTINCT C.chofer_id) AS choferes_activos
        FROM 
            CHOFER C
        JOIN 
            TRANSACCION T ON T.chofer_id_cobro = C.chofer_id
        WHERE 
            C.linea_id = :linea_id 
            AND T.tipo = 'COBRO'
            AND DATE(T.fecha_hora) = CURDATE()
    ";
    $stmt = $pdo->prepare($sqlChoferesActivos);
    $stmt->execute([':linea_id' => $lineaId]);
    $results['choferes_activos'] = $stmt->fetchColumn() ?? 0;

    // 3. Resumen de Choferes (Boletos cobrados y Monto a canjear hoy)
    $sqlResumenChoferes = "
        SELECT 
            U.nombre_completo,
            COUNT(T.transaccion_id) AS boletos_cobrados,
            SUM(T.monto) AS monto_canje
        FROM 
            CHOFER C
        JOIN 
            USUARIO U ON C.usuario_id = U.usuario_id
        LEFT JOIN 
            TRANSACCION T ON C.chofer_id = T.chofer_id_cobro AND T.tipo = 'COBRO' AND DATE(T.fecha_hora) = CURDATE()
        WHERE 
            C.linea_id = :linea_id 
        GROUP BY 
            U.nombre_completo
        ORDER BY 
            monto_canje DESC
    ";
    $stmt = $pdo->prepare($sqlResumenChoferes);
    $stmt->execute([':linea_id' => $lineaId]);
    $results['resumen_choferes'] = $stmt->fetchAll();
    
    // 4. Validaciones Pendientes
    $sqlValidaciones = "
        SELECT 
            U.nombre_completo,
            TD.nombre AS tipo_descuento,
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
    // Nota: Las validaciones no están ligadas a una línea directamente, por lo que 
    // mostramos todas las pendientes. En una versión más compleja, solo se mostrarían
    // aquellas que el Admin de Línea tiene permiso de gestionar.
    $stmt = $pdo->query($sqlValidaciones);
    $results['validaciones_pendientes'] = $stmt->fetchAll();


    echo json_encode(['success' => true, 'data' => $results]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>