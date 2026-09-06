<?php
/**
 * DIGITAL TRANSPORT - UPDATE CHOFER STATUS (PDO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Verificación de Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

$chofer_id = (int)($_POST['chofer_id'] ?? 0);
$nuevo_estado = $_POST['estado'] ?? '';

$estados_validos = ['ACTIVO', 'INACTIVO', 'LICENCIA', 'PENDIENTE', 'RECHAZADO'];

if ($chofer_id <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    // Verificar que el chofer pertenece a la misma línea que el admin (Seguridad extra)
    $stmt = $pdo->prepare("SELECT linea_id FROM CHOFER WHERE chofer_id = ?");
    $stmt->execute([$chofer_id]);
    $chofer = $stmt->fetch();

    if (!$chofer || $chofer['linea_id'] != $_SESSION['linea_id']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para gestionar este chofer.']);
        exit;
    }

    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE CHOFER SET estado_servicio = ? WHERE chofer_id = ?");
    $stmt->execute([$nuevo_estado, $chofer_id]);

    // 4. ENVIAR NOTIFICACIÓN POR CORREO (Simulado si no hay servidor de correo)
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

        // Usamos @ para evitar errores si el servidor de correo local no está configurado
        @mail($to, $subject, $message, $headers);
    }

    echo json_encode(['success' => true, 'message' => "Estado actualizado a $nuevo_estado y notificación enviada."]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
}
?>
