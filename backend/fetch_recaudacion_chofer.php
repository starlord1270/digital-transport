<?php
/**
 * Archivo: fetch_recaudacion_chofer.php
 * Descripción: Obtiene el total recaudado por el chofer en el día actual
 * y un desglose básico para reconstruir contadores en el frontend.
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

$chofer_usuario_id = $_SESSION['usuario_id'];

// 3. Conectar a la BD
require_once 'bd.php';

try {
    // 4. Obtener el chofer_id real (clave foránea en TRANSACCION)
    $sql_chofer = "SELECT chofer_id FROM CHOFER WHERE usuario_id = ?";
    $stmt_chofer = $conn->prepare($sql_chofer);
    $stmt_chofer->bind_param("i", $chofer_usuario_id);
    $stmt_chofer->execute();
    $result_chofer = $stmt_chofer->get_result();

    if ($result_chofer->num_rows === 0) {
        throw new Exception("Chofer no encontrado.");
    }

    $chofer_data = $result_chofer->fetch_assoc();
    $chofer_id = $chofer_data['chofer_id'];
    $stmt_chofer->close();

    // 5. Consultar transacciones de tipo 'COBRO' hechas por este chofer HOY
    // Agrupamos por monto para saber cuántos de cada tipo (aprox) se hicieron.
    $sql_trans = "SELECT monto, COUNT(*) as cantidad 
                  FROM TRANSACCION 
                  WHERE chofer_id_cobro = ? 
                    AND tipo = 'COBRO' 
                    AND DATE(fecha_hora) = CURDATE()
                  GROUP BY monto";

    $stmt_trans = $conn->prepare($sql_trans);
    $stmt_trans->bind_param("i", $chofer_id);
    $stmt_trans->execute();
    $result_trans = $stmt_trans->get_result();

    $desglose = [];
    $total_recaudado = 0.0;

    while ($row = $result_trans->fetch_assoc()) {
        $monto = floatval($row['monto']);
        $cantidad = intval($row['cantidad']);
        
        $total_recaudado += ($monto * $cantidad);
        
        $desglose[] = [
            'monto' => $monto,
            'cantidad' => $cantidad
        ];
    }

    echo json_encode([
        'success' => true,
        'total_recaudado' => $total_recaudado,
        'desglose' => $desglose
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener recaudación: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
