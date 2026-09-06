<?php
use PHPUnit\Framework\TestCase;

class DescuentoTest extends TestCase {
    private $pdo;
    private $testUsuarioId = null;
    private $testEmail = 'descuento_user@test.com';
    private $tipoDescuentoId = 2; 

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

        $this->pdo->exec("DELETE FROM USUARIO WHERE email = '{$this->testEmail}'");
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) VALUES (1, '900', 'Descuento Test', ?, ?, 50.00)");
        $stmt->execute([$this->testEmail, $password_hash]);
        $this->testUsuarioId = $this->pdo->lastInsertId();

        $this->pdo->exec("DELETE FROM VALIDACION_ESPECIAL WHERE usuario_id = {$this->testUsuarioId}");
    }

    protected function tearDown(): void {
        if ($this->pdo && $this->testUsuarioId) {
            $this->pdo->exec("DELETE FROM VALIDACION_ESPECIAL WHERE usuario_id = {$this->testUsuarioId}");
            $this->pdo->exec("DELETE FROM USUARIO WHERE usuario_id = {$this->testUsuarioId}");
        }
    }

    public function testAsignacionDescuentoExitoso() {
        $sql_insert = "INSERT INTO VALIDACION_ESPECIAL (usuario_id, tipo_desc_id, fecha_solicitud, estado_validacion) VALUES (?, ?, NOW(), 'APROBADA')";
        $stmt_insert = $this->pdo->prepare($sql_insert);
        $result = $stmt_insert->execute([$this->testUsuarioId, $this->tipoDescuentoId]);

        $this->assertTrue($result, "La inserción de la validación especial debe ser exitosa.");

        $stmt_select = $this->pdo->prepare("SELECT COUNT(*) as total FROM VALIDACION_ESPECIAL WHERE usuario_id = ? AND tipo_desc_id = ? AND estado_validacion = 'APROBADA'");
        $stmt_select->execute([$this->testUsuarioId, $this->tipoDescuentoId]);
        $total_registros = $stmt_select->fetch()['total'];
        
        $this->assertEquals(1, $total_registros, "El registro de validación especial no se encontró en la base de datos.");
    }
}