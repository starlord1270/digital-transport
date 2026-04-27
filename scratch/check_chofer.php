<?php
// Usando 127.0.0.1 para evitar problemas de socket en CLI
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $stmt = $pdo->query("DESCRIBE CHOFER");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
