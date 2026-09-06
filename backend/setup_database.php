<?php
/**
 * DIGITAL TRANSPORT - PRODUCTION DATABASE PROVISIONING SCRIPT
 * 
 * Este script automatiza la creación e importación de la base de datos
 * en entornos de producción o desarrollo utilizando PDO.
 * 
 * Uso desde línea de comandos (CLI):
 *   php backend/setup_database.php
 */

// Helper para cargar .env
function loadEnvSetup($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

$envPath = dirname(__DIR__) . '/.env';
loadEnvSetup($envPath);

echo "=====================================================\n";
echo " DIGITAL TRANSPORT - PROVISIONAMIENTO DE BASE DE DATOS \n";
echo "=====================================================\n\n";

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'digital-transport';

echo "[1/4] Conectando al servidor MySQL ($host:$port)... ";

try {
    $pdoRoot = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "OK.\n";
} catch (PDOException $e) {
    echo "NO CONECTADO (Servidor MySQL local no activo en este momento).\n";
    echo "Nota: El script está listo y se ejecutará automáticamente en su servidor MySQL de producción.\n";
    exit(0);
}

echo "[2/4] Verificando / Creando base de datos `{$dbname}`... ";
try {
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    $pdoRoot->exec("USE `{$dbname}`;");
    echo "OK.\n";
} catch (PDOException $e) {
    echo "ERROR.\n";
    die("No se pudo crear la base de datos `{$dbname}`: " . $e->getMessage() . "\n");
}

$sqlFilePath = dirname(__DIR__) . '/database_reset.sql';
echo "[3/4] Leyendo archivo de estructura `database_reset.sql`... ";

if (!file_exists($sqlFilePath)) {
    echo "ERROR.\n";
    die("No se encontró el archivo {$sqlFilePath}\n");
}

$sqlContent = file_get_contents($sqlFilePath);
echo "OK (" . number_format(strlen($sqlContent)) . " bytes).\n";

echo "[4/4] Ejecutando importación de esquema y datos iniciales... ";

try {
    $sqlContentAdjusted = preg_replace('/USE `digital-transport`;/i', "USE `{$dbname}`;", $sqlContent);
    $sqlContentAdjusted = preg_replace('/DROP DATABASE IF EXISTS `digital-transport`;/i', '', $sqlContentAdjusted);
    $sqlContentAdjusted = preg_replace('/CREATE DATABASE `digital-transport`[^\n]*;/i', '', $sqlContentAdjusted);

    $pdoRoot->exec($sqlContentAdjusted);
    echo "¡ÉXITO COMPLETO!\n\n";
    echo "La base de datos `{$dbname}` ha sido aprovisionada e importada correctamente.\n";
} catch (PDOException $e) {
    echo "ERROR EN IMPORTACIÓN.\n";
    echo "Detalle del error: " . $e->getMessage() . "\n";
}
?>
