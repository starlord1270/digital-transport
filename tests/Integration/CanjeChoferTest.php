<?php
use PHPUnit\Framework\TestCase;

class CanjeChoferTest extends TestCase {
    private $pdo;
    private $choferUsuarioId;
    private $choferId;
    private $montoInicial = 100.00;
    private $montoCanje = 100.00;

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

        // --- 1. Limpieza de datos anteriores ---
        $this->pdo->exec("DELETE FROM USUARIO WHERE email = 'canje_chofer@test.com'");

        // --- 2. Preparar Chofer ---
        $stmt = $this->pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) VALUES (3, '400', 'Chofer Canje Test', 'canje_chofer@test.com', 'hash', ?)");
        $stmt->execute([$this->montoInicial]);
        $this->choferUsuarioId = $this->pdo->lastInsertId();

        $stmtChofer = $this->pdo->prepare("INSERT INTO CHOFER (usuario_id, linea_id) VALUES (?, 1)");
        $stmtChofer->execute([$this->choferUsuarioId]);
        $this->choferId = $this->pdo->lastInsertId();

        $this->pdo->exec("DELETE FROM CANJE_CHOFER WHERE chofer_id = {$this->choferId}");
    }

    protected function tearDown(): void {
        if (!$this->pdo) return;
        if ($this->choferId) {
            $this->pdo->exec("DELETE FROM CANJE_CHOFER WHERE chofer_id = {$this->choferId}");
            $this->pdo->exec("DELETE FROM CHOFER WHERE chofer_id = {$this->choferId}");
        }
        if ($this->choferUsuarioId) {
            $this->pdo->exec("DELETE FROM USUARIO WHERE usuario_id = {$this->choferUsuarioId}");
        }
    }

    public function testCanjeChoferExitoso() {
        $monto = $this->montoCanje; 
        $saldoEsperado = 0.00;

        $stmt_update = $this->pdo->prepare("UPDATE USUARIO SET saldo = saldo - ? WHERE usuario_id = ?");
        $result_update = $stmt_update->execute([$monto, $this->choferUsuarioId]);

        $this->assertTrue($result_update, "La actualización de saldo en USUARIO debe ser exitosa.");

        $stmt_saldo = $this->pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt_saldo->execute([$this->choferUsuarioId]);
        $row = $stmt_saldo->fetch();
        $saldoActual = floatval($row['saldo']);
        $this->assertEquals($saldoEsperado, $saldoActual, "El saldo final del chofer debe ser 0.00 después del canje.");

        $stmt_insert = $this->pdo->prepare("INSERT INTO CANJE_CHOFER (chofer_id, monto) VALUES (?, ?)");
        $stmt_insert->execute([$this->choferId, $monto]);

        $stmt_canje = $this->pdo->prepare("SELECT COUNT(*) as total FROM CANJE_CHOFER WHERE chofer_id = ? AND monto = ?");
        $stmt_canje->execute([$this->choferId, $monto]);
        $total_registros = $stmt_canje->fetch()['total'];
        
        $this->assertEquals(1, $total_registros, "Se debe registrar exactamente un canje por el monto correcto.");
    }
}