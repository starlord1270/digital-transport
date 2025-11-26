<?php
// fetch_history.php

// 🛑 CONFIGURACIÓN DE ERRORES: Muestra todos los errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpieza de buffer y gestión de sesión
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_end_clean(); 

header('Content-Type: application/json');

// --- 1. VERIFICACIÓN DE SESIÓN (CRÍTICO) ---
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "TEST 401: Sesión no activa o expirada."]);
    exit();
}
$current_user_id = $_SESSION['usuario_id']; 


// --- 2. CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

// Fecha límite para el historial (últimos 30 días)
$date_limit = date('Y-m-d H:i:s', strtotime('-30 days'));

try {
    // 3. CONEXIÓN USANDO PDO
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 4. CONSULTA SQL CORREGIDA ---
    // CORRECCIÓN: Usamos T.tipo = 'COBRO' ya que T.tipo_movimiento = 'Uso' no existe.
    // NOTA: Para obtener la Línea real, necesitarías JOINs adicionales con CHOFER y LINEA.
    $sql = "
        SELECT
            T.transaccion_id,
            T.monto,
            T.fecha_hora,
            T.tarjeta_id,
            'Línea de Servicio' AS nombre_linea, 
            'Cobro por Viaje' AS ruta_detalle
        FROM 
            TRANSACCION T
        JOIN 
            TARJETA TA ON T.tarjeta_id = TA.tarjeta_id
        WHERE 
            TA.usuario_id = :user_id 
            AND T.tipo = 'COBRO' 
            AND T.fecha_hora >= :date_limit
        ORDER BY 
            T.fecha_hora DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':date_limit', $date_limit);
    $stmt->execute();
    $raw_history = $stmt->fetchAll();

    // --- 5. FORMATO DE DATOS PARA EL FRONTEND ---
    $formatted_history = [];
    $total_gasto = 0.0;
    
    foreach ($raw_history as $item) {
        $monto = (float)$item['monto'];
        $total_gasto += $monto;
        
        // Usar DateTime::createFromFormat si el formato de BD no es estándar, 
        // pero asumiremos que fecha_hora es estándar DATETIME.
        $date_time = new DateTime($item['fecha_hora']);

        $formatted_history[] = [
            'id' => $item['transaccion_id'],
            'date' => $date_time->format('Y-m-d'), 
            'time' => $date_time->format('H:i A'),
            'line' => $item['nombre_linea'],
            'details' => $item['ruta_detalle'] . ' (Tarjeta ID: ' . $item['tarjeta_id'] . ')',
            'amount' => $monto,
            'status' => 'Completado'
        ];
    }
    
    // 6. DEVOLVER JSON
    echo json_encode([
        'total_trips' => count($formatted_history),
        'total_spent' => number_format($total_gasto, 2, '.', ''),
        'history' => $formatted_history
    ]);

} catch (PDOException $e) {
    // Manejo de errores de conexión o consulta
    http_response_code(500);
    echo json_encode([
        "error" => "Error de BD: SQLSTATE " . $e->getCode() . " - " . $e->getMessage(),
        "hint" => "Verifique el estado de la tabla TRANSACCION y las columnas referenciadas (tarjeta_id, tipo)."
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de procesamiento: " . $e->getMessage()]);
    exit();
}
?>