<?php
require_once '/opt/lampp/htdocs/Competencia-Analisis/digital-transport/backend/includes/db.php';
$tables = ['USUARIO', 'CHOFER', 'VEHICULO', 'LINEA'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $col) {
            echo "{$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
