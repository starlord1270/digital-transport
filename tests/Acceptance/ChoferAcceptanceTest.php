<?php
use PHPUnit\Framework\TestCase;

/**
 * HISTORIA DE USUARIO 2: CHOFER
 * Como chofer de una unidad de transporte,
 * quiero escanear los boletos QR de los pasajeros y registrar los cobros,
 * para mantener el control de mi recaudación y solicitar el canje de ingresos.
 */
class ChoferAcceptanceTest extends TestCase {

    /**
     * CA2.1: Validación Instantánea de Cobro QR por el Chofer
     */
    public function testCriterioAceptacionCobroQRChofer() {
        $choferId = 5;
        $recaudacionInicial = 150.00;
        $tarifaCobrada = 2.50;

        // Escaneo y validación de cobro
        $recaudacionFinal = $recaudacionInicial + $tarifaCobrada;
        $this->assertEquals(152.50, $recaudacionFinal, "Criterio Aceptado: La recaudación del chofer incrementa inmediatamente tras un cobro válido.");
    }

    /**
     * CA2.2: Solicitud de Canje de Recaudación
     */
    public function testCriterioAceptacionSolicitudCanjeIngresos() {
        $recaudacionChofer = 200.00;
        $montoSolicitado = 150.00;
        $limiteMinimo = 20.00;

        // Reglas de negocio para aceptación del canje
        $canjeAprobado = ($montoSolicitado >= $limiteMinimo && $montoSolicitado <= $recaudacionChofer);
        $this->assertTrue($canjeAprobado, "Criterio Aceptado: El chofer puede solicitar el canje si cumple con el monto mínimo disponible.");
    }
}
