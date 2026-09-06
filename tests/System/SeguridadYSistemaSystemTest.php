<?php
use PHPUnit\Framework\TestCase;

class SeguridadYSistemaSystemTest extends TestCase {

    /**
     * Prueba la carga de variables de entorno del sistema y modos
     */
    public function testCargaVariablesEntorno() {
        $envFile = __DIR__ . '/../../.env';
        $this->assertFileExists($envFile, "El archivo .env de configuración del sistema debe existir.");

        $appEnv = getenv('APP_ENV') ?: 'production';
        $this->assertContains($appEnv, ['local', 'testing', 'production'], "APP_ENV debe tener un entorno válido.");
    }

    /**
     * Prueba la generación y validación de tokens CSRF del sistema
     */
    public function testGeneracionYValidacionCSRF() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $token1 = getCsrfToken();
        $this->assertNotEmpty($token1, "El token CSRF generado no debe ser vacío.");
        $this->assertEquals(64, strlen($token1), "El token CSRF debe tener 64 caracteres hexadecimales.");

        $esValido = verifyCsrfToken($token1);
        $this->assertTrue($esValido, "El token CSRF debe ser válido.");

        $esInvalido = verifyCsrfToken('token_falso_invalid_123');
        $this->assertFalse($esInvalido, "Un token CSRF alterado debe ser rechazado.");
    }

    /**
     * Prueba la prevención de ataques XSS mediante el sanitizador del sistema
     */
    public function testSanitizacionSalidaXSS() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $inputPeligroso = "<script>alert('hack');</script>";
        $outputLimpio = sanitizeOutput($inputPeligroso);

        $this->assertStringNotContainsString("<script>", $outputLimpio, "El script HTML debe ser sanitizado.");
        $this->assertEquals("&lt;script&gt;alert(&#039;hack&#039;);&lt;/script&gt;", $outputLimpio, "Los caracteres especiales deben convertirse en entidades HTML.");
    }
}
