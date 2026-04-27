<?php
/**
 * DIGITAL TRANSPORT - CHECK NEW PAYMENTS (POLLING)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

$chofer_id = (int)($_GET['chofer_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if ($chofer_id === 0) {
    echo json_encode(['success' => false, 'error' => 'ID de chofer inválido.']);
    exit;
}

try {
    // Buscar transacciones nuevas para este chofer
    $sql = "
        SELECT 
            T.transaccion_id,
            U.nombre_completo as pasajero,
            T.monto,
            DATE_FORMAT(T.fecha_hora, '%H:%i:%s') as hora
        FROM 
            TRANSACCION T
        JOIN 
            USUARIO U ON T.usuario_id = U.usuario_id
        WHERE 
            T.chofer_id_cobro = ? 
            AND T.tipo = 'COBRO'
            AND T.transaccion_id > ?
            AND T.fecha_hora >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ORDER BY 
            T.transaccion_id DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$chofer_id, $last_id]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'nuevos_pagos' => $pagos
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
