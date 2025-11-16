<?php
/**
 * Archivo: db_connection.php
 * Propósito: Establecer y manejar la conexión a la base de datos 'digital-transport'.
 * Uso: Incluir este archivo en cualquier script PHP que necesite interactuar con la BD.
 */

// ----------------------------------------------------
// 1. Configuración de Credenciales
// ----------------------------------------------------

// Define las constantes de conexión. Asegúrate de modificar estos valores
// con las credenciales reales de tu servidor MySQL (generalmente localhost).
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');   // Usuario por defecto en XAMPP/WAMP
define('DB_PASSWORD', '');       // Contraseña por defecto en XAMPP/WAMP (dejar vacío si no tienes)
define('DB_NAME', 'digital-transport');

// ----------------------------------------------------
// 2. Establecer la Conexión
// ----------------------------------------------------

// Crea una nueva instancia de la clase mysqli.
// La variable $conn contendrá el objeto de conexión.
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// ----------------------------------------------------
// 3. Verificar la Conexión
// ----------------------------------------------------

// Verifica si hubo algún error al intentar conectar
if ($conn->connect_error) {
    // Si la conexión falla, muestra un mensaje de error y detiene el script
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// ----------------------------------------------------
// 4. Configuración de Caracteres
// ----------------------------------------------------

// Establece el conjunto de caracteres a UTF-8 para evitar problemas con acentos y caracteres especiales
if (!$conn->set_charset("utf8")) {
    // Muestra una advertencia si no se puede establecer el charset
    error_log("Advertencia: Error al cargar el conjunto de caracteres utf8: " . $conn->error);
}

// Nota: Una vez que este archivo se incluye, la variable $conn estará disponible
// para ejecutar consultas SQL en el script que lo incluyó.

// Ejemplo de cómo cerrar la conexión (opcional, ya que PHP la cierra al terminar)
/*
function cerrar_conexion($conn) {
    if ($conn) {
        $conn->close();
    }
}
*/
?>