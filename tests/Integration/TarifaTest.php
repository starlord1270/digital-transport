<?php
use PHPUnit\Framework\TestCase;

class TarifaTest extends TestCase {
    private $pdo;
    private $tarifaIdEstandar = 1; 
    private $tarifaOriginal = 2.50;

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

        $stmt = $this->pdo->prepare("UPDATE TARIFA SET monto = ? WHERE tipo_desc_id = ?");
        $stmt->execute([$this->tarifaOriginal, $this->tarifaIdEstandar]);
    }

    protected function tearDown(): void {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("UPDATE TARIFA SET monto = ? WHERE tipo_desc_id = ?");
            $stmt->execute([$this->tarifaOriginal, $this->tarifaIdEstandar]);
        }
    }

    public function testActualizacionTarifaEstandarExitoso() {
        $nuevaTarifa = 3.00;

        $stmt_update = $this->pdo->prepare("UPDATE TARIFA SET monto = ? WHERE tipo_desc_id = ?");
        $result = $stmt_update->execute([$nuevaTarifa, $this->tarifaIdEstandar]);

        $this->assertTrue($result, "La actualización de la tarifa debe ser exitosa.");

        $stmt_select = $this->pdo->prepare("SELECT monto FROM TARIFA WHERE tipo_desc_id = ?");
        $stmt_select->execute([$this->tarifaIdEstandar]);
        $tarifaActual = floatval($stmt_select->fetch()['monto']);
        
        $this->assertEquals($nuevaTarifa, $tarifaActual, "El monto de la tarifa no se actualizó correctamente a {$nuevaTarifa}.");
    }
}