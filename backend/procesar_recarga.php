<?php
/**
 * DIGITAL TRANSPORT - PROCESAMIENTO SEGURO DE RECARGAS (SOLICITUD Y CONFIRMACIÓN SEPARADAS)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

function respond($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Método de petición no permitido.');
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    respond(false, 'Sesión no válida o expirada.');
}

$usuario_id = (int)$_SESSION['usuario_id'];
$user_role = (int)($_SESSION['tipo_usuario_id'] ?? 1);

// 1. Validar Token CSRF
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    http_response_code(403);
    respond(false, 'Token de seguridad inválido o expirado.');
}

// 2. Recepción de datos
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$payment_method = trim($_POST['payment_method'] ?? 'qr');
$referencia = trim($_POST['referencia_pago'] ?? '');

if ($amount === false || $amount <= 0 || $amount > 5000) {
    respond(false, 'Por favor, ingrese un monto de recarga válido (entre 1.00 y 5,000.00 Bs.).');
}

if (empty($referencia) || strlen($referencia) < 6) {
    respond(false, 'Debe proporcionar un número de referencia bancario/QR válido (mínimo 6 dígitos).');
}

try {
    $pdo->beginTransaction();

    // 3. Verificar anti-replay de la referencia
    $stmtRef = $pdo->prepare("SELECT u_recarga_id FROM U_RECARGA WHERE usuario_id = ? AND monto = ? AND fecha_recarga > (NOW() - INTERVAL 5 MINUTE)");
    $stmtRef->execute([$usuario_id, $amount]);
    if ($stmtRef->fetch()) {
        throw new Exception('Ya existe una solicitud de recarga reciente por el mismo monto. Intente en unos minutos.');
    }

    // 4. Registrar Solicitud en U_RECARGA con estado PENDIENTE y referencia
    $stmtUR = $pdo->prepare("INSERT INTO U_RECARGA (usuario_id, punto_id, tipo_recarga_id, monto, estado, referencia, fecha_recarga) VALUES (?, 1, 1, ?, 'PENDIENTE', ?, NOW())");
    $stmtUR->execute([$usuario_id, $amount, $referencia]);
    $recarga_id = $pdo->lastInsertId();

    // 5. Auto-confirmación RESTRENGIDA ÚNICAMENTE a entorno local de desarrollo (APP_ENV=local)
    $autoConfirmLocal = (getenv('APP_ENV') === 'local');

    if ($autoConfirmLocal) {
        $stmtUser = $pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ? FOR UPDATE");
        $stmtUser->execute([$usuario_id]);
        $current_balance = (float)$stmtUser->fetchColumn();

        $new_balance = $current_balance + $amount;

        $stmtUpdate = $pdo->prepare("UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?");
        $stmtUpdate->execute([$new_balance, $usuario_id]);

        $stmtUpdateUR = $pdo->prepare("UPDATE U_RECARGA SET estado = 'CONFIRMADA' WHERE u_recarga_id = ?");
        $stmtUpdateUR->execute([$recarga_id]);

        $stmtTx = $pdo->prepare("INSERT INTO TRANSACCION (usuario_id, tipo, monto, fecha_hora) VALUES (?, 'RECARGA', ?, NOW())");
        $stmtTx->execute([$usuario_id, $amount]);
        $transaccion_id = $pdo->lastInsertId();

        $pdo->commit();
        $_SESSION['saldo'] = $new_balance;

        respond(true, "✅ Recarga confirmada y acreditada exitosamente (Modo Local). Se han sumado Bs. " . number_format($amount, 2) . " (Ref: $referencia).", [
            'nuevo_saldo' => $new_balance,
            'transaccion_id' => $transaccion_id,
            'estado' => 'CONFIRMADA'
        ]);
    } else {
        // En producción: La recarga se registra y queda PENDIENTE de verificación por el Operador del Punto de Recarga / SuperAdmin
        $pdo->commit();

        respond(true, "⌛ Solicitud de recarga de Bs. " . number_format($amount, 2) . " registrada (Ref: $referencia). Pendiente de confirmación por el operador.", [
            'recarga_id' => $recarga_id,
            'estado' => 'PENDIENTE'
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en procesar_recarga: " . $e->getMessage());
    respond(false, 'Error en el procesamiento de la solicitud de recarga.');
}
?>
