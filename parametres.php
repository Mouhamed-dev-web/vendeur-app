<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur', 'livreur']);
$u = current_user();

$pageTitle = 'Paramètres';
$activePage = 'parametres';
$pageScript = 'assets/js/parametres.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="card" style="max-width:560px;">
  <h3 class="mt-0">Informations du profil</h3>
  <div id="infos-alert"></div>
  <form id="infos-form">
    <label>Nom complet<input type="text" name="nom" value="<?= e($u['nom']) ?>" required></label>
    <?php if ($u['role'] !== 'livreur'): ?>
    <label>Nom de la boutique<input type="text" name="boutique_nom" value="<?= e($u['boutique_nom'] ?? '') ?>"></label>
    <?php endif; ?>
    <label>Téléphone<input type="text" name="telephone" value="<?= e($u['telephone']) ?>" required></label>
    <label>Email (non modifiable)<input type="email" value="<?= e($u['email']) ?>" disabled></label>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
  </form>
</div>

<div class="card" style="max-width:560px;">
  <h3 class="mt-0">Changer le mot de passe</h3>
  <div id="password-alert"></div>
  <form id="password-form">
    <label>Mot de passe actuel<input type="password" name="mot_de_passe_actuel" required></label>
    <label>Nouveau mot de passe (6 caractères minimum)<input type="password" name="nouveau_mot_de_passe" minlength="6" required></label>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Modifier le mot de passe</button></div>
  </form>
</div>

<div class="card" style="max-width:560px;">
  <h3 class="mt-0">Apparence</h3>
  <p class="text-muted">Basculez entre le mode clair et le mode sombre selon votre préférence.</p>
  <button class="btn btn-secondary" onclick="toggleTheme()">🌙 / ☀️ Changer de thème</button>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
