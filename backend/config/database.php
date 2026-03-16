<?php
// backend/config/database.php
// Conexión a PostgreSQL mediante PDO

// ── Configuración ─────────────────────────────────────────────
// Cambia estos valores según tu entorno
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'hospital');
define('DB_USER', 'postgres');
define('DB_PASS', 'admin');

// ── Conexión (singleton) ───────────────────────────────────────
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
