<?php
// fetch_history.php

header('Content-Type: application/json');

// --- 1. CONFIGURACIÓN DE LA BASE DE DATOS ---
// ⚠️ AJUSTA estos valores a tu configuración real ⚠️
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

// 🛑 SIMULACIÓN DE USUARIO LOGUEADO 🛑
$current_user_id = 1; 

// Fecha límite para el historial (últimos 30 días)
$date_limit = date('Y-m-d', strtotime('-30 days'));

try {
    // 2. CONEXIÓN USANDO PDO
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 3. CONSULTA SQL PARA OBTENER EL HISTORIAL DE VIAJES (TRANSACCIONES DE USO) ---
    $sql = "
        SELECT
            T.transaccion_id,
            T.monto,
            T.fecha_hora,
            T.tarjeta_id,
            -- Asumimos campos genéricos para línea y ruta ya que no están en TRANSACCION
            'Línea de Servicio' AS nombre_linea, 
            'Estación Origen/Destino' AS ruta_detalle
        FROM 
            TRANSACCION T
        JOIN 
            TARJETA TA ON T.tarjeta_id = TA.tarjeta_id
        WHERE 
            TA.usuario_id = :user_id 
            AND T.tipo_movimiento = 'Uso' -- Filtra solo los viajes/usos
            AND T.fecha_hora >= :date_limit
        ORDER BY 
            T.fecha_hora DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':date_limit', $date_limit);
    $stmt->execute();
    $raw_history = $stmt->fetchAll();

    // --- 4. FORMATO DE DATOS PARA EL FRONTEND ---
    $formatted_history = [];
    $total_gasto = 0.0;
    
    foreach ($raw_history as $item) {
        $monto = (float)$item['monto'];
        $total_gasto += $monto;
        
        $date_time = new DateTime($item['fecha_hora']);

        $formatted_history[] = [
            'id' => $item['transaccion_id'],
            'date' => $date_time->format('Y-m-d'), // Para agrupar
            'time' => $date_time->format('H:i A'),
            'line' => $item['nombre_linea'],
            'details' => $item['ruta_detalle'],
            'amount' => $monto,
            'status' => 'Completado'
        ];
    }
    
    // 5. DEVOLVER JSON, incluyendo estadísticas
    echo json_encode([
        'total_trips' => count($formatted_history),
        'total_spent' => number_format($total_gasto, 2, '.', ''),
        'history' => $formatted_history
    ]);

} catch (PDOException $e) {
    // Manejo de errores de conexión o consulta
    http_response_code(500);
    echo json_encode(["error" => "Error de BD: SQLSTATE " . $e->getCode() . " - " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de procesamiento: " . $e->getMessage()]);
}
?>