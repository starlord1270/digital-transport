<?php
/**
 * DIGITAL TRANSPORT - PDO DATABASE WRAPPER
 * 
 * Este archivo centraliza la conexión a la base de datos utilizando PDO
 * para mayor seguridad y flexibilidad.
 */

class Database {
    private $host = "localhost";
    private $db_name = "digital-transport";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            die("Error crítico de base de datos.");
        }

        return $this->conn;
    }
}

// Inicializar conexión global para retrocompatibilidad controlada
$database = new Database();
$pdo = $database->getConnection();
?>
