<?php
// backend/api/admin/users.php
// ─────────────────────────────────────────────────────────────
// ⚠️⚠️⚠️  VULNERABILIDAD INTENCIONAL — BROKEN ACCESS CONTROL  ⚠️⚠️⚠️
//
// Usa requireAuth() en lugar de requireRole('admin').
// Cualquier usuario AUTENTICADO puede acceder sin importar su rol.
// OWASP A01:2021 — Broken Access Control
//
// FIX (no aplicado a propósito): requireRole('admin');
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
    // ⚠️ SOLO verifica autenticación — NO verifica que sea administrador
    requireAuth();

    $db = getDB();

    // doctors.id == users.id → LEFT JOIN doctors d ON d.id = u.id
    $users = $db->query("
        SELECT
            u.id,
            u.username,
            u.password,
            u.full_name,
            u.email,
            u.role,
            u.created_at,
            CASE WHEN u.role = 'doctor' THEN d.specialty       ELSE NULL END AS specialty,
            CASE WHEN u.role = 'doctor' THEN d.license_number  ELSE NULL END AS license_number
        FROM users u
        LEFT JOIN doctors d ON d.id = u.id
        ORDER BY u.role, u.full_name
    ")->fetchAll();

    $stats = $db->query("
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN role='admin'   THEN 1 ELSE 0 END) AS admins,
            SUM(CASE WHEN role='doctor'  THEN 1 ELSE 0 END) AS doctors,
            SUM(CASE WHEN role='usuario' THEN 1 ELSE 0 END) AS patients
        FROM users
    ")->fetch();

    jsonResponse([
        'vulnerability' => '⚠️ BROKEN ACCESS CONTROL: Este endpoint solo verifica autenticación, NO el rol.',
        'accessed_as'   => currentUser(),
        'stats'         => $stats,
        'users'         => $users,
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
}
