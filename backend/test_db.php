<?php
// backend/test_db.php

// Incluir tu archivo de conexión
require_once 'bd.php'; 

echo "<h1>PRUEBA DE CONEXIÓN A BD</h1>";

// Si el script llegó hasta aquí, la conexión fue exitosa.
if ($conn->ping()) {
    echo "<p style='color: green; font-size: 1.2em;'>✅ ¡ÉXITO! La conexión a la base de datos 'digital-transport' es correcta.</p>";

    // Prueba de consulta para confirmar que la BD existe
    $result = $conn->query("SELECT COUNT(*) FROM USUARIO");
    if ($result) {
        $row = $result->fetch_array();
        echo "<p style='color: blue;'>Total de registros en USUARIO: " . $row[0] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ ADVERTENCIA: La conexión funciona, pero la tabla USUARIO no se pudo consultar. Revisa el nombre de la base de datos o la tabla.</p>";
    }
} else {
    // Si la conexión falló, pero el 'die' de bd.php no se ejecutó, mostramos el error.
    echo "<p style='color: red; font-size: 1.2em;'>❌ ¡FALLO! No se pudo conectar a la base de datos.</p>";
    echo "<p>Error: " . $conn->connect_error . "</p>";
}

// Cerrar la conexión
$conn->close();
?>