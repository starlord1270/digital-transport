<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=digital-transport", "root", "");
    $email = 'sofia.maldonado@linea240.com';
    $new_pass = '123456';
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE USUARIO SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo "Contraseña de $email actualizada con éxito a: $new_pass";
    } else {
        echo "No se encontró al usuario $email o la contraseña ya era la misma.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
