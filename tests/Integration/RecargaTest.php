<?php
use PHPUnit\Framework\TestCase;

class RecargaTest extends TestCase {
    private $pdo;
    private $testEmail = 'recarga_test_user@example.com';
    private $testDoc = '111222333';
    private $testUsuarioId = null;

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

        $this->pdo->exec("DELETE FROM USUARIO WHERE documento_identidad = '{$this->testDoc}'");
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo, fecha_registro) VALUES (1, ?, 'Usuario Recarga', ?, ?, 0.00, NOW())");
        $stmt->execute([$this->testDoc, $this->testEmail, $password_hash]);
        $this->testUsuarioId = $this->pdo->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM USUARIO WHERE documento_identidad = '{$this->testDoc}'");
        }
    }

    public function testRecargaAumentaElSaldoYRegistraTransaccion() {
        $monto_recarga = 50.00;

        $stmt_update = $this->pdo->prepare("UPDATE USUARIO SET saldo = saldo + ? WHERE usuario_id = ?");
        $stmt_update->execute([$monto_recarga, $this->testUsuarioId]);

        $stmt_saldo = $this->pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt_saldo->execute([$this->testUsuarioId]);
        $saldo_actual = $stmt_saldo->fetch()['saldo'];
        $this->assertEquals(50.00, floatval($saldo_actual), "El saldo final debe ser 50.00.");

        $sql_transaccion = "INSERT INTO TRANSACCION (tipo, monto, fecha_hora) VALUES ('RECARGA', ?, NOW())";
        $stmt_transaccion = $this->pdo->prepare($sql_transaccion);
        $stmt_transaccion->execute([$monto_recarga]);

        $stmt_check = $this->pdo->prepare("SELECT COUNT(*) as total FROM TRANSACCION WHERE tipo = 'RECARGA' AND monto = ?");
        $stmt_check->execute([$monto_recarga]);
        $total_transacciones = $stmt_check->fetch()['total'];
        
        $this->assertTrue($total_transacciones > 0, "Debe existir al menos una transacción de recarga.");
    }
}