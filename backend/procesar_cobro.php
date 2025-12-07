<?php
/**
 * Archivo: procesar_cobro.php
 * Descripción: Procesa el cobro de pasaje mediante código QR escaneado por el chofer
 * Valida el QR, verifica saldo, descuenta el monto y registra la transacción
 */

header('Content-Type: application/json');

// 1. Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Verificar que el chofer esté logueado
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 3) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso denegado. Debe estar logueado como chofer.'
    ]);
    exit;
}

// Obtener el usuario_id del chofer de la sesión
$chofer_usuario_id = $_SESSION['usuario_id'];

// 3. Consultar el chofer_id desde la base de datos
require_once 'bd.php';

$sql_chofer = "SELECT chofer_id FROM CHOFER WHERE usuario_id = ?";
$stmt_chofer = $conn->prepare($sql_chofer);
$stmt_chofer->bind_param("i", $chofer_usuario_id);
$stmt_chofer->execute();
$result_chofer = $stmt_chofer->get_result();

if ($result_chofer->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Error: Chofer no encontrado en la base de datos.'
    ]);
    $stmt_chofer->close();
    $conn->close();
    exit;
}

$chofer_data = $result_chofer->fetch_assoc();
$chofer_id = $chofer_data['chofer_id'];
$stmt_chofer->close();

// 4. Recibir datos JSON del frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Datos JSON inválidos.'
    ]);
    exit;
}

$qrData = trim($data['qrData'] ?? '');
$tarifaId = intval($data['tarifaId'] ?? 0);
$monto = floatval($data['monto'] ?? 0);

// 5. Validar datos básicos
if (empty($qrData) || $tarifaId <= 0 || $monto <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos o inválidos.'
    ]);
    exit;
}

// 6. Extraer usuario_id del código QR
// Formato esperado: DT-USER-{usuario_id}-{timestamp}
$parts = explode('-', $qrData);

if (count($parts) < 3 || $parts[0] !== 'DT' || $parts[1] !== 'USER') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Código QR inválido. Formato no reconocido.'
    ]);
    exit;
}

$usuario_id = intval($parts[2]);

if ($usuario_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Código QR inválido. ID de usuario no válido.'
    ]);
    exit;
}

// 6. Conectar a la base de datos
require_once 'bd.php';

// 7. Verificar que el usuario existe y obtener su saldo actual
$sql_usuario = "SELECT nombre_completo, saldo, tipo_usuario_id FROM USUARIO WHERE usuario_id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no encontrado en el sistema.'
    ]);
    $stmt_usuario->close();
    $conn->close();
    exit;
}

$usuario_data = $result_usuario->fetch_assoc();
$nombre_completo = $usuario_data['nombre_completo'];
$saldo_actual = floatval($usuario_data['saldo']);
$tipo_usuario = intval($usuario_data['tipo_usuario_id']);
$stmt_usuario->close();

// 8. Verificar que el saldo sea suficiente
if ($saldo_actual < $monto) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => "Saldo insuficiente. Saldo actual: Bs. " . number_format($saldo_actual, 2)
    ]);
    $conn->close();
    exit;
}

// 9. Iniciar transacción SQL
$conn->begin_transaction();

try {
    // a) Descontar el monto del saldo del usuario
    $nuevo_saldo = $saldo_actual - $monto;
    $sql_update_saldo = "UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?";
    $stmt_update = $conn->prepare($sql_update_saldo);
    $stmt_update->bind_param("di", $nuevo_saldo, $usuario_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Fallo al actualizar el saldo del usuario.");
    }
    $stmt_update->close();
    
    // b) Registrar la transacción en la tabla TRANSACCION
    $tipo = "COBRO";
    $sql_insert = "INSERT INTO TRANSACCION (tipo, monto, usuario_id, chofer_id_cobro, fecha_hora) 
                   VALUES (?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sdii", $tipo, $monto, $usuario_id, $chofer_id);
    
    if (!$stmt_insert->execute()) {
        throw new Exception("Fallo al registrar la transacción.");
    }
    $stmt_insert->close();
    
    // c) Confirmar transacción
    $conn->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Cobro procesado exitosamente',
        'pasajero' => $nombre_completo,
        'monto_cobrado' => number_format($monto, 2),
        'nuevo_saldo' => number_format($nuevo_saldo, 2)
    ]);
    
} catch (Exception $e) {
    // d) Revertir transacción en caso de error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar el cobro: ' . $e->getMessage()
    ]);
}

// 10. Cerrar conexión
$conn->close();
?>