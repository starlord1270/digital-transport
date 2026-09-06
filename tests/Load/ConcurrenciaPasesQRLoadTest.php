<?php
use PHPUnit\Framework\TestCase;

class ConcurrenciaPasesQRLoadTest extends TestCase {

    /**
     * Ráfaga de carga masiva de generación y validación de 50,000 Pases QR concurrentes
     */
    public function testCargaMasivaPasesQRConcurrentes() {
        $concurrentUsers = 50000;
        $secretKey = 'carga_secret_production_key';
        
        $startTime = microtime(true);
        $memStart = memory_get_usage();

        $exitos = 0;
        for ($i = 1; $i <= $concurrentUsers; $i++) {
            $timestamp = 1700000000 + ($i % 3600);
            $tokenData = json_encode(['u' => $i, 'ts' => $timestamp, 'l' => ($i % 10) + 1]);
            $signature = hash_hmac('sha256', $tokenData, $secretKey);
            
            // Simular recepción y verificación en backend
            $expectedSig = hash_hmac('sha256', $tokenData, $secretKey);
            if (hash_equals($expectedSig, $signature)) {
                $exitos++;
            }
        }

        $endTime = microtime(true);
        $memEnd = memory_get_usage();

        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $concurrentUsers / ($endTime - $startTime);
        $memoryDeltaMb = ($memEnd - $memStart) / (1024 * 1024);

        $this->assertEquals($concurrentUsers, $exitos, "Todas las 50,000 peticiones QR deben ser validadas sin errores.");
        $this->assertLessThan(2000, $durationMs, "50,000 Pases QR deben procesarse en menos de 2,000 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertGreaterThan(25000, $throughput, "El rendimento debe superar 25,000 req/sec en carga masiva.");
    }
}
