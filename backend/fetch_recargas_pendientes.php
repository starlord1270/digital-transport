<?php
/**
 * DIGITAL TRANSPORT - OBTENER RECARGAS PENDIENTES (PUNTO RECARGA / SUPERADMIN)
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [2, 5], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

$user_id = (int)$_SESSION['usuario_id'];
$user_role = (int)$_SESSION['tipo_usuario_id'];

try {
    if ($user_role === 5) {
        // SuperAdmin ve todas las recargas pendientes
        $sql = "SELECT 
                    r.u_recarga_id, 
                    r.usuario_id, 
                    u.nombre_completo as usuario_nombre, 
                    u.documento_identidad,
                    u.email,
                    r.monto, 
                    r.estado, 
                    r.referencia, 
                    r.fecha_recarga,
                    p.nombre as punto_nombre
                FROM U_RECARGA r
                JOIN USUARIO u ON r.usuario_id = u.usuario_id
                JOIN PUNTO_RECARGA p ON r.punto_id = p.punto_id
                WHERE r.estado = 'PENDIENTE'
                ORDER BY r.fecha_recarga DESC";
        $stmt = $pdo->query($sql);
    } else {
        // Operador de Punto de Recarga solo ve las de su punto asignado
        $stmtPunto = $pdo->prepare("SELECT punto_id FROM PUNTO_RECARGA WHERE usuario_id = ?");
        $stmtPunto->execute([$user_id]);
        $punto_id = (int)$stmtPunto->fetchColumn();

        if ($punto_id <= 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Operador sin punto de recarga asignado.']);
            exit;
        }

        $sql = "SELECT 
                    r.u_recarga_id, 
                    r.usuario_id, 
                    u.nombre_completo as usuario_nombre, 
                    u.documento_identidad,
                    u.email,
                    r.monto, 
                    r.estado, 
                    r.referencia, 
                    r.fecha_recarga,
                    p.nombre as punto_nombre
                FROM U_RECARGA r
                JOIN USUARIO u ON r.usuario_id = u.usuario_id
                JOIN PUNTO_RECARGA p ON r.punto_id = p.punto_id
                WHERE r.estado = 'PENDIENTE' AND r.punto_id = ?
                ORDER BY r.fecha_recarga DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$punto_id]);
    }

    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'recargas' => $pendientes
    ]);

} catch (PDOException $e) {
    error_log("Error en fetch_recargas_pendientes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener solicitudes de recarga.']);
}
?>
