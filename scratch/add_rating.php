<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $pdo->exec("ALTER TABLE CHOFER ADD COLUMN rating DECIMAL(3,2) DEFAULT 5.0");
    echo "Columna 'rating' añadida con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
