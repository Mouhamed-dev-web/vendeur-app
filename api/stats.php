<?php
require_once __DIR__ . '/../config/config.php';

$user = require_role_api(['admin', 'vendeur']);
$pdo = getDB();
$scopeUserId = $user['role'] === 'admin' ? null : (int) $user['id'];

function scopedCount(PDO $pdo, string $table, ?int $scopeUserId, string $extraWhere = '', array $extraParams = []): int
{
    $sql = "SELECT COUNT(*) FROM $table WHERE 1=1 $extraWhere";
    $params = $extraParams;
    if ($scopeUserId !== null) { $sql .= ' AND user_id = ?'; $params[] = $scopeUserId; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$whereUser = $scopeUserId !== null ? ' AND user_id = ' . (int) $scopeUserId : '';

$nbClients = scopedCount($pdo, 'clients', $scopeUserId);
$nbProduits = scopedCount($pdo, 'produits', $scopeUserId);
$nbCommandes = scopedCount($pdo, 'commandes', $scopeUserId);
$nbCommandesEnAttente = scopedCount($pdo, 'commandes', $scopeUserId, " AND statut = 'en_attente'");

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut != 'annulee'" . $whereUser);
$stmt->execute();
$chiffreAffaires = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE quantite_stock <= seuil_alerte" . $whereUser);
$stmt->execute();
$produitsRupture = (int) $stmt->fetchColumn();

// Ventes des 7 derniers jours
$sql = "SELECT DATE(created_at) AS jour, COALESCE(SUM(total),0) AS montant
        FROM commandes WHERE statut != 'annulee' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)" . $whereUser . "
        GROUP BY DATE(created_at) ORDER BY jour ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$ventesParJour = $stmt->fetchAll();

// Top produits vendus
$sql = "SELECT p.nom, SUM(cl.quantite) AS total_vendu
        FROM commande_lignes cl
        JOIN produits p ON p.id = cl.produit_id
        JOIN commandes cmd ON cmd.id = cl.commande_id
        WHERE 1=1" . ($scopeUserId !== null ? ' AND cmd.user_id = ' . (int) $scopeUserId : '') . "
        GROUP BY p.id ORDER BY total_vendu DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$topProduits = $stmt->fetchAll();

json_response(true, '', [
    'nb_clients' => $nbClients,
    'nb_produits' => $nbProduits,
    'nb_commandes' => $nbCommandes,
    'nb_commandes_en_attente' => $nbCommandesEnAttente,
    'chiffre_affaires' => $chiffreAffaires,
    'produits_rupture' => $produitsRupture,
    'ventes_par_jour' => $ventesParJour,
    'top_produits' => $topProduits,
]);
