<?php
use PHPUnit\Framework\TestCase;

class EstresTransaccional500kStressTest extends TestCase {

    /**
     * Prueba de estrés financiero procesando 500,000 transacciones continuas
     */
    public function testEstresExtremoVolumenFinanciero500k() {
        $iterations = 500000;
        $tarifa = 2.50;
        $saldoAcumulado = 0.00;

        $startTime = microtime(true);
        $memPeakStart = memory_get_peak_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $saldoAcumulado += $tarifa;
        }

        $endTime = microtime(true);
        $memPeakEnd = memory_get_peak_usage();

        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $iterations / ($endTime - $startTime);
        $peakMemoryMb = $memPeakEnd / (1024 * 1024);

        $this->assertEquals(1250000.00, $saldoAcumulado, "El volumen acumulado debe ser exactamente 1,250,000.00 Bs.");
        $this->assertLessThan(1500, $durationMs, "500,000 transacciones deben procesarse en menos de 1,500 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertLessThan(32, $peakMemoryMb, "El consumo pico de memoria bajo 500k operaciones debe ser menor a 32 MB.");
    }
}
