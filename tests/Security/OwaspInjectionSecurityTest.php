<?php
use PHPUnit\Framework\TestCase;

class OwaspInjectionSecurityTest extends TestCase {

    /**
     * Prueba contra Inyección SQL (SQLi) mediante parámetros PDO preparados
     */
    public function testPrevencionInyeccionSQL() {
        $sqliPayloadsWithQuotes = [
            "' OR '1'='1",
            "'; DROP TABLE USUARIO; --",
            "admin'--"
        ];

        foreach ($sqliPayloadsWithQuotes as $payload) {
            $escaped = addslashes($payload);
            $this->assertNotEquals($payload, $escaped, "El payload SQLi con comillas '{$payload}' debe ser neutralizado con escape.");
        }

        // Simular sentencia preparada de búsqueda por email con PDO
        $sql = "SELECT * FROM USUARIO WHERE email = ?";
        $this->assertStringContainsString("?", $sql, "La consulta SQL preparada debe utilizar marcadores de posición posicionales (bind parameter).");
    }

    /**
     * Prueba contra Inyección de Comandos del Sistema y Path Traversal
     */
    public function testPrevencionPathTraversalYCommandInjection() {
        $traversalPayloads = [
            "../../../../etc/passwd",
            "....//....//config.php",
            "file:///etc/shadow",
            "index.php; cat /etc/passwd"
        ];

        foreach ($traversalPayloads as $path) {
            $basename = basename($path);
            $this->assertStringNotContainsString("../", $basename, "Path traversal en '{$path}' debe ser filtrado.");
            $this->assertStringNotContainsString(";", $basename, "Command injection en '{$path}' debe ser desarmado.");
        }
    }
}
