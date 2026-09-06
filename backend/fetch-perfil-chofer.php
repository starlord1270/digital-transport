<?php
/**
 * DIGITAL TRANSPORT - FETCH PERFIL CHOFER (PDO - ULTRA ROBUSTO)
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// 1. Verificación básica de sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit;
}

try {
    $usuario_id = $_SESSION['usuario_id'];

    // 2. Consulta con LEFT JOINs para evitar que la falta de datos en tablas secundarias bloquee todo
    $sql = "SELECT 
                u.nombre_completo, 
                u.email, 
                u.documento_identidad, 
                u.fecha_registro,
                u.tipo_usuario_id,
                c.licencia, 
                c.rating, 
                c.estado_servicio,
                l.nombre as linea_name, 
                v.placa, 
                v.modelo, 
                v.capacidad
            FROM USUARIO u
            LEFT JOIN CHOFER c ON u.usuario_id = c.usuario_id
            LEFT JOIN LINEA l ON c.linea_id = l.linea_id
            LEFT JOIN VEHICULO v ON c.vehiculo_placa = v.placa
            WHERE u.usuario_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Formatear datos para el frontend
        $result = [
            'nombre_completo' => $data['nombre_completo'] ?? 'Usuario sin nombre',
            'email' => $data['email'] ?? 'Sin email',
            'documento_identidad' => $data['documento_identidad'] ?? 'S/N',
            'miembro_desde' => $data['fecha_registro'] ? date('M Y', strtotime($data['fecha_registro'])) : '---',
            'licencia' => $data['licencia'] ?? 'No registrada',
            'rating' => number_format(floatval($data['rating'] ?? 0), 1),
            'linea_name' => $data['linea_name'] ?? 'Línea no asignada',
            'placa' => $data['placa'] ?? 'Sin placa',
            'modelo' => $data['modelo'] ?? 'Modelo no registrado',
            'capacidad' => ($data['capacidad'] ?? '0') . ' pasajeros'
        ];

        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el registro del usuario en la base de datos.']);
    }

} catch (PDOException $e) {
    // Registrar el error detallado en el log del servidor
    error_log("Error en fetch-perfil-chofer: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error general: ' . $e->getMessage()]);
}
?>