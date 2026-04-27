<?php
/**
 * DIGITAL TRANSPORT - PROCESAR CANJE (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Verificación de Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$canje_id = (int)($_POST['canje_id'] ?? 0);

if ($canje_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de canje inválido.']);
    exit;
}

try {
    // 1. Verificar existencia y pertenencia
    $stmt = $pdo->prepare("
        SELECT CC.canje_id, C.linea_id, CC.monto 
        FROM CANJE_CHOFER CC 
        JOIN CHOFER C ON CC.chofer_id = C.chofer_id 
        WHERE CC.canje_id = ?
    ");
    $stmt->execute([$canje_id]);
    $canje = $stmt->fetch();

    if (!$canje || $canje['linea_id'] != $_SESSION['linea_id']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para procesar este canje.']);
        exit;
    }

    // 2. Actualizar a PAGADO
    $stmt = $pdo->prepare("UPDATE CANJE_CHOFER SET estado = 'PAGADO' WHERE canje_id = ?");
    $stmt->execute([$canje_id]);

    echo json_encode(['success' => true, 'message' => "Liquidación de Bs. " . number_format($canje['monto'], 2) . " marcada como PAGADA."]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
}
?>
