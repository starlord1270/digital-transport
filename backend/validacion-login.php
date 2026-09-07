<?php
/**
 * DIGITAL TRANSPORT - VALIDACIÓN LOGIN (PDO + RATE LIMITING)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

$response = ['success' => false, 'message' => 'Error de autenticación.', 'redirect' => ''];

// 1. LEER DATOS (JSON o POST)
$jsonData = json_decode(file_get_contents('php://input'), true);
$email = trim($jsonData['email'] ?? $_POST['email'] ?? '');
$password = $jsonData['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $response['message'] = 'Faltan campos obligatorios.';
    echo json_encode($response);
    exit;
}

// 2. RATE LIMITING POR IP / EMAIL EN BD (5 intentos cada 15 min)
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$timeWindow = 900; // 15 min
$maxAttempts = 5;

try {
    $stmtCheck = $pdo->prepare("SELECT id, intentos, TIMESTAMPDIFF(SECOND, ultimo_intento, NOW()) as transcurrido FROM INTENTOS_LOGIN WHERE ip_address = ? AND email = ?");
    $stmtCheck->execute([$ip, $email]);
    $intentData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($intentData) {
        if ((int)$intentData['transcurrido'] < $timeWindow && (int)$intentData['intentos'] >= $maxAttempts) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Por favor, intente nuevamente en 15 minutos.'
            ]);
            exit;
        }
        // Si ya pasaron los 15 minutos, se resetea automáticamente en el fallo/éxito
    }

    // 3. BUSCAR USUARIO
    $stmt = $pdo->prepare("SELECT usuario_id, password_hash, tipo_usuario_id, nombre_completo, saldo FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Credenciales correctas -> resetear intentos en BD y sesión
        $stmtClear = $pdo->prepare("DELETE FROM INTENTOS_LOGIN WHERE ip_address = ? AND email = ?");
        $stmtClear->execute([$ip, $email]);
        $_SESSION['login_attempts'] = [];
        
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$user['usuario_id'];
        $_SESSION['tipo_usuario_id'] = (int)$user['tipo_usuario_id'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['saldo'] = $user['saldo'];
        $_SESSION['logged_in'] = true;

        // Reset/generar CSRF Token de sesión
        generateCsrfToken();

        $tipo_id = (int)$user['tipo_usuario_id'];

        // 4. DETERMINAR REDIRECCIÓN
        if ($tipo_id === 4) { // ADMIN_LINEA
            $stmt = $pdo->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            $admin_data = $stmt->fetch();
            
            if ($admin_data) {
                $_SESSION['linea_id'] = (int)$admin_data['linea_id'];
                $response['success'] = true;
                $response['message'] = '¡Bienvenido Administrador!';
                $response['redirect'] = 'dashboard-admin-linea/dashboard-admin.php';
            } else {
                $response['message'] = 'Administrador sin línea asignada.';
            }

        } elseif ($tipo_id === 3) { // CHOFER
            $stmt = $pdo->prepare("SELECT estado_servicio FROM CHOFER WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            $chofer_status = $stmt->fetchColumn();

            if ($chofer_status === 'PENDIENTE') {
                $response['message'] = 'Tu cuenta aún está pendiente de validación por el administrador de la línea.';
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE CHOFER SET estado_servicio = 'ACTIVO' WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            
            $response['success'] = true;
            $response['message'] = '¡Bienvenido Chofer!';
            $response['redirect'] = 'choferes/cobro-chofer.php';
        
        } elseif ($tipo_id === 5) { // SUPER_ADMIN
            $response['success'] = true;
            $response['message'] = '¡Bienvenido Master Admin!';
            $response['redirect'] = 'dashboard-superadmin/dashboard.php';

        } else {
            // PASAJEROS
            $response['success'] = true;
            $response['message'] = '¡Inicio de sesión exitoso!';
            $response['redirect'] = 'index.php';
        }
        
    } else {
        // Registrar intento fallido en BD
        if ($intentData) {
            if ((int)$intentData['transcurrido'] >= $timeWindow) {
                $stmtUpd = $pdo->prepare("UPDATE INTENTOS_LOGIN SET intentos = 1, ultimo_intento = NOW() WHERE id = ?");
                $stmtUpd->execute([$intentData['id']]);
            } else {
                $stmtUpd = $pdo->prepare("UPDATE INTENTOS_LOGIN SET intentos = intentos + 1, ultimo_intento = NOW() WHERE id = ?");
                $stmtUpd->execute([$intentData['id']]);
            }
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO INTENTOS_LOGIN (ip_address, email, intentos, ultimo_intento) VALUES (?, ?, 1, NOW())");
            $stmtIns->execute([$ip, $email]);
        }

        $response['message'] = 'Email o Contraseña incorrectos.';
    }

} catch (PDOException $e) {
    error_log("Error en validacion-login: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Error interno en el servidor.';
}

echo json_encode($response);
?>