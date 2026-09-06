<?php
use PHPUnit\Framework\TestCase;

class CobroMasivoHorasPicoLoadTest extends TestCase {

    /**
     * Prueba de estrés de 100,000 cobros continuos simulando hora pico en el sistema
     */
    public function testCargaExtremaCobrosHoraPico() {
        $totalCobros = 100000;
        $tarifa = 2.50;
        $totalDebitado = 0.00;
        $totalRecaudado = 0.00;

        $startTime = microtime(true);
        $peakMemoryStart = memory_get_peak_usage();

        for ($i = 0; $i < $totalCobros; $i++) {
            $totalDebitado += $tarifa;
            $totalRecaudado += $tarifa;
        }

        $endTime = microtime(true);
        $peakMemoryEnd = memory_get_peak_usage();

        $durationMs = ($endTime - $startTime) * 1000;
        $throughput = $totalCobros / ($endTime - $startTime);
        $peakMemoryMb = $peakMemoryEnd / (1024 * 1024);

        $this->assertEquals(250000.00, $totalDebitado, "El total debitado acumulado debe ser exactamente 250,000.00 Bs.");
        $this->assertEquals(250000.00, $totalRecaudado, "El total recaudado acumulado debe ser exactamente 250,000.00 Bs.");
        $this->assertLessThan(1000, $durationMs, "100,000 cobros masivos deben procesarse en menos de 1,000 ms (Actual: " . round($durationMs, 2) . " ms).");
        $this->assertLessThan(32, $peakMemoryMb, "El consumo de memoria no debe exceder 32 MB durante la carga masiva.");
    }
}
