<?php
use PHPUnit\Framework\TestCase;

class SecuritySanitizationBenchmarkTest extends TestCase {

    /**
     * Benchmark del procesador de seguridad para sanitización XSS y CSRF (10,000 ops)
     */
    public function testRendimientoSanitizacionSeguridad() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $iterations = 10000;
        $inputSucio = "<script>alert('Ataque XSS " . rand(1, 100) . "');</script>";

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $resultado = sanitizeOutput($inputSucio);
        }

        $endTime = microtime(true);
        $executionTimeMs = ($endTime - $startTime) * 1000;
        $throughput = $iterations / ($endTime - $startTime);

        $this->assertLessThan(150, $executionTimeMs, "10,000 sanitizaciones XSS deben ejecutarse en menos de 150 ms (Actual: " . round($executionTimeMs, 2) . " ms).");
        $this->assertGreaterThan(50000, $throughput, "El rendimiento de sanitización debe superar 50,000 ops/seg.");
    }
}
