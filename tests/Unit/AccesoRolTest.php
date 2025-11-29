<?php
use PHPUnit\Framework\TestCase;

// Definición de roles basada en la tabla TIPO_USUARIO de tu BD
// Asumo la siguiente jerarquía de IDs (cuanto mayor el ID, mayor el acceso): 
// 1 = Pasajero (Estandar), 3 = Chofer, 4 = Administrador

class AccesoRolTest extends TestCase 
{
    // --- 1. FUNCIÓN DE ACCESO (SIMULADA) ---
    /**
     * Simula la lógica de tu 'middleware' (archivo PHP que verifica el rol)
     * para determinar si el rol actual del usuario tiene el permiso necesario.
     * En este modelo, un rol superior (ID mayor) puede acceder a un rol inferior.
     */
    private function checkAccess(int $user_role_id, int $required_role_id): bool
    {
        // El usuario tiene permiso si su ID de rol es igual o mayor al ID de rol requerido.
        if ($user_role_id >= $required_role_id) {
            return true;
        }
        
        return false;
    }

    // --- 2. TESTS DE ACCESO EXITOSO ---

    public function testAdminPuedeAccederARecursosDeAdmin()
    {
        // Administrador (4) requiere Administrador (4)
        $this->assertTrue($this->checkAccess(4, 4), "El Administrador (4) debe tener acceso a sus propias páginas.");
    }

    public function testChoferPuedeAccederARecursosDeChofer()
    {
        // Chofer (3) requiere Chofer (3)
        $this->assertTrue($this->checkAccess(3, 3), "El Chofer (3) debe tener acceso a sus propias páginas.");
    }

    public function testAdminPuedeAccederARecursosDeChofer()
    {
        // Administrador (4) requiere Chofer (3)
        $this->assertTrue($this->checkAccess(4, 3), "El Administrador (4) debe poder acceder a recursos de Chofer (3).");
    }


    // --- 3. TESTS DE ACCESO RESTRINGIDO (Fallo) ---

    public function testChoferNoPuedeAccederARecursosDeAdmin()
    {
        // Chofer (3) requiere Administrador (4)
        $this->assertFalse($this->checkAccess(3, 4), "El Chofer (3) NO debe tener acceso a las páginas de Administrador (4).");
    }
    
    public function testPasajeroNoPuedeAccederARecursosDeChofer()
    {
        // Pasajero (1) requiere Chofer (3)
        $this->assertFalse($this->checkAccess(1, 3), "El Pasajero (1) NO debe tener acceso a las páginas de Chofer (3).");
    }
    
    public function testPasajeroNoPuedeAccederARecursosDeAdmin()
    {
        // Pasajero (1) requiere Administrador (4)
        $this->assertFalse($this->checkAccess(1, 4), "El Pasajero (1) NO debe tener acceso a las páginas de Administrador (4).");
    }
}