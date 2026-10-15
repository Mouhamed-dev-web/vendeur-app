<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$scopeUserId = $user['role'] === 'admin' ? null : (int) $user['id'];

switch ($method) {
    case 'GET':
        $sql = 'SELECT p.*, cmd.reference, cmd.total AS commande_total, cl.nom AS client_nom
                FROM paiements p
                JOIN commandes cmd ON cmd.id = p.commande_id
                JOIN clients cl ON cl.id = cmd.client_id
                WHERE 1=1';
        $params = [];
        if ($scopeUserId !== null) { $sql .= ' AND cmd.user_id = ?'; $params[] = $scopeUserId; }
        if (!empty($_GET['statut'])) { $sql .= ' AND p.statut = ?'; $params[] = $_GET['statut']; }
        if (!empty($_GET['commande_id'])) { $sql .= ' AND p.commande_id = ?'; $params[] = (int) $_GET['commande_id']; }
        $sql .= ' ORDER BY p.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'POST':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $commandeId = (int) ($input['commande_id'] ?? 0);
        $montant = (float) ($input['montant'] ?? 0);
        $mode = $input['mode'] ?? 'especes';
        $statut = $input['statut'] ?? 'paye';

        $modesValides = ['especes', 'orange_money', 'wave', 'virement', 'autre'];
        $statutsValides = ['en_attente', 'partiel', 'paye'];

        if (!$commandeId || $montant <= 0 || !in_array($mode, $modesValides, true) || !in_array($statut, $statutsValides, true)) {
            json_response(false, 'Données de paiement invalides.', null, 422);
        }

        $sql = 'SELECT id FROM commandes WHERE id = ?';
        $params = [$commandeId];
        if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) json_response(false, 'Commande invalide.', null, 422);

        $stmt = $pdo->prepare(
            'INSERT INTO paiements (commande_id, montant, mode, statut, date_paiement) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$commandeId, $montant, $mode, $statut]);
        json_response(true, 'Paiement enregistré.', ['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        $statut = $input['statut'] ?? '';
        if (!$id || !in_array($statut, ['en_attente', 'partiel', 'paye'], true)) {
            json_response(false, 'Requête invalide.', null, 422);
        }

        $sql = 'UPDATE paiements p JOIN commandes cmd ON cmd.id = p.commande_id
                SET p.statut = ?, p.date_paiement = IF(? = "paye", NOW(), p.date_paiement) WHERE p.id = ?';
        $params = [$statut, $statut, $id];
        if ($scopeUserId !== null) { $sql .= ' AND cmd.user_id = ?'; $params[] = $scopeUserId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stmt->rowCount() ? json_response(true, 'Paiement mis à jour.') : json_response(false, 'Paiement introuvable.', null, 404);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
