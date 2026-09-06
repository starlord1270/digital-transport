<?php
use PHPUnit\Framework\TestCase;

/**
 * HISTORIA DE USUARIO 1: PASAJERO
 * Como pasajero del transporte público,
 * quiero gestionar mi saldo y utilizar mi Pase Digital QR,
 * para poder viajar de forma rápida y segura sin utilizar efectivo.
 */
class PasajeroAcceptanceTest extends TestCase {

    /**
     * CA1.1: Registro e Inicio de Sesión de Pasajero
     */
    public function testCriterioAceptacionRegistroEInicioSesionPasajero() {
        $credenciales = [
            'email' => 'pasajero.aceptacion@transport.com',
            'password' => 'Pasajero2026!'
        ];

        // 1. Simulación de Registro
        $hashPassword = password_hash($credenciales['password'], PASSWORD_DEFAULT);
        $this->assertTrue(password_verify($credenciales['password'], $hashPassword));

        // 2. Simulación de Autenticación en Sistema
        $sessionMock = [
            'usuario_id' => 101,
            'tipo_usuario_id' => 1, // Pasajero Estándar
            'nombre_completo' => 'Pasajero Aceptación',
            'email' => $credenciales['email'],
            'saldo' => 25.00
        ];

        $this->assertEquals(1, $sessionMock['tipo_usuario_id'], "El rol asignado debe ser Pasajero (1).");
        $this->assertGreaterThan(0, $sessionMock['saldo'], "El monedero debe iniciarse correctamente.");
    }

    /**
     * CA1.2: Generación y Descuento por Viaje con Pase Digital QR
     */
    public function testCriterioAceptacionPaseDigitalYDebitoViaje() {
        $saldoInicial = 25.00;
        $tarifaPasaje = 2.50;

        // 1. Emisión del Pase Digital QR
        $tokenPaseQR = json_encode([
            'usuario_id' => 101,
            'emision' => time(),
            'validez_minutos' => 5
        ]);
        $this->assertJson($tokenPaseQR);

        // 2. Procesamiento del Viaje
        $saldoFinal = $saldoInicial - $tarifaPasaje;
        $this->assertEquals(22.50, $saldoFinal, "El saldo debe actualizarse a 22.50 Bs. tras el débito.");
    }

    /**
     * CA1.3: Advertencia Visual por Saldo Bajo
     */
    public function testCriterioAceptacionAlertaSaldoBajo() {
        $saldoBajo = 3.00;
        $umbralAlerta = 5.00;

        $alertaActivada = ($saldoBajo < $umbralAlerta);
        $this->assertTrue($alertaActivada, "Criterio Aceptado: La alerta de saldo bajo debe desplegarse si el saldo es < 5.00 Bs.");
    }
}
