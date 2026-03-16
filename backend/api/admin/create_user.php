<?php
// backend/api/admin/create_user.php
// Endpoint: POST /backend/api/admin/create_user.php
// Rol requerido: admin (correctamente protegido)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

requireRole('admin');

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username']  ?? '');
$password = trim($body['password']  ?? '');
$fullName = trim($body['full_name'] ?? '');
$email    = trim($body['email']     ?? '');
$role     = trim($body['role']      ?? '');

if (!$username || !$password || !$fullName || !$email || !$role) {
    jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
}
if (!in_array($role, ['usuario', 'doctor', 'admin'], true)) {
    jsonResponse(['error' => 'Rol inválido'], 400);
}

$db = getDB();
try {
    $stmt = $db->prepare("INSERT INTO users (username,password,full_name,email,role) VALUES(?,?,?,?,?)");
    $stmt->execute([$username, $password, $fullName, $email, $role]);
    jsonResponse(['success' => true, 'message' => "Usuario '$username' creado.", 'id' => $db->lastInsertId()]);
} catch (PDOException $e) {
    jsonResponse(['error' => 'El usuario ya existe o datos inválidos.'], 409);
}
