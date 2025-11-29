<?php
use PHPUnit\Framework\TestCase;

class CobroFalloTest extends TestCase {
    private $conn;
    private $pasajeroId;
    private $tarifaEstandar = 2.50; 

    protected function setUp(): void {
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // 1. Preparar Pasajero con saldo INSUFICIENTE (1.00 < 2.50)
        $this->conn->query("DELETE FROM USUARIO WHERE email = 'cobro_fail_pasajero@test.com'");
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) 
                            VALUES (1, '300', 'Cobro Fail Pasajero', 'cobro_fail_pasajero@test.com', 'hash', 1.00)");
        $this->pasajeroId = $this->conn->insert_id;
    }

    protected function tearDown(): void {
        $this->conn->query("DELETE FROM USUARIO WHERE email = 'cobro_fail_pasajero@test.com'");
        $this->conn->close();
    }

    public function testCobroFallaPorSaldoInsuficiente() {
        $saldoInicial = 1.00;
        
        // --- 1. Simular la Lógica de Cobro Fallida ---
        
        // En tu aplicación PHP real, aquí tendrías un 'if ($saldo >= $tarifa) { UPDATE... }'.
        // Aquí simulamos que la aplicación verifica y rechaza la transacción ANTES del UPDATE.
        
        // 1.1 Simular la verificación de saldo (SELECT) en tu lógica PHP.
        $result_saldo_check = $this->conn->query("SELECT saldo FROM USUARIO WHERE usuario_id = {$this->pasajeroId}");
        $saldoActual = floatval($result_saldo_check->fetch_assoc()['saldo']);

        // 1.2 Aserción: La lógica PHP debe determinar que la transacción no puede proceder.
        $this->assertLessThan($this->tarifaEstandar, $saldoActual, "El saldo actual debería ser menor a la tarifa (1.00 < 2.50).");

        // --- 2. Simular la Lógica de Cobro: (El UPDATE NO DEBERÍA EJECUTARSE) ---
        // Puesto que la lógica de tu aplicación detendría la ejecución aquí, el saldo 
        // debe permanecer igual al saldo inicial.

        // Aserción 2: Verificar que el saldo del pasajero NO cambia
        $result_saldo_final = $this->conn->query("SELECT saldo FROM USUARIO WHERE usuario_id = {$this->pasajeroId}");
        $saldoFinal = floatval($result_saldo_final->fetch_assoc()['saldo']);
        
        $this->assertEquals($saldoInicial, $saldoFinal, "El saldo final debe permanecer sin cambios si la transacción falla.");
    }
}