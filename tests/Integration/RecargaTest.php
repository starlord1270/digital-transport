<?php
use PHPUnit\Framework\TestCase;

// Importante: La conexión a la BD de pruebas ya está establecida gracias a phpunit.xml

class RecargaTest extends TestCase {
    private $conn;
    private $testEmail = 'recarga_test_user@example.com';
    private $testDoc = '111222333';
    private $testUsuarioId = null;

    protected function setUp(): void {
        // Conexión a la DB de PRUEBAS
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // Limpieza de datos y preparación de un usuario con saldo 0.00
        $this->conn->query("DELETE FROM USUARIO WHERE documento_identidad = '{$this->testDoc}'");
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo, fecha_registro)
                            VALUES (1, '{$this->testDoc}', 'Usuario Recarga', '{$this->testEmail}', '{$password_hash}', 0.00, NOW())");
        
        // Obtener el ID del usuario
        $result = $this->conn->query("SELECT usuario_id FROM USUARIO WHERE documento_identidad = '{$this->testDoc}'");
        $this->testUsuarioId = $result->fetch_assoc()['usuario_id'];
    }

    protected function tearDown(): void {
        // Limpieza final de datos
        $this->conn->query("DELETE FROM USUARIO WHERE documento_identidad = '{$this->testDoc}'");
        $this->conn->close();
    }

    public function testRecargaAumentaElSaldoYRegistraTransaccion() {
        $monto_recarga = 50.00;

        // --- 1. Lógica de Recarga: Actualizar el saldo ---
        $sql_update = "UPDATE USUARIO SET saldo = saldo + ? WHERE usuario_id = ?";
        $stmt_update = $this->conn->prepare($sql_update);
        $stmt_update->bind_param("di", $monto_recarga, $this->testUsuarioId);
        $stmt_update->execute();

        // Aserción 1: Verificar que el saldo en la BD es el esperado (50.00)
        $result_saldo = $this->conn->query("SELECT saldo FROM USUARIO WHERE usuario_id = {$this->testUsuarioId}");
        $saldo_actual = $result_saldo->fetch_assoc()['saldo'];
        $this->assertEquals(50.00, floatval($saldo_actual), "El saldo final debe ser 50.00.");


        // --- 2. Lógica de Registro de Transacción ---
        // (Simula la inserción en la tabla TRANSACCION y U_RECARGA)
        $sql_transaccion = "INSERT INTO TRANSACCION (tipo, monto, fecha_hora) VALUES (?, ?, NOW())";
        $tipo = 'RECARGA';
        $stmt_transaccion = $this->conn->prepare($sql_transaccion);
        $stmt_transaccion->bind_param("sd", $tipo, $monto_recarga);
        $stmt_transaccion->execute();

        // Aserción 2: Verificar que se registró la transacción
        $result_transaccion = $this->conn->query("SELECT COUNT(*) as total FROM TRANSACCION WHERE tipo = 'RECARGA' AND monto = {$monto_recarga}");
        $total_transacciones = $result_transaccion->fetch_assoc()['total'];
        
        $this->assertTrue($total_transacciones > 0, "Debe existir al menos una transacción de recarga.");
    }
}