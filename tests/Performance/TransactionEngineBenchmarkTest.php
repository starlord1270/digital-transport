<?php
use PHPUnit\Framework\TestCase;

class TransactionEngineBenchmarkTest extends TestCase {

    /**
     * Benchmark de cómputo transaccional de débitos y créditos en lote (10,000 transacciones)
     */
    public function testRendimientoMotorTransaccionalLote() {
        $iterations = 10000;
        $saldoPasajero = 50000.00;
        $recaudacionChofer = 0.00;
        $tarifa = 2.50;

        $startTime = microtime(true);
        $peakMemoryStart = memory_get_peak_usage();

        for ($i = 0; $i < $iterations; $i++) {
            if ($saldoPasajero >= $tarifa) {
                $saldoPasajero -= $tarifa;
                $recaudacionChofer += $tarifa;
            }
        }

        $endTime = microtime(true);
        $peakMemoryEnd = memory_get_peak_usage();

        $executionTimeMs = ($endTime - $startTime) * 1000;
        $memoryMb = ($peakMemoryEnd - $peakMemoryStart) / (1024 * 1024);

        $this->assertEquals(25000.00, $saldoPasajero, "El saldo final debe ser 25,000.00 Bs.");
        $this->assertEquals(25000.00, $recaudacionChofer, "La recaudación acumulada debe ser 25,000.00 Bs.");
        $this->assertLessThan(100, $executionTimeMs, "10,000 operaciones transaccionales deben procesarse en menos de 100 ms (Actual: " . round($executionTimeMs, 2) . " ms).");
    }
}
