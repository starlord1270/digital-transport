<?php
/**
 * DIGITAL TRANSPORT - FETCH PERFIL (REFACTORIZADA)
 */
header('Content-Type: application/json');
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$user_id = $_SESSION['usuario_id'];

try {
    $sql = "
        SELECT nombre_completo, documento_identidad, email, saldo, fecha_registro, tipo_usuario_id
        FROM USUARIO 
        WHERE usuario_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $roles = [1 => "Estándar", 2 => "Estudiante", 5 => "Adulto", 6 => "Adulto Mayor"];
        $tipo_pasajero = $roles[$data['tipo_usuario_id']] ?? "Usuario";
        
        $fecha_registro = new DateTime($data['fecha_registro']);

        echo json_encode([
            'success' => true,
            'data' => [
                'nombre_completo' => $data['nombre_completo'],
                'documento_identidad' => $data['documento_identidad'],
                'email' => $data['email'],
                'saldo' => number_format($data['saldo'], 2, '.', ''),
                'miembro_desde' => $fecha_registro->format('M Y'),
                'tipo_pasajero' => $tipo_pasajero
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
}
?>