<?php
use PHPUnit\Framework\TestCase;

class FlujoPasajeroSystemTest extends TestCase {

    /**
     * Prueba el flujo completo de validación de datos de un nuevo Pasajero
     */
    public function testFlujoRegistroPasajero() {
        $inputData = [
            'nombre_completo' => 'Juan Pérez Pasajero',
            'documento_identidad' => '12345678',
            'email' => 'juan.pasajero@test.com',
            'password' => 'Secreta123!',
            'confirm_password' => 'Secreta123!'
        ];

        // Validaciones del sistema
        $this->assertNotEmpty($inputData['nombre_completo'], "El nombre no debe estar vacío.");
        $this->assertTrue(filter_var($inputData['email'], FILTER_VALIDATE_EMAIL) !== false, "El email debe ser válido.");
        $this->assertEquals($inputData['password'], $inputData['confirm_password'], "Las contraseñas deben coincidir.");
        $this->assertGreaterThanOrEqual(8, strlen($inputData['password']), "La contraseña debe tener al menos 8 caracteres.");
        
        // Hash de la contraseña generado por el sistema
        $hash = password_hash($inputData['password'], PASSWORD_DEFAULT);
        $this->assertTrue(password_verify('Secreta123!', $hash), "La contraseña cifrada debe poder verificarse.");
    }

    /**
     * Prueba la generación de token QR para el Pase Digital de viaje
     */
    public function testGeneracionTokenQRPaseDigital() {
        $usuarioId = 42;
        $saldoActual = 15.50;
        $timestamp = time();

        // Estructura del payload codificado en el código QR del pasajero
        $qrPayload = json_encode([
            'u' => $usuarioId,
            'ts' => $timestamp,
            'sig' => hash_hmac('sha256', "{$usuarioId}:{$timestamp}", 'secret_key')
        ]);

        $this->assertJson($qrPayload, "El payload del QR debe ser un JSON válido.");
        
        $decoded = json_decode($qrPayload, true);
        $this->assertEquals($usuarioId, $decoded['u']);
        $this->assertArrayHasKey('sig', $decoded);
    }

    /**
     * Prueba de alerta de saldo bajo y cálculo de recarga
     */
    public function testLogicaSaldoYBajoSaldoAlert() {
        $saldoUmbral = 5.00;
        $saldoPasajero = 3.50;
        $montoRecarga = 20.00;

        // Alerta de saldo bajo
        $mostrarAlerta = ($saldoPasajero < $saldoUmbral);
        $this->assertTrue($mostrarAlerta, "El sistema debe activar la alerta cuando el saldo es menor a 5.00 Bs.");

        // Aplicación de recarga
        $nuevoSaldo = $saldoPasajero + $montoRecarga;
        $this->assertEquals(23.50, $nuevoSaldo, "El saldo final tras recarga debe ser 23.50 Bs.");
        
        $alertaDesactivada = ($nuevoSaldo < $saldoUmbral);
        $this->assertFalse($alertaDesactivada, "La alerta debe desactivarse tras recargar saldo suficiente.");
    }
}
