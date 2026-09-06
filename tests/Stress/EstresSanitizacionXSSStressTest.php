<?php
use PHPUnit\Framework\TestCase;

class EstresSanitizacionXSSStressTest extends TestCase {

    /**
     * Prueba de estrés de 500,000 filtrados de seguridad XSS contra consumo de memoria
     */
    public function testEstresExtremoSanitizacionXSS500k() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $iterations = 500000;
        $payloadIntenso = "<script>alert('Ataque Inyección " . rand(1, 1000) . "');</script><iframe src='http://malicious.test'></iframe>";

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $resultado = sanitizeOutput($payloadIntenso);
        }

        $endTime = microtime(true);
        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $iterations / ($endTime - $startTime);

        $this->assertLessThan(2000, $durationMs, "500,000 filtrados XSS deben procesarse en menos de 2,000 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertGreaterThan(100000, $throughput, "La velocidad de desinfección bajo estrés debe superar 100,000 ops/sec.");
    }
}
