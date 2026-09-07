<?php
/**
 * DIGITAL TRANSPORT - SOLICITAR CANJE (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 3) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

try {
    // 1. Obtener chofer_id
    $stmt = $pdo->prepare("SELECT chofer_id FROM CHOFER WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $chofer_id = $stmt->fetchColumn();

    // 2. Verificar si ya hay una solicitud pendiente
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM CANJE_CHOFER WHERE chofer_id = ? AND estado = 'PENDIENTE'");
    $stmt->execute([$chofer_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud de canje en proceso.']);
        exit;
    }

    // 3. Calcular saldo pendiente actual
    // Ingresos
    $stmt = $pdo->prepare("SELECT SUM(monto) FROM TRANSACCION WHERE chofer_id_cobro = ? AND tipo = 'COBRO'");
    $stmt->execute([$chofer_id]);
    $total_cobros = $stmt->fetchColumn() ?: 0;

    // Egresos (Pagados)
    $stmt = $pdo->prepare("SELECT SUM(monto) FROM CANJE_CHOFER WHERE chofer_id = ? AND estado = 'PAGADO'");
    $stmt->execute([$chofer_id]);
    $total_pagado = $stmt->fetchColumn() ?: 0;
    
    $saldo_pendiente = $total_cobros - $total_pagado;

    if ($saldo_pendiente <= 0) {
        echo json_encode(['success' => false, 'message' => 'No tienes saldo acumulado para liquidar.']);
        exit;
    }

    // 4. Crear la solicitud
    $stmt = $pdo->prepare("INSERT INTO CANJE_CHOFER (chofer_id, monto, fecha_canje, estado) VALUES (?, ?, NOW(), 'PENDIENTE')");
    $stmt->execute([$chofer_id, $saldo_pendiente]);

    echo json_encode(['success' => true, 'message' => "Solicitud de liquidación por Bs. " . number_format($saldo_pendiente, 2) . " enviada correctamente."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>
