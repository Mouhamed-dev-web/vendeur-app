<?php
/**
 * Authentification et gestion des rôles (admin / vendeur / livreur).
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        die('Accès refusé : vous n\'avez pas les droits nécessaires pour cette page.');
    }
}

/** Variante pour les endpoints API : renvoie du JSON au lieu de mourir avec du HTML. */
function require_login_api(): array
{
    if (!is_logged_in()) {
        json_response(false, 'Session expirée, veuillez vous reconnecter.', null, 401);
    }
    return current_user();
}

function require_role_api(array $roles): array
{
    $user = require_login_api();
    if (!in_array($user['role'], $roles, true)) {
        json_response(false, 'Action non autorisée pour votre rôle.', null, 403);
    }
    return $user;
}

function attempt_login(string $email, string $password): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND actif = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        session_regenerate_id(true);
        return $user;
    }
    return null;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function register_user(string $nom, string $email, string $telephone, string $password, string $boutique = ''): array
{
    $pdo = getDB();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (nom, email, telephone, password_hash, role, boutique_nom) VALUES (?, ?, ?, ?, "vendeur", ?)'
    );
    $stmt->execute([$nom, $email, $telephone, $hash, $boutique]);

    return ['success' => true, 'id' => (int) $pdo->lastInsertId()];
}
