<?php
/**
 * DIGITAL TRANSPORT - GUARDAR PUNTO DE RECARGA + OPERADOR (SUPER ADMIN)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
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
        echo json_encode(['success' => false, 'error' => "El correo '$email' ya está registrado en el sistema. Por favor use otro."]);
        exit;
    }

    // 1. Crear Usuario Operador
    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO USUARIO (nombre_completo, email, password_hash, tipo_usuario_id, documento_identidad) VALUES (?, ?, ?, 2, ?)");
    $stmtUser->execute([$nombre_operador, $email, $pass_hash, 'PUNTO-'.time()]);
    $usuario_id = $pdo->lastInsertId();

    // 2. Crear Punto de Recarga
    $stmtPunto = $pdo->prepare("INSERT INTO PUNTO_RECARGA (nombre, ubicacion, usuario_id, lat, lng) VALUES (?, ?, ?, ?, ?)");
    $stmtPunto->execute([$nombre_punto, $ubicacion, $usuario_id, $lat, $lng]);

    // Auditoría
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'NUEVO_PUNTO_RECARGA', "Se habilitó el punto '$nombre_punto' operado por $nombre_operador");

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Punto habilitado correctamente.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
