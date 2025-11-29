<?php
// Usamos el namespace de PHPUnit para poder extender la clase TestCase
use PHPUnit\Framework\TestCase;

// El nombre de la clase debe terminar en 'Test'
class SeguridadTest extends TestCase 
{
    /**
     * Prueba que las contraseñas se hasheen correctamente y sean verificables.
     */
    public function testPasswordHashingAndVerification()
    {
        $password_raw = "MiClaveSuperSecreta123";
        // 1. Hashear la contraseña (simulando tu lógica en registro_usuario.php)
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

        // Aserción 1: Verificar que el hash no es la contraseña en texto plano
        $this->assertNotEquals($password_raw, $password_hash, 
            "El hash NO debe ser igual al texto plano de la contraseña.");

        // Aserción 2: Verificar que password_verify() funciona con la contraseña correcta
        $this->assertTrue(
            password_verify($password_raw, $password_hash),
            "El hash debe ser verificable usando la contraseña original."
        );
        
        // Aserción 3: Verificar que password_verify() falla con una contraseña incorrecta
        $this->assertFalse(
            password_verify("ClaveIncorrecta", $password_hash),
            "El hash debe fallar la verificación con una contraseña incorrecta."
        );
    }
    
    /**
     * Prueba simple de validación de formato de correo electrónico.
     */
    public function testEmailFormatValidation()
    {
        // Prueba de email válido
        $this->assertTrue(filter_var("usuario@dominio.com", FILTER_VALIDATE_EMAIL) !== false, 
            "El email 'usuario@dominio.com' debe ser válido.");

        // Prueba de email inválido
        $this->assertFalse(filter_var("correo-invalido@", FILTER_VALIDATE_EMAIL) !== false, 
            "El email 'correo-invalido@' debe ser inválido.");
    }
}