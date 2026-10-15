<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$scopeUserId = $user['role'] === 'admin' ? null : (int) $user['id'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $sql = 'SELECT * FROM produits WHERE id = ?';
            $params = [(int) $_GET['id']];
            if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $p = $stmt->fetch();
            $p ? json_response(true, '', $p) : json_response(false, 'Produit introuvable.', null, 404);
        }

        $search = clean_str($_GET['q'] ?? '');
        $sql = 'SELECT * FROM produits WHERE 1=1';
        $params = [];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        if ($search !== '') { $sql .= ' AND nom LIKE ?'; $params[] = "%$search%"; }
        if (!empty($_GET['rupture'])) { $sql .= ' AND quantite_stock <= seuil_alerte'; }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'POST':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Mouvement de stock manuel (entrée / sortie / ajustement) sur produit existant
        if ($action === 'mouvement') {
            $produitId = (int) ($input['produit_id'] ?? 0);
            $type = $input['type'] ?? '';
            $quantite = (int) ($input['quantite'] ?? 0);
            $motif = clean_str($input['motif'] ?? '');

            if (!$produitId || !in_array($type, ['entree', 'sortie', 'ajustement'], true) || $quantite <= 0) {
                json_response(false, 'Données de mouvement de stock invalides.', null, 422);
            }

            $sql = 'SELECT * FROM produits WHERE id = ?';
            $params = [$produitId];
            if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $produit = $stmt->fetch();
            if (!$produit) json_response(false, 'Produit introuvable.', null, 404);

            $nouvelleQte = match ($type) {
                'entree' => $produit['quantite_stock'] + $quantite,
                'sortie' => max(0, $produit['quantite_stock'] - $quantite),
                'ajustement' => $quantite,
            };

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE produits SET quantite_stock = ? WHERE id = ?')->execute([$nouvelleQte, $produitId]);
            $pdo->prepare('INSERT INTO stock_mouvements (produit_id, type, quantite, motif) VALUES (?, ?, ?, ?)')
                ->execute([$produitId, $type, $quantite, $motif]);
            $pdo->commit();

            json_response(true, 'Stock mis à jour.', ['quantite_stock' => $nouvelleQte]);
        }

        $nom = clean_str($input['nom'] ?? '');
        $description = clean_str($input['description'] ?? '');
        $categorie = clean_str($input['categorie'] ?? '');
        $prix = (float) ($input['prix'] ?? 0);
        $stock = (int) ($input['quantite_stock'] ?? 0);
        $seuil = (int) ($input['seuil_alerte'] ?? 5);

        if ($nom === '' || $prix <= 0) {
            json_response(false, 'Le nom et un prix valide sont obligatoires.', null, 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO produits (user_id, nom, description, categorie, prix, quantite_stock, seuil_alerte) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $nom, $description, $categorie, $prix, $stock, $seuil]);
        $id = (int) $pdo->lastInsertId();

        if ($stock > 0) {
            $pdo->prepare('INSERT INTO stock_mouvements (produit_id, type, quantite, motif) VALUES (?, "entree", ?, "Stock initial")')
                ->execute([$id, $stock]);
        }

        json_response(true, 'Produit ajouté avec succès.', ['id' => $id], 201);
        break;

    case 'PUT':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        if (!$id) json_response(false, 'Identifiant produit manquant.', null, 422);

        $nom = clean_str($input['nom'] ?? '');
        $description = clean_str($input['description'] ?? '');
        $categorie = clean_str($input['categorie'] ?? '');
        $prix = (float) ($input['prix'] ?? 0);
        $seuil = (int) ($input['seuil_alerte'] ?? 5);

        if ($nom === '' || $prix <= 0) {
            json_response(false, 'Le nom et un prix valide sont obligatoires.', null, 422);
        }

        $sql = 'UPDATE produits SET nom=?, description=?, categorie=?, prix=?, seuil_alerte=? WHERE id=?';
        $params = [$nom, $description, $categorie, $prix, $seuil, $id];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Produit mis à jour.') : json_response(false, 'Produit introuvable.', null, 404);
        break;

    case 'DELETE':
        csrf_verify_or_fail();
        parse_str(file_get_contents('php://input'), $input);
        $id = (int) ($input['id'] ?? ($_GET['id'] ?? 0));
        if (!$id) json_response(false, 'Identifiant produit manquant.', null, 422);

        $sql = 'DELETE FROM produits WHERE id = ?';
        $params = [$id];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Produit supprimé.') : json_response(false, 'Produit introuvable.', null, 404);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
