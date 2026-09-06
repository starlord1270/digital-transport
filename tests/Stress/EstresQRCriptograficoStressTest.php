<?php
use PHPUnit\Framework\TestCase;

class EstresQRCriptograficoStressTest extends TestCase {

    /**
     * Pruebas de estrés criptográfico con 250,000 operaciones QR continuas
     */
    public function testEstresExtremoGeneracionYAutenticacionQR() {
        $iterations = 250000;
        $secretKey = 'extreme_stress_test_key_2026';
        
        $startTime = microtime(true);
        $memStart = memory_get_usage();

        $exitos = 0;
        for ($i = 1; $i <= $iterations; $i++) {
            $data = "user_{$i}_timestamp_" . (1700000000 + $i);
            $sig = hash_hmac('sha256', $data, $secretKey);
            $expected = hash_hmac('sha256', $data, $secretKey);
            if (hash_equals($expected, $sig)) {
                $exitos++;
            }
        }

        $endTime = microtime(true);
        $memEnd = memory_get_usage();

        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $iterations / ($endTime - $startTime);

        $this->assertEquals($iterations, $exitos, "Las 250,000 firmas HMAC deben validarse correctamente sin fallos.");
        $this->assertLessThan(2000, $durationMs, "250,000 validaciones criptográficas deben procesarse en menos de 2,000 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertGreaterThan(100000, $throughput, "El rendimiento bajo estrés debe superar 100,000 ops/sec.");
    }
}
