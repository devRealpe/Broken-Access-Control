<?php
// backend/middleware/auth.php
// Helpers de sesión y control de acceso

if (session_status() === PHP_SESSION_NONE) session_start();

// ----------------------------------------------------------------
// Helpers básicos
// ----------------------------------------------------------------
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

// ----------------------------------------------------------------
// requireAuth — solo verifica que exista sesión (SIN verificar rol)
// ¡Esta es la función VULNERABLE que usan los endpoints del backend!
// ----------------------------------------------------------------
function requireAuth(): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado. Inicia sesión primero.']);
        exit;
    }
}

// ----------------------------------------------------------------
// requireRole — verifica sesión Y rol (función SEGURA, no usada
// en los endpoints vulnerables para fines de la demostración)
// ----------------------------------------------------------------
function requireRole(string ...$roles): void
{
    requireAuth();
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Acceso denegado. No tienes permisos para este recurso.',
            'your_role' => currentRole(),
            'required'  => $roles,
        ]);
        exit;
    }
}

// ----------------------------------------------------------------
// jsonResponse — wrapper para respuestas JSON
// ----------------------------------------------------------------
function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    // CORS permisivo (vulnerable por diseño para la demo)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
