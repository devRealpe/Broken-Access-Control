<?php
// backend/api/login.php
// Endpoint: POST /backend/api/login.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (!$username || !$password) {
    jsonResponse(['error' => 'Usuario y contraseña requeridos'], 400);
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// ⚠️ VULNERABILIDAD: comparación de contraseña en texto plano
if (!$user || $user['password'] !== $password) {
    jsonResponse(['error' => 'Credenciales incorrectas'], 401);
}

// Iniciar sesión
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user']    = [
    'id'        => $user['id'],
    'username'  => $user['username'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
    'role'      => $user['role'],
];

jsonResponse([
    'success'  => true,
    'message'  => 'Sesión iniciada correctamente',
    'user'     => $_SESSION['user'],
]);
