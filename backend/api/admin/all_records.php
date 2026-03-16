<?php
// backend/api/admin/all_records.php
// ─────────────────────────────────────────────────────────────
// ESQUEMA PostgreSQL:
//   patients.id = users.id  (NO existe columna user_id)
//   doctors.id  = users.id  (NO existe columna user_id)
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    requireRole('admin');
    $db = getDB();

    // patients.id == users.id → JOIN users up ON up.id = p.id
    // doctors.id  == users.id → JOIN users ud ON ud.id = d.id
    $records = $db->query("
        SELECT
            mr.id,
            mr.visit_date,
            mr.diagnosis,
            mr.treatment,
            mr.notes,
            mr.created_at,
            up.full_name AS patient_name,
            ud.full_name AS doctor_name,
            d.specialty
        FROM medical_records mr
        JOIN patients p  ON p.id  = mr.patient_id
        JOIN users    up ON up.id = p.id
        JOIN doctors  d  ON d.id  = mr.doctor_id
        JOIN users    ud ON ud.id = d.id
        ORDER BY mr.visit_date DESC
    ")->fetchAll();

    jsonResponse(['records' => $records, 'total' => count($records)]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
