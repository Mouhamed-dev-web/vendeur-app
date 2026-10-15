<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$scopeUserId = $user['role'] === 'admin' ? null : (int) $user['id'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $sql = 'SELECT cmd.*, cl.nom AS client_nom, cl.telephone AS client_telephone, cl.adresse AS client_adresse
                    FROM commandes cmd JOIN clients cl ON cl.id = cmd.client_id
                    WHERE cmd.id = ?';
            $params = [(int) $_GET['id']];
            if ($scopeUserId !== null) { $sql .= ' AND cmd.user_id = ?'; $params[] = $scopeUserId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $commande = $stmt->fetch();
            if (!$commande) json_response(false, 'Commande introuvable.', null, 404);

            $stmt = $pdo->prepare(
                'SELECT cl.*, p.nom AS produit_nom FROM commande_lignes cl
                 JOIN produits p ON p.id = cl.produit_id WHERE cl.commande_id = ?'
            );
            $stmt->execute([$commande['id']]);
            $commande['lignes'] = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT * FROM paiements WHERE commande_id = ? ORDER BY created_at DESC');
            $stmt->execute([$commande['id']]);
            $commande['paiements'] = $stmt->fetchAll();

            json_response(true, '', $commande);
        }

        $sql = 'SELECT cmd.*, cl.nom AS client_nom, cl.telephone AS client_telephone
                FROM commandes cmd JOIN clients cl ON cl.id = cmd.client_id WHERE 1=1';
        $params = [];
        if ($scopeUserId !== null) { $sql .= ' AND cmd.user_id = ?'; $params[] = $scopeUserId; }
        if (!empty($_GET['statut'])) { $sql .= ' AND cmd.statut = ?'; $params[] = $_GET['statut']; }
        if (!empty($_GET['q'])) {
            $sql .= ' AND (cmd.reference LIKE ? OR cl.nom LIKE ?)';
            $params[] = '%' . $_GET['q'] . '%';
            $params[] = '%' . $_GET['q'] . '%';
        }
        $sql .= ' ORDER BY cmd.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'POST':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $_GET['action'] ?? 'create';

        if ($action === 'changer_statut') {
            $id = (int) ($input['id'] ?? 0);
            $statut = $input['statut'] ?? '';
            $valides = ['en_attente', 'validee', 'en_livraison', 'livree', 'annulee'];
            if (!$id || !in_array($statut, $valides, true)) {
                json_response(false, 'Requête invalide.', null, 422);
            }
            $sql = 'UPDATE commandes SET statut = ? WHERE id = ?';
            $params = [$statut, $id];
            if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $stmt->rowCount() ? json_response(true, 'Statut mis à jour.') : json_response(false, 'Commande introuvable.', null, 404);
        }

        // Création d'une commande avec ses lignes
        $clientId = (int) ($input['client_id'] ?? 0);
        $lignes = $input['lignes'] ?? [];
        $notes = clean_str($input['notes'] ?? '');

        if (!$clientId || !is_array($lignes) || count($lignes) === 0) {
            json_response(false, 'Client et au moins un produit sont requis.', null, 422);
        }

        // Vérifie que le client appartient bien au vendeur
        $sql = 'SELECT id FROM clients WHERE id = ?';
        $params = [$clientId];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) json_response(false, 'Client invalide.', null, 422);

        try {
            $pdo->beginTransaction();

            $total = 0;
            $lignesValidees = [];
            foreach ($lignes as $ligne) {
                $produitId = (int) ($ligne['produit_id'] ?? 0);
                $quantite = (int) ($ligne['quantite'] ?? 0);
                if (!$produitId || $quantite <= 0) continue;

                $sqlP = 'SELECT * FROM produits WHERE id = ?';
                $paramsP = [$produitId];
                if ($scopeUserId !== null) { $sqlP .= ' AND user_id = ?'; $paramsP[] = $scopeUserId; }
                $stmtP = $pdo->prepare($sqlP);
                $stmtP->execute($paramsP);
                $produit = $stmtP->fetch();
                if (!$produit) throw new Exception('Produit introuvable dans la commande.');
                if ($produit['quantite_stock'] < $quantite) {
                    throw new Exception('Stock insuffisant pour "' . $produit['nom'] . '" (disponible : ' . $produit['quantite_stock'] . ').');
                }

                $total += $produit['prix'] * $quantite;
                $lignesValidees[] = ['produit' => $produit, 'quantite' => $quantite];
            }

            if (empty($lignesValidees)) throw new Exception('Aucune ligne de commande valide.');

            $reference = generate_reference();
            $stmt = $pdo->prepare(
                'INSERT INTO commandes (user_id, client_id, reference, statut, total, notes) VALUES (?, ?, ?, "en_attente", ?, ?)'
            );
            $stmt->execute([$user['id'], $clientId, $reference, $total, $notes]);
            $commandeId = (int) $pdo->lastInsertId();

            foreach ($lignesValidees as $l) {
                $pdo->prepare('INSERT INTO commande_lignes (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)')
                    ->execute([$commandeId, $l['produit']['id'], $l['quantite'], $l['produit']['prix']]);

                $nouvelleQte = $l['produit']['quantite_stock'] - $l['quantite'];
                $pdo->prepare('UPDATE produits SET quantite_stock = ? WHERE id = ?')
                    ->execute([$nouvelleQte, $l['produit']['id']]);
                $pdo->prepare('INSERT INTO stock_mouvements (produit_id, type, quantite, motif) VALUES (?, "sortie", ?, ?)')
                    ->execute([$l['produit']['id'], $l['quantite'], 'Commande ' . $reference]);
            }

            // Crée automatiquement une entrée paiement "en attente" et une livraison "en attente"
            $pdo->prepare('INSERT INTO paiements (commande_id, montant, mode, statut) VALUES (?, ?, "especes", "en_attente")')
                ->execute([$commandeId, $total]);

            $stmtCl = $pdo->prepare('SELECT adresse FROM clients WHERE id = ?');
            $stmtCl->execute([$clientId]);
            $adresseClient = $stmtCl->fetchColumn() ?: 'À préciser';
            $pdo->prepare('INSERT INTO livraisons (commande_id, adresse, statut) VALUES (?, ?, "en_attente")')
                ->execute([$commandeId, $adresseClient]);

            $pdo->commit();
            json_response(true, 'Commande créée avec succès.', ['id' => $commandeId, 'reference' => $reference], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(false, $e->getMessage(), null, 422);
        }
        break;

    case 'DELETE':
        csrf_verify_or_fail();
        parse_str(file_get_contents('php://input'), $input);
        $id = (int) ($input['id'] ?? ($_GET['id'] ?? 0));
        if (!$id) json_response(false, 'Identifiant commande manquant.', null, 422);

        $sql = 'DELETE FROM commandes WHERE id = ?';
        $params = [$id];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Commande supprimée.') : json_response(false, 'Commande introuvable.', null, 404);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
