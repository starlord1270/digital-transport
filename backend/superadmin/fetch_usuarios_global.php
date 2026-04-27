<?php
/**
 * DIGITAL TRANSPORT - FETCH USUARIOS GLOBAL (SUPER ADMIN)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

// Seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

try {
    $sql = "
        SELECT 
            usuario_id,
            nombre_completo,
            email,
            documento_identidad,
            tipo_usuario_id,
            DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') as fecha_registro
        FROM 
            USUARIO
        ORDER BY 
            fecha_registro DESC
    ";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'usuarios' => $usuarios]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
