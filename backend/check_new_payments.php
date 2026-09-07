<?php
/**
 * DIGITAL TRANSPORT - CHECK NEW PAYMENTS (POLLING)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$last_id = (int)($_GET['last_id'] ?? 0);

try {
    // Look up chofer_id for current authenticated session user
    $stmtChofer = $pdo->prepare("SELECT chofer_id FROM CHOFER WHERE usuario_id = ?");
    $stmtChofer->execute([$usuario_id]);
    $chofer_id = (int)$stmtChofer->fetchColumn();

    if ($chofer_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Chofer no encontrado.']);
        exit;
    }

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
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
?>
