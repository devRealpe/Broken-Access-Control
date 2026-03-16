<?php
// backend/api/admin/all_records.php
// Endpoint: GET /backend/api/admin/all_records.php
// Rol requerido: admin (correctamente protegido)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

requireRole('admin');

$db   = getDB();
$stmt = $db->query("
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
    JOIN patients p ON p.id   = mr.patient_id
    JOIN users    up ON up.id = p.user_id
    JOIN doctors  d  ON d.id  = mr.doctor_id
    JOIN users    ud ON ud.id = d.user_id
    ORDER BY mr.visit_date DESC
");
$records = $stmt->fetchAll();

jsonResponse(['records' => $records, 'total' => count($records)]);
