<?php
/**
 * DIGITAL TRANSPORT - PROCESAR CANJE (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

// Verificación de Admin (4 Admin Línea o 5 SuperAdmin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [4, 5], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

// CSRF Protection
if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$canje_id = (int)($_POST['canje_id'] ?? 0);
$userRole = (int)$_SESSION['tipo_usuario_id'];
$usuarioId = (int)$_SESSION['usuario_id'];

if ($canje_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de canje inválido.']);
    exit;
}

try {
    // 1. Obtener línea administrada desde BD para Admin de Línea
    $adminLineaId = 0;
    if ($userRole === 4) {
        $stmtAdmin = $pdo->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
        $stmtAdmin->execute([$usuarioId]);
        $adminLineaId = (int)$stmtAdmin->fetchColumn();
    }

    // 2. Verificar existencia y pertenencia
    $stmt = $pdo->prepare("
        SELECT CC.canje_id, C.linea_id, CC.monto 
        FROM CANJE_CHOFER CC 
        JOIN CHOFER C ON CC.chofer_id = C.chofer_id 
        WHERE CC.canje_id = ?
    ");
    $stmt->execute([$canje_id]);
    $canje = $stmt->fetch();

    if (!$canje || ($userRole === 4 && (int)$canje['linea_id'] !== $adminLineaId)) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para procesar este canje.']);
        exit;
    }

    // 3. Actualizar a PAGADO
    $stmt = $pdo->prepare("UPDATE CANJE_CHOFER SET estado = 'PAGADO' WHERE canje_id = ?");
    $stmt->execute([$canje_id]);

    echo json_encode(['success' => true, 'message' => "Liquidación de Bs. " . number_format($canje['monto'], 2) . " marcada como PAGADA."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>
