<?php
// fetch_tickets.php

// Establece el encabezado para que el navegador sepa que la respuesta es JSON
header('Content-Type: application/json');

// --- 1. CONFIGURACIÓN DE LA BASE DE DATOS ---
// ⚠️ AJUSTA estos valores ⚠️
$dbHost = 'localhost';
$dbName = 'digital-transport'; // Nombre de BD confirmado
$dbUser = 'root'; 
$dbPass = ''; 

// 🛑 SIMULACIÓN DE USUARIO LOGUEADO 🛑
$current_user_id = 1; 

try {
    // 2. CONEXIÓN USANDO PDO
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 3. CONSULTA SQL (Solo TARJETA, sin JOINs) ---
    // Usamos SELECT explícito para evitar problemas futuros si se añaden más columnas.
    $sql = "
        SELECT
            T.tarjeta_id,
            T.saldo_actual,
            T.estado,
            T.codigo_nfc
        FROM 
            TARJETA T
        WHERE 
            T.usuario_id = :user_id 
            AND T.estado != 'Inactivo'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $raw_tickets = $stmt->fetchAll();

    // --- 4. FORMATO DE DATOS PARA EL FRONTEND ---
    $formatted_tickets = [];
    
    foreach ($raw_tickets as $ticket) {
        $saldo = (float)$ticket['saldo_actual'];
        $totalUses = 999; // Representa que el uso es ilimitado hasta agotar el saldo
        $usedUses = 0;   
        
        // Determinar el estado
        $status = 'Activo'; 
        if ($saldo <= 0) {
            $status = 'Usado';
        }

        // Determinar el nombre del pase basándonos en el saldo
        $tipo_pase = 'Pase Saldo (Actual: ' . number_format($saldo, 2) . ' Bs)';
        
        // Determinamos la línea y fecha de expiración por defecto ya que no existen en la BD
        $linea = 'Línea de Transporte Estándar';
        $expiration_date = '2099-12-31T23:59:00'; 

        $formatted_tickets[] = [
            'id' => 'TRJ-' . $ticket['tarjeta_id'],
            'line' => $linea,
            'type' => $tipo_pase, 
            'expires' => $expiration_date,
            'totalUses' => $totalUses,
            'usedUses' => $usedUses,
            'status' => $status,
            'qrData' => 'DT-TRJ-' . $ticket['tarjeta_id'] . '-' . $ticket['codigo_nfc'] // Código NFC para QR
        ];
    }
    
    // 5. DEVOLVER JSON
    echo json_encode($formatted_tickets);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de BD: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de Procesamiento: " . $e->getMessage()]);
}
?>