<?php
// backend/api/my_records.php
// Endpoint: GET /backend/api/my_records.php
// Rol requerido: usuario (ver su propio historial)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Solo verifica autenticación, NO verifica que sea rol 'usuario'
requireAuth();

$user = currentUser();
$db   = getDB();

// Buscar el paciente asociado al usuario
$stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user['id']]);
$patient = $stmt->fetch();

if (!$patient) {
    jsonResponse(['error' => 'No tienes un perfil de paciente asociado.'], 404);
}

// Obtener historial médico
$stmt = $db->prepare("
    SELECT
        mr.id,
        mr.visit_date,
        mr.diagnosis,
        mr.treatment,
        mr.notes,
        mr.created_at,
        u.full_name  AS doctor_name,
        d.specialty  AS doctor_specialty
    FROM medical_records mr
    JOIN doctors  d ON d.id      = mr.doctor_id
    JOIN users    u ON u.id      = d.user_id
    WHERE mr.patient_id = ?
    ORDER BY mr.visit_date DESC
");
$stmt->execute([$patient['id']]);
$records = $stmt->fetchAll();

// Info del paciente
$stmt = $db->prepare("
    SELECT p.*, u.full_name, u.email
    FROM patients p
    JOIN users u ON u.id = p.user_id
    WHERE p.id = ?
");
$stmt->execute([$patient['id']]);
$patientInfo = $stmt->fetch();

jsonResponse([
    'patient' => $patientInfo,
    'records' => $records,
]);
