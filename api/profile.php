<?php
require_once __DIR__ . '/../config/config.php';

$user = require_login_api();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'PUT') {
    json_response(false, 'Méthode non supportée.', null, 405);
}

csrf_verify_or_fail();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? 'infos';

if ($action === 'infos') {
    $nom = clean_str($input['nom'] ?? '');
    $telephone = clean_str($input['telephone'] ?? '');
    $boutique = clean_str($input['boutique_nom'] ?? '');

    if ($nom === '' || $telephone === '') {
        json_response(false, 'Le nom et le téléphone sont obligatoires.', null, 422);
    }

    $stmt = $pdo->prepare('UPDATE users SET nom = ?, telephone = ?, boutique_nom = ? WHERE id = ?');
    $stmt->execute([$nom, $telephone, $boutique, $user['id']]);

    $_SESSION['user']['nom'] = $nom;
    $_SESSION['user']['telephone'] = $telephone;
    $_SESSION['user']['boutique_nom'] = $boutique;

    json_response(true, 'Profil mis à jour.');
}

if ($action === 'password') {
    $actuel = (string) ($input['mot_de_passe_actuel'] ?? '');
    $nouveau = (string) ($input['nouveau_mot_de_passe'] ?? '');

    if (strlen($nouveau) < 6) {
        json_response(false, 'Le nouveau mot de passe doit contenir au moins 6 caractères.', null, 422);
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($actuel, $hash)) {
        json_response(false, 'Mot de passe actuel incorrect.', null, 422);
    }

    $nouveauHash = password_hash($nouveau, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$nouveauHash, $user['id']]);
    json_response(true, 'Mot de passe modifié avec succès.');
}

json_response(false, 'Action inconnue.', null, 400);
