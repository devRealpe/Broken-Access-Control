<?php
// DIAGNÓSTICO — eliminar después de la demo
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$result = [];

try {
    $db = getDB();
    $result['db_connection'] = '✅ Conexión exitosa';
} catch (Exception $e) {
    echo json_encode(['db_connection' => '❌ ' . $e->getMessage()]);
    exit;
}

try {
    $count = $db->query("SELECT COUNT(*) as total FROM users")->fetch();
    $result['users_count'] = '✅ ' . $count['total'] . ' usuarios';
} catch (Exception $e) {
    $result['users_count'] = '❌ ' . $e->getMessage();
}

// Test con placeholder con nombre :username
try {
    $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
    $stmt->execute([':username' => 'juan_perez']);
    $user = $stmt->fetch();
    if ($user) {
        $result['juan_perez_found']     = '✅ Encontrado';
        $result['password_stored']      = $user['password'];
        $result['password_match']       = ($user['password'] === 'pass1234') ? '✅ Coincide' : '❌ NO coincide, valor: "' . $user['password'] . '"';
    } else {
        $result['juan_perez_found'] = '❌ No encontrado — problema con placeholder :username';
    }
} catch (Exception $e) {
    $result['juan_perez_found'] = '❌ Error: ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
