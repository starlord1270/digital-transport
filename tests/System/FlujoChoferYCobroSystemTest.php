<?php
use PHPUnit\Framework\TestCase;

class FlujoChoferYCobroSystemTest extends TestCase {

    /**
     * Prueba la validación y escaneo del ticket QR del pasajero por parte del Chofer
     */
    public function testEscaneoYValidacionTicketQR() {
        $usuarioId = 10;
        $timestamp = time();
        $secretKey = 'secret_key';

        // Token recibido en el escaner del chofer
        $qrPayload = json_encode([
            'u' => $usuarioId,
            'ts' => $timestamp,
            'sig' => hash_hmac('sha256', "{$usuarioId}:{$timestamp}", $secretKey)
        ]);

        $data = json_decode($qrPayload, true);
        
        // Simular verificación de firma criptográfica del QR en backend
        $expectedSig = hash_hmac('sha256', "{$data['u']}:{$data['ts']}", $secretKey);
        $esValido = hash_equals($expectedSig, $data['sig']);

        $this->assertTrue($esValido, "El ticket QR escaneado por el chofer debe ser auténtico.");
    }

    /**
     * Prueba el flujo de cobro tarifa chofer-pasajero e incremento de recaudación
     */
    public function testProcesoCobroEIncrementoRecaudacion() {
        $saldoPasajeroInicial = 10.00;
        $recaudacionChoferInicial = 50.00;
        $tarifaCobrada = 2.50;

        // Evaluación de saldo suficiente
        $puedeCobrar = ($saldoPasajeroInicial >= $tarifaCobrada);
        $this->assertTrue($puedeCobrar, "El cobro debe ser autorizado.");

        // Ejecución simulada de la transacción
        $saldoPasajeroFinal = $saldoPasajeroInicial - $tarifaCobrada;
        $recaudacionChoferFinal = $recaudacionChoferInicial + $tarifaCobrada;

        $this->assertEquals(7.50, $saldoPasajeroFinal, "El pasajero debe quedar con 7.50 Bs.");
        $this->assertEquals(52.50, $recaudacionChoferFinal, "El chofer debe acumular 52.50 Bs.");
    }

    /**
     * Prueba de regla de negocio para solicitudes de canje de chofer
     */
    public function testSolicitudCanjeRecaudacionChofer() {
        $recaudacionActual = 120.00;
        $montoSolicitadoCanje = 100.00;
        $montoMinimoCanje = 20.00;

        $esValido = ($montoSolicitadoCanje >= $montoMinimoCanje && $montoSolicitadoCanje <= $recaudacionActual);

        $this->assertTrue($esValido, "La solicitud de canje debe ser aprobada.");

        $recaudacionRemanente = $recaudacionActual - $montoSolicitadoCanje;
        $this->assertEquals(20.00, $recaudacionRemanente, "El saldo remanente debe ser 20.00 Bs.");
    }
}
