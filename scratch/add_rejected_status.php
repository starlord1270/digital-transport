<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $pdo->exec("ALTER TABLE CHOFER MODIFY COLUMN estado_servicio ENUM('ACTIVO','INACTIVO','LICENCIA','PENDIENTE','RECHAZADO') DEFAULT 'PENDIENTE'");
    echo "Enum 'estado_servicio' actualizado con éxito. Ahora incluye 'RECHAZADO'.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
