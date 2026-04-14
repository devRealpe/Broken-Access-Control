<?php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // ✅ CORRECCIÓN: se reemplaza requireAuth() por requireRole('admin')
    // requireAuth() solo verificaba que existiera una sesión activa, sin importar el rol.
    // requireRole('admin') verifica primero la sesión y luego que el rol sea exactamente 'admin'.
    // Cualquier otro rol (usuario, doctor) recibirá un 403 Forbidden.
    requireRole('admin');

    $db = getDB();

    // ⚠️  VULNERABILIDAD ADICIONAL: la consulta expone la columna `password` en texto plano.
    // En un sistema real se debería:
    //   1. Nunca almacenar contraseñas en texto plano (usar password_hash / bcrypt).
    //   2. No seleccionar ni devolver la columna password en ningún endpoint de listado.
    // Se deja el campo en la consulta para mantener compatibilidad con la demo,
    // pero se añade el comentario para que sea visible durante la explicación en clase.
    $users = $db->query("
        SELECT
            u.id, u.username, u.password, u.full_name, u.email, u.role, u.created_at,
            CASE WHEN u.role = 'doctor' THEN d.specialty      ELSE NULL END AS specialty,
            CASE WHEN u.role = 'doctor' THEN d.license_number ELSE NULL END AS license_number
        FROM users u
        LEFT JOIN doctors d ON d.id = u.id
        ORDER BY u.role, u.full_name
    ")->fetchAll();

    $stats = $db->query("
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN role='admin'   THEN 1 ELSE 0 END) AS admins,
            SUM(CASE WHEN role='doctor'  THEN 1 ELSE 0 END) AS doctors,
            SUM(CASE WHEN role='usuario' THEN 1 ELSE 0 END) AS patients
        FROM users
    ")->fetch();

    jsonResponse([
        'accessed_as' => currentUser(),
        'stats'       => $stats,
        'users'       => $users,
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
