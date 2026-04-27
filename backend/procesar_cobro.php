<?php
/**
 * DIGITAL TRANSPORT - PROCESAR COBRO (SEGURIDAD REFORZADA)
 * Detección automática de tarifa basada en el perfil del usuario para evitar fraudes.
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// 1. Verificar sesión básica
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = 0;
$chofer_id = 0;

// DETERMINAR MODO DE COBRO E IDENTIFICAR PASAJERO
if (isset($data['bus_payment']) && $data['bus_payment'] === true) {
    // MODO: PASAJERO ESCANEA AL BUS
    $usuario_id = $_SESSION['usuario_id'];
    $chofer_id = intval($data['chofer_id'] ?? 0);
} else {
    // MODO: CHOFER ESCANEA AL PASAJERO
    if ($_SESSION['tipo_usuario_id'] != 3) {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit;
    }
    
    $qrData = trim($data['qrData'] ?? '');
    if (strpos($qrData, 'DT-USER-') === 0) {
        $usuario_id = intval(explode('-', $qrData)[2]);
    } elseif (strpos($qrData, 'USER_') === 0) {
        $usuario_id = intval(explode('_', $qrData)[1]);
    }
    
    $stmt = $pdo->prepare("SELECT chofer_id FROM CHOFER WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $chofer_id = $stmt->fetchColumn();
}

if ($usuario_id <= 0 || $chofer_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Identificación fallida.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. DETECCIÓN AUTOMÁTICA DE TARIFA (SEGURIDAD LADO SERVIDOR)
    // Buscamos si el pasajero tiene una validación especial aprobada
    $stmt = $pdo->prepare("
        SELECT tipo_desc_id 
        FROM VALIDACION_ESPECIAL 
        WHERE usuario_id = ? AND estado_validacion = 'APROBADA' 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $tipo_desc_id = $stmt->fetchColumn() ?: 1; // 1 = Estándar

    // Obtener el monto de la tarifa oficial para ese tipo de descuento
    $stmt = $pdo->prepare("SELECT tarifa_id, monto FROM TARIFA WHERE tipo_desc_id = ? LIMIT 1");
    $stmt->execute([$tipo_desc_id]);
    $tarifa_oficial = $stmt->fetch();

    if (!$tarifa_oficial) {
        // Fallback a tarifa estándar
        $stmt = $pdo->query("SELECT tarifa_id, monto FROM TARIFA WHERE tipo_desc_id = 1 LIMIT 1");
        $tarifa_oficial = $stmt->fetch();
    }

    $monto_a_cobrar = floatval($tarifa_oficial['monto']);

    // 3. Validar pasajero y saldo
    $stmt = $pdo->prepare("SELECT nombre_completo, saldo FROM USUARIO WHERE usuario_id = ? FOR UPDATE");
    $stmt->execute([$usuario_id]);
    $pasajero = $stmt->fetch();

    if (!$pasajero) throw new Exception("Pasajero no encontrado.");
    if ($pasajero['saldo'] < $monto_a_cobrar) {
        throw new Exception("Saldo insuficiente. Tu tarifa es de Bs. " . number_format($monto_a_cobrar, 2));
    }

    // 4. Realizar cobro
    $nuevo_saldo = $pasajero['saldo'] - $monto_a_cobrar;
    $stmt = $pdo->prepare("UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?");
    $stmt->execute([$nuevo_saldo, $usuario_id]);

    // 5. Registrar transacción
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

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>