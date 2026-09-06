<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $pdo->exec("ALTER TABLE CANJE_CHOFER ADD COLUMN estado ENUM('PENDIENTE', 'PAGADO') DEFAULT 'PENDIENTE'");
    echo "Columna 'estado' añadida a CANJE_CHOFER con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
