<?php
/**
 * DIGITAL TRANSPORT - FETCH HISTORY (REFACTORIZADA)
 */
header('Content-Type: application/json');
require_once 'includes/db.php'; // Usa el nuevo envoltorio PDO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Sesión no activa."]);
    exit();
}

$user_id = $_SESSION['usuario_id'];
$date_limit = date('Y-m-d H:i:s', strtotime('-30 days'));

try {
    // Consulta optimizada para historial de viajes (COBRO) y recargas (RECARGA)
    $sql = "
        SELECT
            T.transaccion_id,
            T.tipo,
            T.monto,
            T.fecha_hora,
            COALESCE(L.nombre, 'Sistema Digital') AS nombre_linea,
            CASE 
                WHEN T.tipo = 'RECARGA' THEN 'Recarga de Saldo'
                ELSE 'Cobro por Viaje'
            END AS detalles
        FROM 
            TRANSACCION T
        LEFT JOIN
            CHOFER C ON T.chofer_id_cobro = C.chofer_id
        LEFT JOIN
            LINEA L ON C.linea_id = L.linea_id
        WHERE 
            T.usuario_id = ? 
            AND T.fecha_hora >= ?
        ORDER BY 
            T.fecha_hora DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date_limit]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_history = [];
    $total_spent = 0;
    $total_trips = 0;

    foreach ($results as $row) {
        $dt = new DateTime($row['fecha_hora']);
        
        if ($row['tipo'] === 'COBRO') {
            $total_spent += floatval($row['monto']);
            $total_trips++;
        }

        $formatted_history[] = [
            'id' => $row['transaccion_id'],
            'type' => $row['tipo'],
            'date' => $dt->format('d/m/Y'),
            'time' => $dt->format('H:i'),
            'line' => $row['nombre_linea'],
            'details' => $row['detalles'],
            'amount' => $row['monto'],
            'status' => 'Completado'
        ];
    }

    echo json_encode([
        'total_trips' => $total_trips,
        'total_spent' => number_format($total_spent, 2, '.', ''),
        'history' => $formatted_history
    ]);

} catch (PDOException $e) {
    error_log("Error en fetch_history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Error de base de datos al obtener el historial."]);
}
?>