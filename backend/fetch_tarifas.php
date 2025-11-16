<?php
// fetch_tarifas.php
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; // Asegúrate de que esta sea tu contraseña si tienes una, o déjala vacía si no.

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 🛑 CONSULTA FINAL: Hace JOIN con TIPO_DESCUENTO para obtener el nombre 🛑
    $sql = "
        SELECT 
            t.tarifa_id, 
            td.nombre AS nombre,     
            t.monto_bs AS costo,           
            td.nombre AS tipo_pasajero     
        FROM 
            TARIFA t
        JOIN
            TIPO_DESCUENTO td ON t.tipo_desc_id = td.tipo_desc_id
        ORDER BY 
            t.monto_bs DESC
    ";

    $stmt = $pdo->query($sql);
    $tarifas = $stmt->fetchAll();

    echo json_encode(['success' => true, 'tarifas' => $tarifas]);

} catch (PDOException $e) {
    // Si la conexión falla, se devuelve este JSON de error.
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
}
?>