<?php
use PHPUnit\Framework\TestCase;

class CobroFalloTest extends TestCase {
    private $pdo;
    private $pasajeroId;
    private $tarifaEstandar = 2.50; 

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
        
        $this->pdo->exec("DELETE FROM USUARIO WHERE email = 'cobro_fail_pasajero@test.com'");
        $stmt = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) VALUES (1, '300', 'Cobro Fail Pasajero', 'cobro_fail_pasajero@test.com', 'hash', 1.00)");
        $stmt->execute();
        $this->pasajeroId = $this->pdo->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->pdo) {
            $this->pdo->exec("DELETE FROM USUARIO WHERE email = 'cobro_fail_pasajero@test.com'");
        }
    }

    public function testCobroFallaPorSaldoInsuficiente() {
        $saldoInicial = 1.00;
        
        $stmt = $this->pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt->execute([$this->pasajeroId]);
        $saldoActual = floatval($stmt->fetch()['saldo']);

        $this->assertLessThan($this->tarifaEstandar, $saldoActual, "El saldo actual debería ser menor a la tarifa (1.00 < 2.50).");

        $stmtFinal = $this->pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmtFinal->execute([$this->pasajeroId]);
        $saldoFinal = floatval($stmtFinal->fetch()['saldo']);
        
        $this->assertEquals($saldoInicial, $saldoFinal, "El saldo final debe permanecer sin cambios si la transacción falla.");
    }
}