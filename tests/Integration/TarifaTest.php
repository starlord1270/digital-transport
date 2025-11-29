<?php
use PHPUnit\Framework\TestCase;

class TarifaTest extends TestCase {
    private $conn;
    // Asumiremos que el ID 1 corresponde a la tarifa Adulto Estándar que se cobra normalmente.
    private $tarifaIdEstandar = 1; 
    private $tarifaOriginal = 2.50; // Valor original de la tarifa Adulto Estándar

    protected function setUp(): void {
        // Inicializar la conexión a la BD de PRUEBAS
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        // Asegurar que el valor inicial sea 2.50 antes de ejecutar la prueba
        $this->conn->query("UPDATE TARIFA SET monto = {$this->tarifaOriginal} WHERE tipo_desc_id = {$this->tarifaIdEstandar}");
    }

    protected function tearDown(): void {
        // Limpieza: Restablecer la tarifa a su valor original para no afectar otras pruebas
        $this->conn->query("UPDATE TARIFA SET monto = {$this->tarifaOriginal} WHERE tipo_desc_id = {$this->tarifaIdEstandar}");
        $this->conn->close();
    }

    public function testActualizacionTarifaEstandarExitoso() {
        $nuevaTarifa = 3.00;

        // --- 1. Simular la Lógica del Administrador: UPDATE en la tabla TARIFA ---
        // (Tu script 'actualizar_tarifa.php' o similar haría esto)
        $sql_update = "UPDATE TARIFA SET monto = ? WHERE tipo_desc_id = ?";
        $stmt_update = $this->conn->prepare($sql_update);
        $stmt_update->bind_param("di", $nuevaTarifa, $this->tarifaIdEstandar);
        $result = $stmt_update->execute();

        // Aserción 1: Verificar que la consulta SQL fue exitosa
        $this->assertTrue($result, "La actualización de la tarifa debe ser exitosa.");

        // --- 2. Verificar que el valor se actualizó en la BD ---
        $result_select = $this->conn->query("SELECT monto FROM TARIFA WHERE tipo_desc_id = {$this->tarifaIdEstandar}");
        $tarifaActual = floatval($result_select->fetch_assoc()['monto']);
        
        // Aserción 2: El monto en la BD debe ser el nuevo valor (3.00)
        $this->assertEquals($nuevaTarifa, $tarifaActual, "El monto de la tarifa no se actualizó correctamente a {$nuevaTarifa}.");
    }
}