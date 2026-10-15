<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Un vendeur ne voit que ses propres clients ; l'admin voit tout.
$scopeUserId = $user['role'] === 'admin' ? null : (int) $user['id'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $sql = 'SELECT * FROM clients WHERE id = ?';
            $params = [(int) $_GET['id']];
            if ($scopeUserId !== null) {
                $sql .= ' AND user_id = ?';
                $params[] = $scopeUserId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $client = $stmt->fetch();
            $client ? json_response(true, '', $client) : json_response(false, 'Client introuvable.', null, 404);
        }

        $search = clean_str($_GET['q'] ?? '');
        $sql = 'SELECT c.*,
                       (SELECT COUNT(*) FROM commandes cmd WHERE cmd.client_id = c.id) AS nb_commandes
                FROM clients c WHERE 1=1';
        $params = [];
        if ($scopeUserId !== null) {
            $sql .= ' AND c.user_id = ?';
            $params[] = $scopeUserId;
        }
        if ($search !== '') {
            $sql .= ' AND (c.nom LIKE ? OR c.telephone LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= ' ORDER BY c.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'POST':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $nom = clean_str($input['nom'] ?? '');
        $telephone = clean_str($input['telephone'] ?? '');
        $adresse = clean_str($input['adresse'] ?? '');
        $notes = clean_str($input['notes'] ?? '');

        if ($nom === '' || $telephone === '') {
            json_response(false, 'Le nom et le téléphone sont obligatoires.', null, 422);
        }
        if (!is_valid_phone($telephone)) {
            json_response(false, 'Numéro de téléphone invalide.', null, 422);
        }

        $stmt = $pdo->prepare('INSERT INTO clients (user_id, nom, telephone, adresse, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $nom, $telephone, $adresse, $notes]);
        json_response(true, 'Client ajouté avec succès.', ['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Identifiant client manquant.', null, 422);
        }

        $nom = clean_str($input['nom'] ?? '');
        $telephone = clean_str($input['telephone'] ?? '');
        $adresse = clean_str($input['adresse'] ?? '');
        $notes = clean_str($input['notes'] ?? '');

        if ($nom === '' || $telephone === '') {
            json_response(false, 'Le nom et le téléphone sont obligatoires.', null, 422);
        }

        $sql = 'UPDATE clients SET nom = ?, telephone = ?, adresse = ?, notes = ? WHERE id = ?';
        $params = [$nom, $telephone, $adresse, $notes, $id];
        if ($scopeUserId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $scopeUserId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Client mis à jour.') : json_response(false, 'Client introuvable ou aucune modification.', null, 404);
        break;

    case 'DELETE':
        csrf_verify_or_fail();
        parse_str(file_get_contents('php://input'), $input);
        $id = (int) ($input['id'] ?? ($_GET['id'] ?? 0));
        if (!$id) {
            json_response(false, 'Identifiant client manquant.', null, 422);
        }
        $sql = 'DELETE FROM clients WHERE id = ?';
        $params = [$id];
        if ($scopeUserId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $scopeUserId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Client supprimé.') : json_response(false, 'Client introuvable.', null, 404);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
