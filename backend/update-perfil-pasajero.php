<?php
/**
 * DIGITAL TRANSPORT - UPDATE PERFIL (REFACTORIZADA)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$user_id = (int)$_SESSION['usuario_id'];
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($nombre_completo) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE USUARIO SET nombre_completo = ?, email = ? WHERE usuario_id = ?");
    $stmt->execute([$nombre_completo, $email, $user_id]);

    if ($stmt->rowCount() >= 0) {
        $_SESSION['nombre_completo'] = $nombre_completo;
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil en la base de datos.']);
}
?>