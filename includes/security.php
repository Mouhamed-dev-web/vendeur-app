<?php
/**
 * Protections CSRF / XSS et helpers de validation.
 */

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** À appeler en tête de chaque endpoint API qui modifie des données (POST/PUT/DELETE). */
function csrf_verify_or_fail(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    if (!csrf_verify($token)) {
        json_response(false, 'Jeton de sécurité invalide, veuillez recharger la page.', null, 419);
    }
}

// ---- Échappement pour affichage HTML ----
function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ---- Réponse JSON standard pour l'API ----
function json_response(bool $success, string $message = '', $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Nettoyage simple des entrées texte ----
function clean_str($value): string
{
    return trim(strip_tags((string)($value ?? '')));
}

// ---- Validation téléphone (format sénégalais souple : 9 à 13 chiffres, +) ----
function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+?[0-9\s]{8,15}$/', $phone);
}

// ---- Normalise un numéro pour un lien wa.me (indicatif Sénégal par défaut) ----
function normalize_phone_for_whatsapp(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 9) {
        // Numéro local sénégalais sans indicatif -> on ajoute 221
        $digits = '221' . $digits;
    }
    return $digits;
}

function wa_link(string $phone, string $message = ''): string
{
    $number = normalize_phone_for_whatsapp($phone);
    $url = 'https://wa.me/' . $number;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}
