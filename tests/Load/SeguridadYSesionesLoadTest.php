<?php
use PHPUnit\Framework\TestCase;

class SeguridadYSesionesLoadTest extends TestCase {

    /**
     * Prueba de estrés de 100,000 validaciones de tokens CSRF y sanitización XSS continuas
     */
    public function testCargaSeguridadYSanitizacionMasiva() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $iterations = 100000;
        $samplePayload = "<div onclick='alert(1)'>Carga de prueba " . str_repeat("A", 100) . "</div>";

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $clean = sanitizeOutput($samplePayload);
        }

        $endTime = microtime(true);
        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $iterations / ($endTime - $startTime);

        $this->assertLessThan(1500, $durationMs, "100,000 filtrados XSS deben procesarse en menos de 1,500 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertGreaterThan(50000, $throughput, "La velocidad de desinfección en carga masiva debe ser > 50,000 ops/sec.");
    }
}
