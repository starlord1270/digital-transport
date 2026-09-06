<?php
/**
 * DIGITAL TRANSPORT - PDO DATABASE WRAPPER
 * 
 * Este archivo centraliza la conexión a la base de datos utilizando PDO
 * para mayor seguridad y flexibilidad, con soporte para variables de entorno.
 */

// Helper para cargar archivo .env si existe
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Cargar .env si existe en la raíz del proyecto
$envPath = dirname(__DIR__, 2) . '/.env';
loadEnv($envPath);

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = getenv('DB_PORT') ?: '3306';
        $this->db_name = getenv('DB_NAME') ?: 'digital-transport';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch(PDOException $exception) {
            error_log("Error de conexión a la base de datos: " . $exception->getMessage());
            
            $appEnv = getenv('APP_ENV') ?: 'production';
            if ($appEnv === 'local' || getenv('APP_DEBUG') === 'true') {
                die("Error de base de datos: " . htmlspecialchars($exception->getMessage()));
            } else {
                die("Error crítico de servicio. Por favor, contacte al administrador.");
            }
        }

        return $this->conn;
    }
}

// Inicializar conexión global para retrocompatibilidad controlada
$database = new Database();
$pdo = $database->getConnection();
?>

