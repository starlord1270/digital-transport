<?php
/**
 * DIGITAL TRANSPORT - UPDATE CHOFER STATUS (PDO)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

// Verificación de Admin (4 Admin Línea o 5 SuperAdmin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [4, 5], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$chofer_id = (int)($_POST['chofer_id'] ?? 0);
$nuevo_estado = $_POST['estado'] ?? '';
$userRole = (int)$_SESSION['tipo_usuario_id'];
$usuarioId = (int)$_SESSION['usuario_id'];

$estados_validos = ['ACTIVO', 'INACTIVO', 'LICENCIA', 'PENDIENTE', 'RECHAZADO'];

if ($chofer_id <= 0 || !in_array($nuevo_estado, $estados_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    // Para Admin de Línea (4), verificar que el chofer pertenece a su línea
    if ($userRole === 4) {
        $stmtAdmin = $pdo->prepare("SELECT linea_id FROM ADMIN_LINEA WHERE usuario_id = ?");
        $stmtAdmin->execute([$usuarioId]);
        $adminLineaId = (int)$stmtAdmin->fetchColumn();

        $stmt = $pdo->prepare("SELECT linea_id FROM CHOFER WHERE chofer_id = ?");
        $stmt->execute([$chofer_id]);
        $chofer = $stmt->fetch();

        if (!$chofer || (int)$chofer['linea_id'] !== $adminLineaId) {
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para gestionar este chofer.']);
            exit;
        }
    }

    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE CHOFER SET estado_servicio = ? WHERE chofer_id = ?");
    $stmt->execute([$nuevo_estado, $chofer_id]);

    // ENVIAR NOTIFICACIÓN POR CORREO (si hay email registrado)
    $stmt = $pdo->prepare("SELECT u.nombre_completo, u.email FROM USUARIO u JOIN CHOFER c ON u.usuario_id = c.usuario_id WHERE c.chofer_id = ?");
    $stmt->execute([$chofer_id]);
    $user_info = $stmt->fetch();

    if ($user_info && filter_var($user_info['email'], FILTER_VALIDATE_EMAIL)) {
        $to = $user_info['email'];
        $subject = "Actualización de tu cuenta - Digital Transport";
        
        $msg_status = ($nuevo_estado === 'ACTIVO') ? "APROBADA ✅" : "RECHAZADA ❌";
        $extra_msg = ($nuevo_estado === 'ACTIVO') 
            ? "Ahora puedes iniciar sesión y comenzar a trabajar." 
            : "Lamentamos informarte que tu perfil no cumple con los requisitos actuales de la línea.";

        $message = "
        <html>
        <head><title>Notificación Digital Transport</title></head>
        <body style='font-family: sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px;'>
                <h2 style='color: #0b2e88;'>Digital Transport</h2>
                <p>Hola <strong>{$user_info['nombre_completo']}</strong>,</p>
                <p>Te informamos que tu solicitud para unirte como conductor ha sido: <strong style='color: " . ($nuevo_estado === 'ACTIVO' ? '#4caf50' : '#f44336') . ";'>$msg_status</strong></p>
                <p>$extra_msg</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 0.8rem; color: #777;'>Este es un mensaje automático, por favor no respondas.</p>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@digitaltransport.com" . "\r\n";

        @mail($to, $subject, $message, $headers);
    }

    echo json_encode(['success' => true, 'message' => "Estado actualizado a $nuevo_estado y notificación enviada."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar estado en la base de datos.']);
}
?>
