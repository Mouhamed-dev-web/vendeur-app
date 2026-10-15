<?php
/**
 * $pageTitle et $activePage doivent être définis avant l'include.
 */
$u = current_user();
$role = $u['role'] ?? 'vendeur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="user-role" content="<?= e($role) ?>">
<title><?= e($pageTitle ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-brand"><span class="dot"></span> <?= e(APP_NAME) ?></div>
    <nav class="sidebar-nav">
      <?php if (in_array($role, ['admin', 'vendeur'], true)): ?>
        <a href="dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><span class="nav-icon">🏠</span> Tableau de bord</a>
        <a href="clients.php" class="<?= $activePage === 'clients' ? 'active' : '' ?>"><span class="nav-icon">👥</span> Clients</a>
        <a href="produits.php" class="<?= $activePage === 'produits' ? 'active' : '' ?>"><span class="nav-icon">📦</span> Produits & Stock</a>
        <a href="commandes.php" class="<?= $activePage === 'commandes' ? 'active' : '' ?>"><span class="nav-icon">🧾</span> Commandes</a>
        <a href="paiements.php" class="<?= $activePage === 'paiements' ? 'active' : '' ?>"><span class="nav-icon">💰</span> Paiements</a>
        <a href="livraisons.php" class="<?= $activePage === 'livraisons' ? 'active' : '' ?>"><span class="nav-icon">🚚</span> Livraisons</a>
        <a href="statistiques.php" class="<?= $activePage === 'statistiques' ? 'active' : '' ?>"><span class="nav-icon">📊</span> Statistiques</a>
        <?php if ($role === 'admin'): ?>
        <a href="utilisateurs.php" class="<?= $activePage === 'utilisateurs' ? 'active' : '' ?>"><span class="nav-icon">🛡️</span> Utilisateurs</a>
        <?php endif; ?>
        <a href="parametres.php" class="<?= $activePage === 'parametres' ? 'active' : '' ?>"><span class="nav-icon">⚙️</span> Paramètres</a>
      <?php else: ?>
        <a href="livraisons.php" class="<?= $activePage === 'livraisons' ? 'active' : '' ?>"><span class="nav-icon">🚚</span> Mes livraisons</a>
        <a href="parametres.php" class="<?= $activePage === 'parametres' ? 'active' : '' ?>"><span class="nav-icon">⚙️</span> Paramètres</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-foot">Connecté : <?= e($u['nom'] ?? '') ?><br><?= e(ucfirst($role)) ?></div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:12px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <h1><?= e($pageTitle ?? '') ?></h1>
      </div>
      <div class="topbar-actions">
        <button id="theme-toggle" class="icon-btn" onclick="toggleTheme()" title="Changer de thème">🌙</button>
        <a href="logout.php" class="btn btn-secondary btn-sm">Déconnexion</a>
      </div>
    </header>
    <div class="content">
