<?php
// backend/api/admin/create_user.php
session_start();

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

try {
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

    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES ($1,$2,$3,$4,$5)");
    $stmt->execute([$username, $password, $fullName, $email, $role]);

    jsonResponse(['success' => true, 'message' => "Usuario '$username' creado."]);
} catch (Exception $e) {
    $msg = str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')
        ? 'El usuario ya existe.' : $e->getMessage();
    jsonResponse(['error' => $msg], 409);
}
