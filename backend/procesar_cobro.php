<?php
/**
 * DIGITAL TRANSPORT - PROCESAR COBRO (HMAC ESTRICTO & ANTI-FRAUDE)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado.']);
    exit;
}

$secret_key = getenv('QR_SECRET') ?: getenv('CSRF_SECRET');
if (empty($secret_key)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de configuración: Clave de firma QR no definida en el servidor.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$usuario_id = 0;
$chofer_id = 0;

if (isset($data['bus_payment']) && $data['bus_payment'] === true) {
    // MODO: PASAJERO ESCANEA AL BUS
    $usuario_id = (int)$_SESSION['usuario_id'];
    $chofer_id = (int)($data['chofer_id'] ?? 0);

    if ($chofer_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de chofer inválido.']);
        exit;
    }

    // Validar que el chofer exista y esté ACTIVO
    $stmtCh = $pdo->prepare("SELECT chofer_id, estado_servicio FROM CHOFER WHERE chofer_id = ?");
    $stmtCh->execute([$chofer_id]);
    $choferData = $stmtCh->fetch();

    if (!$choferData || strtoupper($choferData['estado_servicio'] ?? '') !== 'ACTIVO') {
        echo json_encode(['success' => false, 'error' => 'El chofer seleccionado no está activo en servicio.']);
        exit;
    }

    // Anti-doble pago: verificar si el pasajero ya le pagó al mismo chofer en los últimos 2 minutos
    $stmtCheckRecent = $pdo->prepare("
        SELECT COUNT(*) FROM TRANSACCION 
        WHERE usuario_id = ? AND chofer_id_cobro = ? AND tipo = 'COBRO' 
          AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $stmtCheckRecent->execute([$usuario_id, $chofer_id]);
    if ($stmtCheckRecent->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Ya registraste un pago a este chofer hace menos de 2 minutos.']);
        exit;
    }

} else {
    // MODO: CHOFER ESCANEA AL PASAJERO
    if ((int)($_SESSION['tipo_usuario_id'] ?? 0) !== 3) {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado. Solo choferes pueden cobrar.']);
        exit;
    }
    
    $qrData = trim($data['qrData'] ?? '');
    $jsonDecoded = json_decode($qrData, true);

    if (!is_array($jsonDecoded) || !isset($jsonDecoded['u'], $jsonDecoded['ts'], $jsonDecoded['sig'], $jsonDecoded['nonce'])) {
        echo json_encode(['success' => false, 'error' => 'Formato de QR no válido o inseguro.']);
        exit;
    }

    $usuario_id = (int)$jsonDecoded['u'];
    $ts = (int)$jsonDecoded['ts'];
    $sig = $jsonDecoded['sig'];
    $nonce = $jsonDecoded['nonce'];

    // 1. Expiración de Token QR (5 minutos)
    if (abs(time() - $ts) > 300) {
        echo json_encode(['success' => false, 'error' => 'Código QR expirado. Genera uno nuevo.']);
        exit;
    }

    // 2. Verificación de Firma HMAC
    $payloadToVerify = "{$usuario_id}:{$ts}:{$nonce}";
    $expectedSig = hash_hmac('sha256', $payloadToVerify, $secret_key);
    if (!hash_equals($expectedSig, $sig)) {
        echo json_encode(['success' => false, 'error' => 'Firma de seguridad del QR no válida.']);
        exit;
    }

    // 3. Anti-Replay por nonce
    if (isset($_SESSION['used_nonces'][$nonce])) {
        echo json_encode(['success' => false, 'error' => 'Código QR ya utilizado previamente (intento de doble cobro).']);
        exit;
    }
    $_SESSION['used_nonces'][$nonce] = time();

    // Obtener chofer autenticado
    $stmt = $pdo->prepare("SELECT chofer_id, estado_servicio FROM CHOFER WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $choferRow = $stmt->fetch();

    if (!$choferRow || strtoupper($choferRow['estado_servicio'] ?? '') !== 'ACTIVO') {
        echo json_encode(['success' => false, 'error' => 'Su cuenta de chofer no está activa para cobrar.']);
        exit;
    }
    $chofer_id = (int)$choferRow['chofer_id'];
}

if ($usuario_id <= 0 || $chofer_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Identificación de usuario o chofer fallida.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Detección de tarifa según perfil del usuario
    $stmt = $pdo->prepare("
        SELECT tipo_desc_id 
        FROM VALIDACION_ESPECIAL 
        WHERE usuario_id = ? AND estado_validacion = 'APROBADA' 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $tipo_desc_id = $stmt->fetchColumn() ?: 1;

    $stmt = $pdo->prepare("SELECT tarifa_id, monto FROM TARIFA WHERE tipo_desc_id = ? LIMIT 1");
    $stmt->execute([$tipo_desc_id]);
    $tarifa_oficial = $stmt->fetch();

    if (!$tarifa_oficial) {
        $stmt = $pdo->query("SELECT tarifa_id, monto FROM TARIFA WHERE tipo_desc_id = 1 LIMIT 1");
        $tarifa_oficial = $stmt->fetch();
    }

    $monto_a_cobrar = floatval($tarifa_oficial['monto']);

    $stmt = $pdo->prepare("SELECT nombre_completo, saldo FROM USUARIO WHERE usuario_id = ? FOR UPDATE");
    $stmt->execute([$usuario_id]);
    $pasajero = $stmt->fetch();

    if (!$pasajero) throw new Exception("Pasajero no encontrado.");
    if ($pasajero['saldo'] < $monto_a_cobrar) {
        throw new Exception("Saldo insuficiente. La tarifa es de Bs. " . number_format($monto_a_cobrar, 2));
    }

    $nuevo_saldo = $pasajero['saldo'] - $monto_a_cobrar;
    $stmt = $pdo->prepare("UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?");
    $stmt->execute([$nuevo_saldo, $usuario_id]);

    $stmt = $pdo->prepare("
        INSERT INTO TRANSACCION (tipo, monto, usuario_id, chofer_id_cobro, fecha_hora) 
        VALUES ('COBRO', ?, ?, ?, NOW())
    ");
    $stmt->execute([$monto_a_cobrar, $usuario_id, $chofer_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '¡Pago Procesado!',
        'pasajero' => explode(' ', $pasajero['nombre_completo'])[0],
        'monto' => number_format($monto_a_cobrar, 2),
        'tarifa' => ($tipo_desc_id == 2 ? 'Estudiante' : 'Estándar')
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error PDO en procesar_cobro: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos al procesar el cobro.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en procesar_cobro: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>