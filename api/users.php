<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Liste des livreurs disponibles (pour assignation de livraison)
        $role = $_GET['role'] ?? 'livreur';
        $role = in_array($role, ['livreur', 'vendeur', 'admin'], true) ? $role : 'livreur';

        if ($user['role'] === 'admin') {
            $stmt = $pdo->prepare('SELECT id, nom, email, telephone, role, actif, created_at FROM users WHERE role = ? ORDER BY nom');
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->prepare('SELECT id, nom, email, telephone FROM users WHERE role = "livreur" AND actif = 1 ORDER BY nom');
            $stmt->execute();
        }
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'POST':
        // Création d'un compte livreur, réservé au vendeur/admin
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $nom = clean_str($input['nom'] ?? '');
        $email = clean_str($input['email'] ?? '');
        $telephone = clean_str($input['telephone'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if ($nom === '' || $email === '' || $telephone === '' || strlen($password) < 6) {
            json_response(false, 'Merci de remplir tous les champs (mot de passe : 6 caractères minimum).', null, 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(false, 'Adresse email invalide.', null, 422);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) json_response(false, 'Cet email est déjà utilisé.', null, 422);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (nom, email, telephone, password_hash, role) VALUES (?, ?, ?, ?, "livreur")');
        $stmt->execute([$nom, $email, $telephone, $hash]);
        json_response(true, 'Livreur ajouté avec succès.', ['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        // Activer / désactiver un compte : admin uniquement
        require_role_api(['admin']);
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        $actif = isset($input['actif']) ? (int) (bool) $input['actif'] : null;
        if (!$id || $actif === null) json_response(false, 'Requête invalide.', null, 422);

        $stmt = $pdo->prepare('UPDATE users SET actif = ? WHERE id = ?');
        $stmt->execute([$actif, $id]);
        $stmt->rowCount() ? json_response(true, 'Compte mis à jour.') : json_response(false, 'Utilisateur introuvable.', null, 404);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
