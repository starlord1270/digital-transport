<?php
use PHPUnit\Framework\TestCase;

class CobroTest extends TestCase {
    private $pdo;
    private $pasajeroId;
    private $choferId;
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

        $this->pdo->exec("DELETE FROM USUARIO WHERE email IN ('cobro_pasajero@test.com', 'cobro_chofer@test.com')");
        $stmtP = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) VALUES (1, '100', 'Cobro Pasajero', 'cobro_pasajero@test.com', 'hash', 10.00)");
        $stmtP->execute();
        $this->pasajeroId = $this->pdo->lastInsertId();

        $stmtC = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) VALUES (3, '200', 'Cobro Chofer', 'cobro_chofer@test.com', 'hash', 0.00)");
        $stmtC->execute();
        $choferUsuarioId = $this->pdo->lastInsertId();

        $stmtCh = $this->pdo->prepare("INSERT INTO CHOFER (usuario_id, linea_id) VALUES (?, 1)");
        $stmtCh->execute([$choferUsuarioId]);
        $this->choferId = $this->pdo->lastInsertId();
    }

    protected function tearDown(): void {
        if ($this->pdo) {
            if ($this->choferId) {
                $this->pdo->exec("DELETE FROM TRANSACCION WHERE chofer_id_cobro = {$this->choferId}");
                $this->pdo->exec("DELETE FROM CHOFER WHERE chofer_id = {$this->choferId}");
            }
            $this->pdo->exec("DELETE FROM USUARIO WHERE email IN ('cobro_pasajero@test.com', 'cobro_chofer@test.com')");
        }
    }

    public function testCobroExitosoDescuentaSaldoYRegistraTransaccion() {
        $saldoInicial = 10.00;
        $saldoEsperado = $saldoInicial - $this->tarifaEstandar; 

        $stmt_update = $this->pdo->prepare("UPDATE USUARIO SET saldo = saldo - ? WHERE usuario_id = ?");
        $stmt_update->execute([$this->tarifaEstandar, $this->pasajeroId]);

        $stmt_saldo = $this->pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt_saldo->execute([$this->pasajeroId]);
        $saldoActual = floatval($stmt_saldo->fetch()['saldo']);
        $this->assertEquals($saldoEsperado, $saldoActual, "El saldo final no es el esperado (7.50).");

        $stmt_transaccion = $this->pdo->prepare("INSERT INTO TRANSACCION (tipo, monto, chofer_id_cobro, fecha_hora) VALUES ('COBRO', ?, ?, NOW())");
        $stmt_transaccion->execute([$this->tarifaEstandar, $this->choferId]);

        $stmt_count = $this->pdo->prepare("SELECT COUNT(*) as total FROM TRANSACCION WHERE chofer_id_cobro = ? AND tipo = 'COBRO'");
        $stmt_count->execute([$this->choferId]);
        $total_cobros = $stmt_count->fetch()['total'];
        
        $this->assertEquals(1, $total_cobros, "Se debe registrar exactamente una transacción de COBRO.");
    }
}