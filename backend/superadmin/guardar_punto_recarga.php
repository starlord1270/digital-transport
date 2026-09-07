<?php
/**
 * DIGITAL TRANSPORT - GUARDAR PUNTO DE RECARGA + OPERADOR (SUPER ADMIN)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (int)($_SESSION['tipo_usuario_id'] ?? 0) !== 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente.']);
    exit;
}

$nombre_punto = trim($_POST['nombre_punto'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$nombre_operador = trim($_POST['nombre_operador'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($nombre_punto) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Por favor complete los campos obligatorios.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 0. Verificar si el email ya existe
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM USUARIO WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => "El correo '$email' ya está registrado en el sistema."]);
        exit;
    }

    // 1. Crear Usuario Operador (tipo_usuario_id = 2)
    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO USUARIO (nombre_completo, email, password_hash, tipo_usuario_id, documento_identidad) VALUES (?, ?, ?, 2, ?)");
    $stmtUser->execute([$nombre_operador, $email, $pass_hash, 'PUNTO-'.time()]);
    $usuario_id = $pdo->lastInsertId();

    // 2. Crear Punto de Recarga
    $stmtPunto = $pdo->prepare("INSERT INTO PUNTO_RECARGA (nombre, ubicacion, usuario_id, estado) VALUES (?, ?, ?, 'ACTIVO')");
    $stmtPunto->execute([$nombre_punto, $ubicacion, $usuario_id]);

    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'NUEVO_PUNTO_RECARGA', "Se habilitó el punto '$nombre_punto' operado por $nombre_operador");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Punto habilitado correctamente.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en guardar_punto_recarga: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al registrar el punto de recarga en la base de datos.']);
}
?>
