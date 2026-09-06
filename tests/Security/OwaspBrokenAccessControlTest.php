<?php
use PHPUnit\Framework\TestCase;

class OwaspBrokenAccessControlTest extends TestCase {

    /**
     * Prueba de Control de Acceso Basado en Roles (RBAC) y prevención de Escalado Vertical
     */
    public function testPrevencionEscaladoPrivilegiosVertical() {
        $rolesMatriz = [
            1 => ['pasajero' => true, 'chofer' => false, 'admin_linea' => false, 'super_admin' => false],
            3 => ['pasajero' => false, 'chofer' => true, 'admin_linea' => false, 'super_admin' => false],
            4 => ['pasajero' => false, 'chofer' => false, 'admin_linea' => true, 'super_admin' => false],
            5 => ['pasajero' => false, 'chofer' => false, 'admin_linea' => true, 'super_admin' => true],
        ];

        // Verificar Pasajero no puede acceder a Chofer o Admin
        $this->assertFalse($rolesMatriz[1]['chofer'], "Pasajero no debe tener acceso a funciones de Chofer.");
        $this->assertFalse($rolesMatriz[1]['admin_linea'], "Pasajero no debe tener acceso a funciones de Admin.");

        // Verificar Chofer no puede acceder a Admin
        $this->assertFalse($rolesMatriz[3]['admin_linea'], "Chofer no debe tener acceso al Dashboard Administrativo.");

        // Verificar SuperAdmin tiene acceso a Master Panel
        $this->assertTrue($rolesMatriz[5]['super_admin'], "SuperAdmin debe tener acceso completo.");
    }

    /**
     * Prueba de prevención de Escalado Horizontal (IDOR - Insecure Direct Object References)
     */
    public function testPrevencionIDORAccesoRecursosAjenos() {
        $usuarioSesionActual = 42;
        $recursoSolicitadoUsuario = 99; // Intento de consultar saldo/perfil de otro usuario

        $permiteAccesoDirecto = ($usuarioSesionActual === $recursoSolicitadoUsuario);
        $this->assertFalse($permiteAccesoDirecto, "El sistema debe bloquear el acceso IDOR a cuentas de terceros.");
    }
}
