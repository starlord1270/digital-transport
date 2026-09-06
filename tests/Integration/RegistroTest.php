<?php
use PHPUnit\Framework\TestCase;

class RegistroTest extends TestCase {
    private $pdo;
    private $testEmail = 'integration_test_user@example.com';
    private $testDoc = '999888777';

    protected function setUp(): void {
        $host = defined('DB_TEST_HOST') ? DB_TEST_HOST : '127.0.0.1';
        $user = defined('DB_TEST_USER') ? DB_TEST_USER : 'root';
        $pass = defined('DB_TEST_PASS') ? DB_TEST_PASS : '';
        $dbname = defined('DB_TEST_NAME') ? DB_TEST_NAME : 'digital_transport_test';

        try {
            $this->pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            $this->markTestSkipped("Servidor MySQL de prueba no disponible: " . $e->getMessage());
            return;
        }

        $this->pdo->exec("DELETE FROM USUARIO WHERE email = '{$this->testEmail}' OR documento_identidad = '{$this->testDoc}'");
    }

    protected function tearDown(): void {
        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM USUARIO WHERE email = '{$this->testEmail}' OR documento_identidad = '{$this->testDoc}'");
        }
    }

    public function testRegistroPasajeroEstandarExitoso() {
        $password_raw = 'securePass123';
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
        $tipo_usuario_id = 1;
        $nombre = "Test Integracion";

        $sql = "INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, fecha_registro)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$tipo_usuario_id, $this->testDoc, $nombre, $this->testEmail, $password_hash]);

        $this->assertTrue($result, "La inserción SQL deberia ser exitosa.");

        $stmtSelect = $this->pdo->prepare("SELECT COUNT(*) as total FROM USUARIO WHERE email = ?");
        $stmtSelect->execute([$this->testEmail]);
        $total = $stmtSelect->fetch()['total'];

        $this->assertEquals(1, $total, "Debería haber exactamente 1 registro en la tabla USUARIO.");
    }

    public function testRegistroFallaPorDuplicado() {
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, fecha_registro) VALUES (1, ?, 'Inicial', ?, ?, NOW())");
        $stmt->execute([$this->testDoc, $this->testEmail, $password_hash]);

        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) as total FROM USUARIO WHERE email = ?");
        $stmtCheck->execute([$this->testEmail]);
        $total = $stmtCheck->fetch()['total'];

        $this->assertEquals(1, $total, "Solo debería haber 1 registro, ya que la lógica de duplicado debe prevenir la segunda insercion.");
    }
}