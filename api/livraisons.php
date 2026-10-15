<?php
require_once __DIR__ . '/../config/config.php';

$user = require_login_api();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$role = $user['role'];

switch ($method) {
    case 'GET':
        $sql = 'SELECT l.*, cmd.reference, cmd.total, cl.nom AS client_nom, cl.telephone AS client_telephone,
                       u.nom AS livreur_nom
                FROM livraisons l
                JOIN commandes cmd ON cmd.id = l.commande_id
                JOIN clients cl ON cl.id = cmd.client_id
                LEFT JOIN users u ON u.id = l.livreur_id
                WHERE 1=1';
        $params = [];

        if ($role === 'vendeur') {
            $sql .= ' AND cmd.user_id = ?';
            $params[] = $user['id'];
        } elseif ($role === 'livreur') {
            $sql .= ' AND l.livreur_id = ?';
            $params[] = $user['id'];
        }
        // admin : pas de filtre

        if (!empty($_GET['statut'])) { $sql .= ' AND l.statut = ?'; $params[] = $_GET['statut']; }
        $sql .= ' ORDER BY l.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(true, '', $stmt->fetchAll());
        break;

    case 'PUT':
        csrf_verify_or_fail();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        if (!$id) json_response(false, 'Identifiant livraison manquant.', null, 422);

        // Assignation d'un livreur : réservé à vendeur/admin
        if (isset($input['livreur_id'])) {
            require_role_api(['admin', 'vendeur']);
            $livreurId = (int) $input['livreur_id'];
            $sql = 'UPDATE livraisons l JOIN commandes cmd ON cmd.id = l.commande_id
                    SET l.livreur_id = ? WHERE l.id = ?';
            $params = [$livreurId ?: null, $id];
            if ($role === 'vendeur') { $sql .= ' AND cmd.user_id = ?'; $params[] = $user['id']; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $stmt->rowCount() ? json_response(true, 'Livreur assigné.') : json_response(false, 'Livraison introuvable.', null, 404);
        }

        // Changement de statut : vendeur, admin ou le livreur assigné
        if (isset($input['statut'])) {
            $statut = $input['statut'];
            $valides = ['en_attente', 'en_cours', 'livree', 'echouee'];
            if (!in_array($statut, $valides, true)) json_response(false, 'Statut invalide.', null, 422);

            $sql = 'UPDATE livraisons l JOIN commandes cmd ON cmd.id = l.commande_id
                    SET l.statut = ?, l.date_livraison = IF(? = "livree", NOW(), l.date_livraison) WHERE l.id = ?';
            $params = [$statut, $statut, $id];
            if ($role === 'vendeur') { $sql .= ' AND cmd.user_id = ?'; $params[] = $user['id']; }
            if ($role === 'livreur') { $sql .= ' AND l.livreur_id = ?'; $params[] = $user['id']; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() && $statut === 'livree') {
                // Fait aussi passer la commande au statut "livrée"
                $pdo->prepare('UPDATE commandes cmd JOIN livraisons l ON l.commande_id = cmd.id SET cmd.statut = "livree" WHERE l.id = ?')
                    ->execute([$id]);
            }
            $stmt->rowCount() ? json_response(true, 'Statut de livraison mis à jour.') : json_response(false, 'Livraison introuvable.', null, 404);
        }

        json_response(false, 'Aucune modification fournie.', null, 422);
        break;

    default:
        json_response(false, 'Méthode non supportée.', null, 405);
}
