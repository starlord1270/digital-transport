<?php
// backend/fetch_choferes_data.php
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
// Asume que tienes un archivo bd.php o define tus credenciales aquí.
// Si usas bd.php, reemplaza las siguientes líneas con 'require_once 'bd.php';'
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 
// ----------------------------------------


// Función para enviar respuesta JSON
function sendResponse($success, $data = [], $error = null) {
    echo json_encode(['success' => $success, 'choferes' => $data, 'error' => $error]);
    exit;
}

// 1. Validar la entrada (linea_id)
$lineaId = isset($_GET['linea_id']) ? (int)$_GET['linea_id'] : 0;

if ($lineaId === 0) {
    http_response_code(400);
    sendResponse(false, [], "ID de línea inválido o no proporcionado (ID 0).");
}

try {
    // 2. Conexión usando PDO (según tu código)
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 3. Consulta SQL corregida
    $sql = "
        SELECT 
            C.chofer_id,
            U.nombre_completo,
            C.vehiculo_placa,
            C.estado_servicio,
            -- Boletos cobrados HOY
            COALESCE(SUM(CASE WHEN T.tipo = 'COBRO' AND DATE(T.fecha_hora) = CURDATE() THEN 1 ELSE 0 END), 0) AS boletos_cobrados_hoy,
            -- Monto Total Pendiente de Canje (Suma de COBROS - Suma de CANJES)
            COALESCE(SUM(CASE WHEN T.tipo = 'COBRO' THEN T.monto ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN T.tipo = 'CANJE' THEN T.monto ELSE 0 END), 0) 
            AS monto_a_canjear
        FROM 
            CHOFER C
        JOIN 
            USUARIO U ON C.usuario_id = U.usuario_id
        LEFT JOIN 
            TRANSACCION T ON C.chofer_id = T.chofer_id_cobro
        WHERE 
            C.linea_id = :linea_id 
        GROUP BY 
            C.chofer_id, U.nombre_completo, C.vehiculo_placa, C.estado_servicio
        ORDER BY 
            C.estado_servicio DESC, U.nombre_completo ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':linea_id' => $lineaId]);
    $choferesData = $stmt->fetchAll();

    // 4. Devolver resultados
    sendResponse(true, $choferesData);

} catch (PDOException $e) {
    // 5. Manejo de errores de BD
    http_response_code(500);
    sendResponse(false, [], 'Error de BD: ' . $e->getMessage());
}
?>