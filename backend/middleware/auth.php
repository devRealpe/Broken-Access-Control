<?php
// backend/middleware/auth.php

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function currentRole(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

// ⚠️ VULNERABLE — solo verifica sesión, NO el rol
function requireAuth(): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'No autenticado. Inicia sesión primero.'], 401);
    }
}

// ✅ SEGURA — verifica sesión Y rol
function requireRole(string ...$roles): void
{
    requireAuth();
    if (!in_array(currentRole(), $roles, true)) {
        jsonResponse([
            'error'     => 'Acceso denegado. No tienes permisos para este recurso.',
            'your_role' => currentRole(),
            'required'  => $roles,
        ], 403);
    }
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
