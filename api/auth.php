<?php
require_once __DIR__ . '/../config/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method !== 'POST') {
    json_response(false, 'Méthode non supportée.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($action === 'login') {
    csrf_verify_or_fail();
    $email = clean_str($input['email'] ?? '');
    $password = (string) ($input['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(false, 'Email et mot de passe requis.', null, 422);
    }

    $user = attempt_login($email, $password);
    $user ? json_response(true, 'Connexion réussie.', ['role' => $user['role']]) : json_response(false, 'Email ou mot de passe incorrect.', null, 401);
}

if ($action === 'register') {
    csrf_verify_or_fail();
    $nom = clean_str($input['nom'] ?? '');
    $email = clean_str($input['email'] ?? '');
    $telephone = clean_str($input['telephone'] ?? '');
    $boutique = clean_str($input['boutique'] ?? '');
    $password = (string) ($input['password'] ?? '');

    if ($nom === '' || $email === '' || $telephone === '' || strlen($password) < 6) {
        json_response(false, 'Merci de remplir tous les champs (mot de passe : 6 caractères minimum).', null, 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'Adresse email invalide.', null, 422);
    }
    if (!is_valid_phone($telephone)) {
        json_response(false, 'Numéro de téléphone invalide.', null, 422);
    }

    $result = register_user($nom, $email, $telephone, $password, $boutique);
    if (!$result['success']) {
        json_response(false, $result['message'], null, 422);
    }
    json_response(true, 'Compte créé avec succès, vous pouvez vous connecter.', null, 201);
}

if ($action === 'logout') {
    logout();
    json_response(true, 'Déconnexion réussie.');
}

json_response(false, 'Action inconnue.', null, 400);
