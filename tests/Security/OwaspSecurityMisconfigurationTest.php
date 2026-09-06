<?php
use PHPUnit\Framework\TestCase;

class OwaspSecurityMisconfigurationTest extends TestCase {

    /**
     * Prueba de existencia y reglas de protección del servidor web (.htaccess)
     */
    public function testProteccionArchivosSensiblesHtaccess() {
        $htaccessPath = __DIR__ . '/../../.htaccess';
        $this->assertFileExists($htaccessPath, "El archivo de reglas de seguridad .htaccess debe existir.");

        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString(".env", $content, "Debe denegar acceso público a .env");
        $this->assertStringContainsString(".git", $content, "Debe denegar acceso público a .git");
        $this->assertStringContainsString("composer", $content, "Debe denegar acceso a composer.json");
        $this->assertStringContainsString("Options -Indexes", $content, "Debe deshabilitar el listado de directorios.");
    }

    /**
     * Prueba de ausencia de scripts de depuración con credenciales expuestas
     */
    public function testAusenciaArchivosDepuracionExpuestos() {
        $filesToCheck = [
            __DIR__ . '/../../prueba_conexion.php',
            __DIR__ . '/../../backend/test_db.php'
        ];

        foreach ($filesToCheck as $filePath) {
            $this->assertFileDoesNotExist($filePath, "El script de depuración expuesto '{$filePath}' debe estar eliminado en producción.");
        }
    }
}
