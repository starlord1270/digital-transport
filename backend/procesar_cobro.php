<?php
// procesar_cobro.php
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

// 🛑 SIMULACIÓN DEL ID DEL CHOFER QUE REALIZA EL COBRO 🛑
$chofer_id = 101; 

// Obtener datos de la solicitud POST
$input = json_decode(file_get_contents('php://input'), true);
$qr_data = $input['qrData'] ?? null;
$tarifa_id = $input['tarifaId'] ?? null;
$monto = $input['monto'] ?? null;

if (!$qr_data || !$tarifa_id || !$monto) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. EXTRAER ID DE TARJETA DEL QR DATA
    // El formato del QR es: DT-TRJ-1-NFC-A1B2C3D4 (ejemplo de la respuesta anterior)
    $parts = explode('-', $qr_data);
    $tarjeta_id = $parts[2] ?? null;

    if (!$tarjeta_id || !is_numeric($tarjeta_id)) {
        throw new Exception("Formato de QR inválido o ID de tarjeta no encontrado.");
    }
    
    $monto = (float)$monto;

    // 2. INICIAR TRANSACCIÓN DE BASE DE DATOS (Asegurar Atomicidad)
    $pdo->beginTransaction();

    // 3. VERIFICAR SALDO SUFICIENTE EN LA TARJETA
    $stmt_saldo = $pdo->prepare("SELECT saldo_actual, estado FROM TARJETA WHERE tarjeta_id = :id FOR UPDATE");
    $stmt_saldo->bindParam(':id', $tarjeta_id, PDO::PARAM_INT);
    $stmt_saldo->execute();
    $tarjeta = $stmt_saldo->fetch(PDO::FETCH_ASSOC);

    if (!$tarjeta) {
        throw new Exception("Tarjeta no encontrada.");
    }
    if ($tarjeta['estado'] !== 'Activo') {
        throw new Exception("La tarjeta está inactiva o bloqueada.");
    }
    if ($tarjeta['saldo_actual'] < $monto) {
        throw new Exception("Saldo insuficiente. Saldo actual: " . number_format($tarjeta['saldo_actual'], 2) . " Bs.");
    }

    // 4. ACTUALIZAR EL SALDO
    $nuevo_saldo = $tarjeta['saldo_actual'] - $monto;
    $stmt_update = $pdo->prepare("UPDATE TARJETA SET saldo_actual = :nuevo_saldo WHERE tarjeta_id = :id");
    $stmt_update->bindParam(':nuevo_saldo', $nuevo_saldo);
    $stmt_update->bindParam(':id', $tarjeta_id, PDO::PARAM_INT);
    $stmt_update->execute();

    // 5. REGISTRAR LA TRANSACCIÓN (USO)
    // Asumimos que la tabla TRANSACCION tiene: tarjeta_id, monto, tipo_movimiento, fecha_hora, chofer_id, tarifa_id
    $stmt_transaccion = $pdo->prepare("
        INSERT INTO TRANSACCION 
        (tarjeta_id, monto, tipo_movimiento, fecha_hora, chofer_id, tarifa_id) 
        VALUES (:tarjeta_id, :monto, 'Uso', NOW(), :chofer_id, :tarifa_id)
    ");
    $stmt_transaccion->bindParam(':tarjeta_id', $tarjeta_id, PDO::PARAM_INT);
    $stmt_transaccion->bindParam(':monto', $monto);
    $stmt_transaccion->bindParam(':chofer_id', $chofer_id, PDO::PARAM_INT);
    $stmt_transaccion->bindParam(':tarifa_id', $tarifa_id, PDO::PARAM_INT);
    $stmt_transaccion->execute();

    // 6. CONFIRMAR LA TRANSACCIÓN
    $pdo->commit();

    // 7. DEVOLVER RESPUESTA EXITOSA
    echo json_encode([
        'success' => true, 
        'message' => 'Cobro exitoso!', 
        'tarifa' => $monto, 
        'nuevo_saldo' => number_format($nuevo_saldo, 2, '.', '')
    ]);

} catch (Exception $e) {
    // Si hay un error, deshacer los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>