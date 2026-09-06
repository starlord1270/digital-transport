<?php
/**
 * DIGITAL TRANSPORT - RECAUDACIÓN CHOFER (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    // 1. Obtener chofer_id
    $stmt = $pdo->prepare("SELECT chofer_id FROM CHOFER WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $chofer_id = $stmt->fetchColumn();

    // 2. Sumar recaudación de hoy
    $stmt = $pdo->prepare("SELECT SUM(monto) as total FROM TRANSACCION WHERE chofer_id_cobro = ? AND DATE(fecha_hora) = CURDATE() AND tipo = 'COBRO'");
    $stmt->execute([$chofer_id]);
    $total_hoy = $stmt->fetchColumn() ?: 0;

    // 3. Saldo Total Pendiente (Todo lo cobrado - Todo lo liquidado/pagado)
    // Ingresos (Cobros)
    $stmt = $pdo->prepare("SELECT SUM(monto) FROM TRANSACCION WHERE chofer_id_cobro = ? AND tipo = 'COBRO'");
    $stmt->execute([$chofer_id]);
    $total_cobros = $stmt->fetchColumn() ?: 0;

    // Egresos (Liquidaciones pagadas)
    $stmt = $pdo->prepare("SELECT SUM(monto) FROM CANJE_CHOFER WHERE chofer_id = ? AND estado = 'PAGADO'");
    $stmt->execute([$chofer_id]);
    $total_pagado = $stmt->fetchColumn() ?: 0;
    
    $saldo_pendiente = $total_cobros - $total_pagado;

    // 4. ¿Hay un canje pendiente de aprobación?
    $stmt = $pdo->prepare("SELECT monto FROM CANJE_CHOFER WHERE chofer_id = ? AND estado = 'PENDIENTE' ORDER BY fecha_canje DESC LIMIT 1");
    $stmt->execute([$chofer_id]);
    $canje_pendiente = $stmt->fetchColumn() ?: 0;

    // 5. Desglose hoy
    $stmt = $pdo->prepare("SELECT monto, COUNT(*) as cantidad FROM TRANSACCION WHERE chofer_id_cobro = ? AND DATE(fecha_hora) = CURDATE() AND tipo = 'COBRO' GROUP BY monto");
    $stmt->execute([$chofer_id]);
    $desglose = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_recaudado' => $total_hoy,
        'saldo_pendiente' => $saldo_pendiente,
        'canje_en_proceso' => $canje_pendiente,
        'desglose' => $desglose
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
