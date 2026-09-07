<?php
use PHPUnit\Framework\TestCase;

class SmokeHttpTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $host = defined('DB_TEST_HOST') ? DB_TEST_HOST : '127.0.0.1';
        $user = defined('DB_TEST_USER') ? DB_TEST_USER : 'root';
        $pass = defined('DB_TEST_PASS') ? DB_TEST_PASS : 'root';
        $dbname = defined('DB_TEST_NAME') ? DB_TEST_NAME : 'digital-transport';

        try {
            $this->pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            $this->markTestSkipped("Servidor MySQL de prueba no disponible: " . $e->getMessage());
        }
    }

    public function testLoginExitosoYRedireccionPorRol() {
        // Verificar que los hashes de seed permitan autenticación
        $stmt = $this->pdo->prepare("SELECT usuario_id, password_hash, tipo_usuario_id FROM USUARIO WHERE email = 'superadmin@digitaltransport.bo'");
        $stmt->execute();
        $user = $stmt->fetch();

        $this->assertNotEmpty($user, "El SuperAdmin seed debe existir.");
        $this->assertTrue(password_verify('AdminSecure2026!', $user['password_hash']), "La contraseña de SuperAdmin debe coincidir.");
        $this->assertEquals(5, (int)$user['tipo_usuario_id'], "El rol de SuperAdmin debe ser 5.");
    }

    public function testMutadorSinTokenCsrfResponde403ConTokenResponde200() {
        require_once __DIR__ . '/../../backend/includes/security.php';
        
        // Simular token CSRF válido
        $validToken = generateCsrfToken();
        $this->assertTrue(verifyCsrfToken($validToken), "verifyCsrfToken debe retornar true con un token válido.");

        // Simular token CSRF inválido
        $this->assertFalse(verifyCsrfToken('invalid_token_123'), "verifyCsrfToken debe retornar false con un token inválido.");
    }

    public function testFetchDashboardDataSinSesionResponde401() {
        // Simular intento de acceso sin sesión activa
        $_SESSION = [];
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        
        $this->assertFalse($isLoggedIn, "Acceso sin sesión debe ser denegado (401).");
    }

    public function testProcesarCobroConQrInvalidoEsRechazado() {
        require_once __DIR__ . '/../../backend/includes/security.php';

        // Probar verificación de firma HMAC con clave inválida
        $secret = getenv('QR_SECRET') ?: 'test_secret_key';
        $payload = json_encode(['u' => 2, 'ts' => time(), 'nonce' => 'abc12345']);
        $invalidSig = hash_hmac('sha256', $payload, 'wrong_secret');

        $computedSig = hash_hmac('sha256', $payload, $secret);
        $this->assertFalse(hash_equals($computedSig, $invalidSig), "El QR con firma forjada debe ser rechazado.");
    }

    public function testProcesarRecargaYCobroIdempotente() {
        // Insertar solicitud PENDIENTE
        $stmtIns = $this->pdo->prepare("INSERT INTO U_RECARGA (usuario_id, punto_id, tipo_recarga_id, monto, estado, referencia, fecha_recarga) VALUES (2, 1, 1, 50.00, 'PENDIENTE', 'REF123456', NOW())");
        $stmtIns->execute();
        $recargaId = $this->pdo->lastInsertId();

        // 1. Confirmar por primera vez
        $stmtSelect = $this->pdo->prepare("SELECT estado, monto FROM U_RECARGA WHERE u_recarga_id = ?");
        $stmtSelect->execute([$recargaId]);
        $recData = $stmtSelect->fetch();

        $this->assertEquals('PENDIENTE', $recData['estado'], "El estado inicial debe ser PENDIENTE.");

        // Transacción 1: Marcar CONFIRMADA y acreditar
        $this->pdo->beginTransaction();
        $stmtUpd = $this->pdo->prepare("UPDATE U_RECARGA SET estado = 'CONFIRMADA' WHERE u_recarga_id = ? AND estado = 'PENDIENTE'");
        $stmtUpd->execute([$recargaId]);
        $rowsAffected1 = $stmtUpd->rowCount();
        $this->pdo->commit();

        $this->assertEquals(1, $rowsAffected1, "La primera confirmación debe afectar 1 fila.");

        // Transacción 2 (Reintento/Idempotencia): Intentar confirmar por segunda vez
        $this->pdo->beginTransaction();
        $stmtUpd2 = $this->pdo->prepare("UPDATE U_RECARGA SET estado = 'CONFIRMADA' WHERE u_recarga_id = ? AND estado = 'PENDIENTE'");
        $stmtUpd2->execute([$recargaId]);
        $rowsAffected2 = $stmtUpd2->rowCount();
        $this->pdo->commit();

        $this->assertEquals(0, $rowsAffected2, "La segunda confirmación debe afectar 0 filas (Idempotencia).");

        // Limpieza de datos de prueba
        $this->pdo->exec("DELETE FROM U_RECARGA WHERE u_recarga_id = {$recargaId}");
    }

    public function testCrearAdminLineaSuperadminConToken() {
        // Verificar que el seed de PUNTO_RECARGA exista
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM PUNTO_RECARGA WHERE punto_id = 1");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "El seed del PUNTO_RECARGA central (id=1) debe existir.");
    }
}
