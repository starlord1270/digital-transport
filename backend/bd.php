<?php
/**
 * DIGITAL TRANSPORT - LEGACY DATABASE WRAPPER (MIGRATED TO PDO)
 * 
 * Este archivo se ha actualizado para redirigir al nuevo envoltorio PDO
 * cumpliendo con la regla de "eliminar rastro de mysqli".
 * 
 * NOTA: Para archivos que aún usen la variable $conn, ahora recibirán
 * un objeto PDO en lugar de MySQLi.
 */

require_once __DIR__ . '/includes/db.php';

// Mantenemos la variable $conn para compatibilidad con archivos no refactorizados,
// pero ahora es un objeto PDO.
$conn = $pdo;

// Las constantes de conexión ya están en includes/db.php, pero si se necesitan:
if (!defined('DB_SERVER')) define('DB_SERVER', 'localhost');
if (!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'digital-transport');

// Nota: set_charset("utf8") no es necesario en PDO ya que se define en el DSN.
?>