<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $stmt = $pdo->query("SELECT u.nombre_completo, c.linea_id, c.estado_servicio FROM USUARIO u JOIN CHOFER c ON u.usuario_id = c.usuario_id ORDER BY u.usuario_id DESC LIMIT 5");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($drivers, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
