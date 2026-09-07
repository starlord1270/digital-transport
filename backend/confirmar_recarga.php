<?php
/**
 * DIGITAL TRANSPORT - CONFIRMAR RECARGA DE SALDO (PUNTO RECARGA / SUPERADMIN)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [2, 5], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Solo operadores de Punto de Recarga o SuperAdmin pueden confirmar recargas.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente.']);
    exit;
}

$recarga_id = (int)($_POST['recarga_id'] ?? 0);
$accion = strtoupper(trim($_POST['accion'] ?? 'CONFIRMAR'));

if ($recarga_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de recarga inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener y bloquear registro de solicitud en U_RECARGA
    $stmtR = $pdo->prepare("SELECT usuario_id, monto, estado, punto_id FROM U_RECARGA WHERE u_recarga_id = ? FOR UPDATE");
    $stmtR->execute([$recarga_id]);
    $recData = $stmtR->fetch(PDO::FETCH_ASSOC);

    if (!$recData) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Solicitud de recarga no encontrada.']);
        exit;
    }

    $usuario_id_objetivo = (int)$recData['usuario_id'];
    $monto = (float)$recData['monto'];
    $estado_actual = $recData['estado'];

    // Restricción por punto de recarga: los operadores (rol 2) solo pueden
    // confirmar/rechazar solicitudes de SU propio punto.
    if ((int)$_SESSION['tipo_usuario_id'] === 2) {
        $stmtOp = $pdo->prepare("SELECT punto_id FROM PUNTO_RECARGA WHERE usuario_id = ?");
        $stmtOp->execute([(int)$_SESSION['usuario_id']]);
        $opPuntoId = (int)$stmtOp->fetchColumn();
        if ($opPuntoId <= 0 || (int)$recData['punto_id'] !== $opPuntoId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso denegado: esta solicitud pertenece a otro punto de recarga.']);
            exit;
        }
    }

    // Prevenir auto-confirmación por el mismo usuario si es operador
    if ((int)$_SESSION['usuario_id'] === $usuario_id_objetivo && (int)$_SESSION['tipo_usuario_id'] !== 5) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Un operador no puede auto-confirmarse sus propias recargas.']);
        exit;
    }

    // Idempotencia: Verificar estado actual
    if ($estado_actual === 'CONFIRMADA') {
        $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'La recarga ya se encuentra confirmada previamente.',
            'estado' => 'CONFIRMADA'
        ]);
        exit;
    }

    if ($estado_actual === 'RECHAZADA') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'La solicitud de recarga ya fue rechazada.',
            'estado' => 'RECHAZADA'
        ]);
        exit;
    }

    if ($accion === 'RECHAZAR') {
        $stmtUpdateUR = $pdo->prepare("UPDATE U_RECARGA SET estado = 'RECHAZADA' WHERE u_recarga_id = ?");
        $stmtUpdateUR->execute([$recarga_id]);

        registrarAuditoria($pdo, $_SESSION['usuario_id'], 'RECHAZAR_RECARGA', "Se rechazó la recarga #$recarga_id del usuario #$usuario_id_objetivo por Bs. $monto");
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Solicitud de recarga rechazada.']);
        exit;
    }

    // 2. Accion CONFIRMAR -> Acreditar saldo en USUARIO
    $stmtUser = $pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ? FOR UPDATE");
    $stmtUser->execute([$usuario_id_objetivo]);
    $current_balance = $stmtUser->fetchColumn();

    if ($current_balance === false) {
        throw new Exception("Usuario no encontrado.");
    }

    $new_balance = (float)$current_balance + $monto;

    $stmtUpdate = $pdo->prepare("UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?");
    $stmtUpdate->execute([$new_balance, $usuario_id_objetivo]);

    // 3. Actualizar estado de U_RECARGA a CONFIRMADA
    $stmtUpdateUR = $pdo->prepare("UPDATE U_RECARGA SET estado = 'CONFIRMADA' WHERE u_recarga_id = ?");
    $stmtUpdateUR->execute([$recarga_id]);

    // 4. Registrar Transacción oficial
    $stmtTx = $pdo->prepare("INSERT INTO TRANSACCION (usuario_id, tipo, monto, fecha_hora) VALUES (?, 'RECARGA', ?, NOW())");
    $stmtTx->execute([$usuario_id_objetivo, $monto]);
    $transaccion_id = $pdo->lastInsertId();

    // 5. Registrar en Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CONFIRMAR_RECARGA', "Acreditada recarga #$recarga_id por Bs. $monto a usuario #$usuario_id_objetivo");

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '✅ Recarga acreditada exitosamente.',
        'usuario_id' => $usuario_id_objetivo,
        'nuevo_saldo' => $new_balance,
        'transaccion_id' => $transaccion_id,
        'estado' => 'CONFIRMADA'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en confirmar_recarga: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos al confirmar la recarga.']);
}
?>
