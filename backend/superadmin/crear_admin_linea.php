<?php
/**
 * DIGITAL TRANSPORT - CREAR ADMIN DE LÍNEA (SUPER ADMIN)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// Verificar permisos de SuperAdmin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requieren permisos de SuperAdmin.']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o ausente.']);
    exit;
}

$documento_identidad = trim($_POST['documento_identidad'] ?? '');
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$linea_id = (int)($_POST['linea_id'] ?? 0);
$cargo = trim($_POST['cargo'] ?? 'Administrador de Línea');

if (empty($documento_identidad) || empty($nombre_completo) || empty($email) || empty($password) || $linea_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar email o documento duplicado
    $stmtCheck = $pdo->prepare("SELECT usuario_id FROM USUARIO WHERE email = ? OR documento_identidad = ?");
    $stmtCheck->execute([$email, $documento_identidad]);
    if ($stmtCheck->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'El correo electrónico o documento de identidad ya se encuentra registrado.']);
        exit;
    }

    // Insertar Usuario con rol 4 (ADMIN_LINEA)
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash) VALUES (4, ?, ?, ?, ?)");
    $stmtUser->execute([$documento_identidad, $nombre_completo, $email, $password_hash]);
    $newUserId = (int)$pdo->lastInsertId();

    // Insertar Admin Línea
    $stmtAdmin = $pdo->prepare("INSERT INTO ADMIN_LINEA (usuario_id, linea_id, cargo) VALUES (?, ?, ?)");
    $stmtAdmin->execute([$newUserId, $linea_id, $cargo]);

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'CREAR_ADMIN_LINEA', "SuperAdmin creó el Admin de Línea '$nombre_completo' (ID: $newUserId) para la línea #$linea_id");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Administrador de línea registrado exitosamente."]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en crear_admin_linea: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al registrar el administrador de línea.']);
}
?>
