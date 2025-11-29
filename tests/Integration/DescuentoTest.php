<?php
use PHPUnit\Framework\TestCase;

class DescuentoTest extends TestCase {
    private $conn;
    private $testUsuarioId = null;
    private $testEmail = 'descuento_user@test.com';
    // Asumiremos que el ID 2 corresponde a un 'Tipo Descuento' como Estudiante
    private $tipoDescuentoId = 2; 

    protected function setUp(): void {
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // --- 1. Limpieza de datos anteriores ---
        $this->conn->query("DELETE FROM USUARIO WHERE email = '{$this->testEmail}'");

        // --- 2. Preparar Pasajero ---
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo) 
                            VALUES (1, '900', 'Descuento Test', '{$this->testEmail}', '{$password_hash}', 50.00)");
        $this->testUsuarioId = $this->conn->insert_id;

        // --- 3. Limpiar cualquier validación previa ---
        $this->conn->query("DELETE FROM VALIDACION_ESPECIAL WHERE usuario_id = {$this->testUsuarioId}");
    }

    protected function tearDown(): void {
        // Limpieza de datos creados
        $this->conn->query("DELETE FROM VALIDACION_ESPECIAL WHERE usuario_id = {$this->testUsuarioId}");
        $this->conn->query("DELETE FROM USUARIO WHERE usuario_id = {$this->testUsuarioId}");
        $this->conn->close();
    }

    public function testAsignacionDescuentoExitoso() {
        // --- 1. Simular la Lógica de Inserción en VALIDACION_ESPECIAL (CORRECCIÓN FINAL) ---
        // Se corrigieron los nombres de columna a 'fecha_solicitud' y 'estado_validacion'.
        // Se corrigió el valor a 'APROBADA' (en mayúsculas) para coincidir con el ENUM de la BD.
        $sql_insert = "INSERT INTO VALIDACION_ESPECIAL (usuario_id, tipo_desc_id, fecha_solicitud, estado_validacion)
                       VALUES (?, ?, NOW(), 'APROBADA')";
        $stmt_insert = $this->conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $this->testUsuarioId, $this->tipoDescuentoId);
        $result = $stmt_insert->execute();

        // Aserción 1: Verificar que la inserción SQL fue exitosa
        $this->assertTrue($result, "La inserción de la validación especial debe ser exitosa.");

        // --- 2. Verificar que el registro existe en la BD ---
        $result_select = $this->conn->query("SELECT COUNT(*) as total FROM VALIDACION_ESPECIAL 
                                             WHERE usuario_id = {$this->testUsuarioId} AND tipo_desc_id = {$this->tipoDescuentoId} AND estado_validacion = 'APROBADA'");
        $total_registros = $result_select->fetch_assoc()['total'];
        
        // Aserción 2: Debe existir exactamente 1 registro para este descuento y usuario
        $this->assertEquals(1, $total_registros, "El registro de validación especial no se encontró en la base de datos.");
    }
}