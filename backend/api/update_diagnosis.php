<?php
// backend/api/update_diagnosis.php
// Endpoint: POST /backend/api/update_diagnosis.php
// Rol requerido: doctor

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

requireRole('doctor');

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$patientId  = (int)($body['patient_id']  ?? 0);
$diagnosis  = trim($body['diagnosis']    ?? '');
$treatment  = trim($body['treatment']    ?? '');
$notes      = trim($body['notes']        ?? '');
$visitDate  = $body['visit_date']        ?? date('Y-m-d');

if (!$patientId || !$diagnosis || !$treatment) {
    jsonResponse(['error' => 'Campos requeridos: patient_id, diagnosis, treatment'], 400);
}

$user = currentUser();
$db   = getDB();

$stmt = $db->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user['id']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    jsonResponse(['error' => 'Perfil de doctor no encontrado'], 404);
}

// Verificar que el paciente esté asignado a este doctor
$stmt = $db->prepare("SELECT 1 FROM assigned_patients WHERE doctor_id=? AND patient_id=?");
$stmt->execute([$doctor['id'], $patientId]);
if (!$stmt->fetch()) {
    jsonResponse(['error' => 'Este paciente no está asignado a ti.'], 403);
}

$stmt = $db->prepare("
    INSERT INTO medical_records (patient_id, doctor_id, visit_date, diagnosis, treatment, notes)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$patientId, $doctor['id'], $visitDate, $diagnosis, $treatment, $notes]);

jsonResponse([
    'success' => true,
    'message' => 'Diagnóstico registrado correctamente.',
    'record_id' => $db->lastInsertId(),
]);
