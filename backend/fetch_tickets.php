<?php
// fetch_tickets.php

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

// 1. GESTIÓN DE SESIONES Y SEGURIDAD
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "Usuario no autenticado. Inicie sesión para ver los boletos."]);
    exit();
}

$current_user_id = (int)$_SESSION['usuario_id']; 

try {
    // 2. CONSULTA SQL
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
            AND T.estado != 'BLOQUEADA'
            AND T.estado != 'PERDIDA'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $raw_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. FORMATO DE DATOS
    $formatted_tickets = [];
    
    foreach ($raw_tickets as $ticket) {
        $saldo = (float)$ticket['saldo_actual'];
        $totalUses = 999; 
        $usedUses = 0;   
        
        $status = ($saldo <= 0) ? 'Usado' : 'Activo'; 

        $tipo_pase = 'Pase Saldo (Actual: ' . number_format($saldo, 2) . ' Bs)';
        $linea = 'Tarjeta de Saldo General';
        $expiration_date = '2099-12-31T23:59:00'; 

        $formatted_tickets[] = [
            'id' => 'TRJ-' . $ticket['tarjeta_id'],
            'line' => $linea,
            'type' => $tipo_pase, 
            'expires' => $expiration_date,
            'totalUses' => $totalUses,
            'usedUses' => $usedUses,
            'status' => $status,
            'qrData' => 'DT-TRJ-' . $ticket['tarjeta_id'] . '-' . $ticket['codigo_nfc'] 
        ];
    }
    
    echo json_encode($formatted_tickets);

} catch (PDOException $e) {
    http_response_code(500); 
    echo json_encode(["error" => "Error al obtener los boletos de la base de datos."]);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de procesamiento interno."]);
    exit();
}
?>