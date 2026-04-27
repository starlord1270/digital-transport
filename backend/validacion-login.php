<?php
/**
 * DIGITAL TRANSPORT - VALIDACIÓN LOGIN (REFACTORIZADA A PDO)
 */
header('Content-Type: application/json');
require_once 'includes/db.php';

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

try {
    // 2. BUSCAR USUARIO
    $stmt = $pdo->prepare("SELECT usuario_id, password_hash, tipo_usuario_id, nombre_completo, saldo FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Credenciales correctas
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['tipo_usuario_id'] = $user['tipo_usuario_id'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['saldo'] = $user['saldo'];
        $_SESSION['logged_in'] = true;

        $tipo_id = $user['tipo_usuario_id'];

        // 3. DETERMINAR REDIRECCIÓN
        if ($tipo_id == 4) { // ADMIN_LINEA
            $stmt = $pdo->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            $admin_data = $stmt->fetch();
            
            if ($admin_data) {
                $_SESSION['linea_id'] = $admin_data['linea_id'];
                $response['success'] = true;
                $response['message'] = '¡Bienvenido Administrador!';
                $response['redirect'] = 'dashboard-admin-linea/dashboard-admin.php';
            } else {
                $response['message'] = 'Administrador sin línea asignada.';
            }

        } elseif ($tipo_id == 3) { // CHOFER
            // Verificar si el chofer está validado
            $stmt = $pdo->prepare("SELECT estado_servicio FROM CHOFER WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            $chofer_status = $stmt->fetchColumn();

            if ($chofer_status === 'PENDIENTE') {
                $response['message'] = 'Tu cuenta aún está pendiente de validación por el administrador de la línea.';
                echo json_encode($response);
                exit;
            }

            // Si está validado, se pone en ACTIVO al iniciar sesión
            $stmt = $pdo->prepare("UPDATE CHOFER SET estado_servicio = 'ACTIVO' WHERE usuario_id = ?");
            $stmt->execute([$user['usuario_id']]);
            
            $response['success'] = true;
            $response['message'] = '¡Bienvenido Chofer!';
            $response['redirect'] = 'choferes/cobro-chofer.php';
        
        } elseif ($tipo_id == 5) { // SUPER_ADMIN
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
        $response['message'] = 'Email o Contraseña incorrectos.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Error de servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>