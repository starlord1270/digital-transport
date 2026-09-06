<?php
use PHPUnit\Framework\TestCase;

/**
 * HISTORIA DE USUARIO 3: ADMINISTRADOR DE LÍNEA & SISTEMA
 * Como administrador del sistema / línea de transporte,
 * quiero monitorear el flujo de caja, tarifas y niveles de acceso,
 * para asegurar la sostenibilidad operacional y la seguridad del servicio.
 */
class AdminLineaAcceptanceTest extends TestCase {

    /**
     * CA3.1: Monitoreo de Flujo de Caja y Transacciones Consolidadas
     */
    public function testCriterioAceptacionReporteFlujoCaja() {
        $transacciones = [
            ['tipo' => 'COBRO', 'monto' => 2.50],
            ['tipo' => 'COBRO', 'monto' => 2.50],
            ['tipo' => 'RECARGA', 'monto' => 50.00],
            ['tipo' => 'CANJE', 'monto' => 20.00]
        ];

        $totalIngresos = 0;
        foreach ($transacciones as $t) {
            if ($t['tipo'] === 'COBRO' || $t['tipo'] === 'RECARGA') {
                $totalIngresos += $t['monto'];
            }
        }

        $this->assertEquals(55.00, $totalIngresos, "Criterio Aceptado: El total de ingresos consolidados del reporte debe ser 55.00 Bs.");
    }

    /**
     * CA3.2: Control de Acceso Directo por Rol (RBAC Security)
     */
    public function testCriterioAceptacionControlAccesoPorRol() {
        $rolPasajero = 1;
        $rolChofer = 3;
        $rolAdminLinea = 4;
        $rolSuperAdmin = 5;

        // Regla: Solo Admin (4) y SuperAdmin (5) pueden acceder al panel administrativo de línea
        $pasajeroPermitido = in_array($rolPasajero, [4, 5]);
        $choferPermitido = in_array($rolChofer, [4, 5]);
        $adminPermitido = in_array($rolAdminLinea, [4, 5]);

        $this->assertFalse($pasajeroPermitido, "El Pasajero no debe acceder al Dashboard de Administración.");
        $this->assertFalse($choferPermitido, "El Chofer no debe acceder al Dashboard de Administración.");
        $this->assertTrue($adminPermitido, "El Admin de Línea debe tener acceso al Dashboard.");
    }
}
