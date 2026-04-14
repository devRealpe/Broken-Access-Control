<?php
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();

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

try {
    requireRole('doctor');

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $patientId = (int)($body['patient_id'] ?? 0);
    $diagnosis = trim($body['diagnosis']   ?? '');
    $treatment = trim($body['treatment']   ?? '');
    $notes     = trim($body['notes']       ?? '');
    $visitDate = $body['visit_date']       ?? date('Y-m-d');

    if (!$patientId || !$diagnosis || !$treatment) {
        jsonResponse(['error' => 'Campos requeridos: patient_id, diagnosis, treatment'], 400);
    }

    $user = currentUser();
    $db   = getDB();

    $stmt = $db->prepare("SELECT id FROM doctors WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    $doctor = $stmt->fetch();
    if (!$doctor) jsonResponse(['error' => 'Perfil de doctor no encontrado'], 404);

    $stmt = $db->prepare("SELECT 1 FROM assigned_patients WHERE doctor_id = :did AND patient_id = :pid");
    $stmt->execute([':did' => $doctor['id'], ':pid' => $patientId]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Este paciente no está asignado a ti.'], 403);

    $stmt = $db->prepare("
        INSERT INTO medical_records (patient_id, doctor_id, visit_date, diagnosis, treatment, notes)
        VALUES (:patient_id, :doctor_id, :visit_date, :diagnosis, :treatment, :notes)
    ");
    $stmt->execute([
        ':patient_id' => $patientId,
        ':doctor_id'  => $doctor['id'],
        ':visit_date' => $visitDate,
        ':diagnosis'  => $diagnosis,
        ':treatment'  => $treatment,
        ':notes'      => $notes,
    ]);

    jsonResponse(['success' => true, 'message' => 'Diagnóstico registrado correctamente.']);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
