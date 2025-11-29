<?php
use PHPUnit\Framework\TestCase;

class CanjeChoferTest extends TestCase {
    private $conn;
    private $choferUsuarioId;
    private $choferId;
    private $montoInicial = 100.00;
    private $montoCanje = 100.00;

    protected function setUp(): void {
        // Conexión a la BD de PRUEBAS (usa las constantes de phpunit.xml)
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // --- 1. Limpieza de datos anteriores ---
        $this->conn->query("DELETE FROM USUARIO WHERE email = 'canje_chofer@test.com'");

        // --- 2. Preparar Chofer: Insertar un usuario tipo CHOFER (ID 3) con saldo ---
        
        // 2.1 Insertar en USUARIO
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) 
                            VALUES (3, '400', 'Chofer Canje Test', 'canje_chofer@test.com', 'hash', {$this->montoInicial})");
        $this->choferUsuarioId = $this->conn->insert_id; // Obtiene el usuario_id insertado

        // 2.2 Insertar en CHOFER
        $this->conn->query("INSERT INTO CHOFER (usuario_id, linea_id) VALUES ({$this->choferUsuarioId}, 1)");
        $this->choferId = $this->conn->insert_id; // Obtiene el chofer_id insertado

        // 2.3 Asegurar que no haya canjes previos para este Chofer
        $this->conn->query("DELETE FROM CANJE_CHOFER WHERE chofer_id = {$this->choferId}");
    }

    protected function tearDown(): void {
        // Limpieza de datos creados en el test
        $this->conn->query("DELETE FROM CANJE_CHOFER WHERE chofer_id = {$this->choferId}");
        $this->conn->query("DELETE FROM CHOFER WHERE chofer_id = {$this->choferId}");
        $this->conn->query("DELETE FROM USUARIO WHERE usuario_id = {$this->choferUsuarioId}");
        $this->conn->close();
    }

    public function testCanjeChoferExitoso() {
        // Monto canjeado debe ser igual al saldo inicial para canje total
        $monto = $this->montoCanje; 
        $saldoEsperado = 0.00;

        // --- 1. Simular la Lógica de Canje: Descontar el saldo del USUARIO ---
        $sql_update = "UPDATE USUARIO SET saldo = saldo - ? WHERE usuario_id = ?";
        $stmt_update = $this->conn->prepare($sql_update);
        $stmt_update->bind_param("di", $monto, $this->choferUsuarioId);
        $result_update = $stmt_update->execute();

        // Aserción 1: Verificar que la consulta SQL fue exitosa
        $this->assertTrue($result_update, "La actualización de saldo en USUARIO debe ser exitosa.");

        // Aserción 2: Verificar el saldo final en la BD
        $result_saldo = $this->conn->query("SELECT saldo FROM USUARIO WHERE usuario_id = {$this->choferUsuarioId}");
        $saldoActual = floatval($result_saldo->fetch_assoc()['saldo']);
        $this->assertEquals($saldoEsperado, $saldoActual, "El saldo final del chofer debe ser 0.00 después del canje.");


        // --- 2. Simular la Lógica de Registro de Canje: Insertar en CANJE_CHOFER ---
        $sql_insert = "INSERT INTO CANJE_CHOFER (chofer_id, monto) VALUES (?, ?)";
        $stmt_insert = $this->conn->prepare($sql_insert);
        $stmt_insert->bind_param("id", $this->choferId, $monto);
        $stmt_insert->execute();

        // Aserción 3: Verificar que el registro de canje existe en la BD
        $result_canje = $this->conn->query("SELECT COUNT(*) as total FROM CANJE_CHOFER WHERE chofer_id = {$this->choferId} AND monto = {$monto}");
        $total_registros = $result_canje->fetch_assoc()['total'];
        
        $this->assertEquals(1, $total_registros, "Se debe registrar exactamente un canje por el monto correcto.");
    }
}