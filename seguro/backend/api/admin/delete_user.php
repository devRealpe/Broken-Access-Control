<?php
// backend/api/admin/delete_user.php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    requireRole('admin');

    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $userId = (int)($body['user_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['error' => 'user_id es requerido'], 400);
    }

    // Evitar que el admin se elimine a sí mismo
    $current = currentUser();
    if ($current['id'] === $userId) {
        jsonResponse(['error' => 'No puedes eliminar tu propia cuenta'], 403);
    }

    $db = getDB();

    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    // Eliminar en cascada (en el orden correcto para respetar FK):
    // 1. Registros médicos donde es doctor
    $db->prepare("DELETE FROM medical_records WHERE doctor_id = :id")->execute([':id' => $userId]);
    // 2. Registros médicos donde es paciente
    $db->prepare("DELETE FROM medical_records WHERE patient_id = :id")->execute([':id' => $userId]);
    // 3. Asignaciones
    $db->prepare("DELETE FROM assigned_patients WHERE doctor_id = :id OR patient_id = :id2")
        ->execute([':id' => $userId, ':id2' => $userId]);
    // 4. Fila en doctors o patients
    $db->prepare("DELETE FROM doctors  WHERE id = :id")->execute([':id' => $userId]);
    $db->prepare("DELETE FROM patients WHERE id = :id")->execute([':id' => $userId]);
    // 5. Usuario principal
    $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);

    jsonResponse([
        'success' => true,
        'message' => "Usuario '{$user['username']}' eliminado correctamente.",
        'deleted_id' => $userId
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
