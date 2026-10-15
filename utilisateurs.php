<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']); // le vendeur peut créer des livreurs ; la liste complète est réservée à l'admin

$u = current_user();
$pageTitle = 'Utilisateurs';
$activePage = 'utilisateurs';
$pageScript = 'assets/js/utilisateurs.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="card">
  <div class="card-head">
    <h3 class="mt-0">Livreurs</h3>
    <button class="btn btn-primary" onclick="openLivreurModal()">+ Ajouter un livreur</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><?php if ($u['role'] === 'admin'): ?><th>Statut</th><th></th><?php endif; ?></tr></thead>
      <tbody id="livreurs-body"><tr class="empty-row"><td colspan="5">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="livreur-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="mt-0">Nouveau livreur</h3>
      <button class="modal-close" onclick="closeModal('livreur-modal')">×</button>
    </div>
    <div id="livreur-form-alert"></div>
    <form id="livreur-form">
      <label>Nom complet<input type="text" name="nom" required></label>
      <label>Téléphone<input type="text" name="telephone" required></label>
      <label>Email<input type="email" name="email" required></label>
      <label>Mot de passe (6 caractères minimum)<input type="password" name="password" minlength="6" required></label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('livreur-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le compte</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
