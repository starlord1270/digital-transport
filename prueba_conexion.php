<?php
$dbHost = 'localhost';
$dbName = 'digital-transport';
$dbUser = 'root'; 
$dbPass = ''; 

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    echo "Conexión exitosa. El servidor está funcionando.";
} catch (PDOException $e) {
    echo "ERROR DE CONEXIÓN: " . $e->getMessage();
}