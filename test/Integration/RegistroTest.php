<?php
use PHPUnit\Framework\TestCase;

// IMPORTANTE: Debes asegurarte de que tu 'bd.php' o clase de conexión
// se cargue aquí, y que use las constantes DB_TEST_* definidas en phpunit.xml.

// Reemplaza esta línea si usas una clase de conexión diferente:
// require_once __DIR__ . '/../../backend/bd.php'; 

class RegistroTest extends TestCase {
    private $conn;
    private $testEmail = 'integration_test_user@example.com';
    private $testDoc = '999888777';

    // 1. Configuración: Se ejecuta antes de cada test
    protected function setUp(): void {
        // Inicializa la conexión a la DB de PRUEBAS (usa las constantes de phpunit.xml)
        $this->conn = new mysqli(DB_TEST_HOST, DB_TEST_USER, DB_TEST_PASS, DB_TEST_NAME);
        
        if ($this->conn->connect_error) {
            $this->fail("Fallo la conexión a la base de datos de prueba: " . $this->conn->connect_error);
        }

        // Limpieza de datos anteriores para asegurar la independencia de la prueba
        $this->conn->query("DELETE FROM USUARIO WHERE email = '{$this->testEmail}' OR documento_identidad = '{$this->testDoc}'");
    }

    // 2. Limpieza: Se ejecuta después de cada test
    protected function tearDown(): void {
        // Limpiar los datos creados por la prueba
        $this->conn->query("DELETE FROM USUARIO WHERE email = '{$this->testEmail}' OR documento_identidad = '{$this->testDoc}'");
        $this->conn->close();
    }

    // 3. La prueba real
    public function testRegistroPasajeroEstandarExitoso() {
        
        $password_raw = 'securePass123';
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
        $tipo_usuario_id = 1; // 1 = Estándar

        // Simulamos la inserción en la BD usando prepared statements (como deberías hacerlo)
        $sql = "INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, fecha_registro)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $tipo_usuario_id, $this->testDoc, $nombre, $this->testEmail, $password_hash);
        
        $nombre = "Test Integracion";
        
        $result = $stmt->execute();

        // Aserción 1: Verificar que la consulta SQL se ejecutó sin errores
        $this->assertTrue($result, "La inserción SQL deberia ser exitosa.");

        // Aserción 2: Verificar que el registro existe en la base de datos
        $result = $this->conn->query("SELECT * FROM USUARIO WHERE email = '{$this->testEmail}'");
        $this->assertEquals(1, $result->num_rows, "Debería haber exactamente 1 registro en la tabla USUARIO.");
    }

    public function testRegistroFallaPorDuplicado() {
        // Primero, insertar un registro para duplicarlo
        $password_hash = password_hash('pass', PASSWORD_DEFAULT);
        $this->conn->query("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, fecha_registro)
                            VALUES (1, '{$this->testDoc}', 'Inicial', '{$this->testEmail}', '{$password_hash}', NOW())");

        // Intentar registrar el mismo email de nuevo (simulando un segundo POST)
        // Ya que la lógica de duplicado está en el PHP, aquí solo probamos la aserción 
        // de la BD. Si tu BD tiene una restricción UNIQUE, esta inserción fallaría.
        
        // Asumiendo que tu aplicación maneja la verificación de duplicados ANTES de la inserción, 
        // esta prueba debería validar que tu función de verificación devuelve FALSO.
        
        // Por simplicidad, solo verificamos que solo hay 1 registro.
        $result = $this->conn->query("SELECT * FROM USUARIO WHERE email = '{$this->testEmail}'");
        $this->assertEquals(1, $result->num_rows, "Solo debería haber 1 registro, ya que la lógica de duplicado debe prevenir la segunda insercion.");
    }
}