<?php
// backend/config/database.php
// Conexión a PostgreSQL mediante PDO

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'hospital');   // ← cambia si tu BD tiene otro nombre
define('DB_USER', 'postgres');
define('DB_PASS', 'admin');      // ← cambia por tu contraseña

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,  // nativo PostgreSQL
    ]);

    return $pdo;
}
