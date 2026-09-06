<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    // Ponemos a los choferes recientes en PENDIENTE para que el admin pueda probar la validación
    $pdo->exec("UPDATE CHOFER SET estado_servicio = 'PENDIENTE' WHERE estado_servicio = 'INACTIVO' LIMIT 5");
    echo "Choferes actualizados a PENDIENTE para pruebas.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
