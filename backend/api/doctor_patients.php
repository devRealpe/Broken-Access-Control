<?php
// backend/api/doctor_patients.php
// ─────────────────────────────────────────────────────────────
// ESQUEMA PostgreSQL:
//   doctors.id  = users.id  (NO existe columna user_id)
//   patients.id = users.id  (NO existe columna user_id)
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
    requireRole('doctor');
    $user = currentUser();
    $db   = getDB();

    // doctors.id == users.id → buscamos por id directo
    $stmt = $db->prepare("SELECT id, specialty, license_number FROM doctors WHERE id = $1");
    $stmt->execute([$user['id']]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        jsonResponse(['error' => 'No tienes perfil de doctor.'], 404);
    }

    // Pacientes asignados
    // patients.id == users.id → JOIN users u ON u.id = p.id
    $stmt = $db->prepare("
        SELECT
            p.id,
            u.full_name,
            u.email,
            p.dob,
            p.blood_type,
            p.phone,
            p.address,
            p.emergency_contact,
            (
                SELECT mr.diagnosis FROM medical_records mr
                WHERE mr.patient_id = p.id
                ORDER BY mr.visit_date DESC LIMIT 1
            ) AS last_diagnosis,
            (
                SELECT mr.visit_date FROM medical_records mr
                WHERE mr.patient_id = p.id
                ORDER BY mr.visit_date DESC LIMIT 1
            ) AS last_visit
        FROM patients p
        JOIN assigned_patients ap ON ap.patient_id = p.id
        JOIN users u ON u.id = p.id
        WHERE ap.doctor_id = $1
        ORDER BY u.full_name
    ");
    $stmt->execute([$doctor['id']]);
    $patients = $stmt->fetchAll();

    jsonResponse([
        'doctor'   => array_merge($doctor, ['name' => $user['full_name']]),
        'patients' => $patients,
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
