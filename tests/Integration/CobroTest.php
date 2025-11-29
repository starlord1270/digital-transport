<?php
use PHPUnit\Framework\TestCase;

class CobroTest extends TestCase {
    private $conn;
    private $pasajeroId;
    private $choferId;
    // La tarifa Adulto Estándar es 2.50 según tu esquema
    private $tarifaEstandar = 2.50; 

    protected function setUp(): void {
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // 1. Preparar Pasajero con saldo
        $this->conn->query("DELETE FROM USUARIO WHERE email IN ('cobro_pasajero@test.com', 'cobro_chofer@test.com')");
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) 
                            VALUES (1, '100', 'Cobro Pasajero', 'cobro_pasajero@test.com', 'hash', 10.00)");
        $this->pasajeroId = $this->conn->insert_id;

        // 2. Preparar Chofer
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) 
                            VALUES (3, '200', 'Cobro Chofer', 'cobro_chofer@test.com', 'hash', 0.00)");
        $choferUsuarioId = $this->conn->insert_id;
        $this->conn->query("INSERT INTO CHOFER (usuario_id, linea_id) VALUES ({$choferUsuarioId}, 1)");
        $this->choferId = $this->conn->insert_id;
    }

    protected function tearDown(): void {
        $this->conn->query("DELETE FROM TRANSACCION WHERE chofer_id_cobro = {$this->choferId}");
        $this->conn->query("DELETE FROM CHOFER WHERE chofer_id = {$this->choferId}");
        $this->conn->query("DELETE FROM USUARIO WHERE email IN ('cobro_pasajero@test.com', 'cobro_chofer@test.com')");
        $this->conn->close();
    }

    public function testCobroExitosoDescuentaSaldoYRegistraTransaccion() {
        $saldoInicial = 10.00;
        $saldoEsperado = $saldoInicial - $this->tarifaEstandar; 

        // Simular la Lógica de Cobro: Descuento de saldo
        $sql_update = "UPDATE USUARIO SET saldo = saldo - ? WHERE usuario_id = ?";
        $stmt_update = $this->conn->prepare($sql_update);
        $stmt_update->bind_param("di", $this->tarifaEstandar, $this->pasajeroId);
        $stmt_update->execute();

        // Aserción 1: Verificar el saldo
        $result_saldo = $this->conn->query("SELECT saldo FROM USUARIO WHERE usuario_id = {$this->pasajeroId}");
        $saldoActual = floatval($result_saldo->fetch_assoc()['saldo']);
        $this->assertEquals($saldoEsperado, $saldoActual, "El saldo final no es el esperado (7.50).");

        // Simular el Registro de Transacción
        $sql_transaccion = "INSERT INTO TRANSACCION (tipo, monto, chofer_id_cobro, fecha_hora) VALUES ('COBRO', ?, ?, NOW())";
        $stmt_transaccion = $this->conn->prepare($sql_transaccion);
        $stmt_transaccion->bind_param("di", $this->tarifaEstandar, $this->choferId);
        $stmt_transaccion->execute();

        // Aserción 2: Verificar la transacción registrada
        $result_transaccion = $this->conn->query("SELECT COUNT(*) as total FROM TRANSACCION WHERE chofer_id_cobro = {$this->choferId} AND tipo = 'COBRO'");
        $total_cobros = $result_transaccion->fetch_assoc()['total'];
        
        $this->assertEquals(1, $total_cobros, "Se debe registrar exactamente una transacción de COBRO.");
    }
}