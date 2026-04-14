<?php
// backend/api/admin/delete_record.php
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

    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $recordId = (int)($body['record_id'] ?? 0);

    if (!$recordId) {
        jsonResponse(['error' => 'record_id es requerido'], 400);
    }

    $db = getDB();

    // Verificar que el registro existe
    $stmt = $db->prepare("SELECT id FROM medical_records WHERE id = :id");
    $stmt->execute([':id' => $recordId]);
    $record = $stmt->fetch();

    if (!$record) {
        jsonResponse(['error' => 'Registro médico no encontrado'], 404);
    }

    $db->prepare("DELETE FROM medical_records WHERE id = :id")->execute([':id' => $recordId]);

    jsonResponse([
        'success'    => true,
        'message'    => 'Registro médico eliminado correctamente.',
        'deleted_id' => $recordId
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
