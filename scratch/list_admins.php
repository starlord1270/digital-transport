<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $sql = "SELECT u.usuario_id, u.nombre_completo, u.email, l.nombre as linea 
            FROM USUARIO u 
            JOIN ADMIN_LINEA a ON u.usuario_id = a.usuario_id 
            JOIN LINEA l ON a.linea_id = l.linea_id 
            WHERE u.tipo_usuario_id = 4";
    $stmt = $pdo->query($sql);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "--- Administradores de Línea Registrados ---\n";
    foreach ($admins as $admin) {
        echo "ID: {$admin['usuario_id']} | Nombre: {$admin['nombre_completo']} | Email: {$admin['email']} | Línea: {$admin['linea']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
