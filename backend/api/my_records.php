<?php
// backend/api/my_records.php
// ─────────────────────────────────────────────────────────────
// ESQUEMA PostgreSQL:
//   patients.id = users.id  (NO existe columna user_id)
//   doctors.id  = users.id  (NO existe columna user_id)
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    requireAuth();
    $user = currentUser();
    $db   = getDB();

    // patients.id == users.id → buscamos por id directo
    $stmt = $db->prepare("SELECT id, dob, blood_type, phone, address, emergency_contact FROM patients WHERE id = $1");
    $stmt->execute([$user['id']]);
    $patient = $stmt->fetch();

    if (!$patient) {
        jsonResponse(['error' => 'No tienes un perfil de paciente asociado.'], 404);
    }

    // Historial médico
    // doctors.id == users.id → JOIN users u ON u.id = d.id
    $stmt = $db->prepare("
        SELECT
            mr.id,
            mr.visit_date,
            mr.diagnosis,
            mr.treatment,
            mr.notes,
            mr.created_at,
            u.full_name AS doctor_name,
            d.specialty AS doctor_specialty
        FROM medical_records mr
        JOIN doctors d ON d.id = mr.doctor_id
        JOIN users   u ON u.id = d.id
        WHERE mr.patient_id = $1
        ORDER BY mr.visit_date DESC
    ");
    $stmt->execute([$patient['id']]);
    $records = $stmt->fetchAll();

    // Info del paciente con nombre/email de users
    $stmt = $db->prepare("
        SELECT p.*, u.full_name, u.email
        FROM patients p
        JOIN users u ON u.id = p.id
        WHERE p.id = $1
    ");
    $stmt->execute([$patient['id']]);
    $patientInfo = $stmt->fetch();

    jsonResponse(['patient' => $patientInfo, 'records' => $records]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
