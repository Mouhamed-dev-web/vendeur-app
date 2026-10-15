<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur', 'livreur']);

$pageTitle = current_user()['role'] === 'livreur' ? 'Mes livraisons' : 'Livraisons';
$activePage = 'livraisons';
$pageScript = 'assets/js/livraisons.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="pill-tabs" id="statut-tabs">
  <div class="pill-tab active" data-statut="">Toutes</div>
  <div class="pill-tab" data-statut="en_attente">En attente</div>
  <div class="pill-tab" data-statut="en_cours">En cours</div>
  <div class="pill-tab" data-statut="livree">Livrées</div>
  <div class="pill-tab" data-statut="echouee">Échouées</div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Commande</th><th>Client</th><th>Adresse</th>
          <?php if (current_user()['role'] !== 'livreur'): ?><th>Livreur</th><?php endif; ?>
          <th>Statut</th><th></th>
        </tr>
      </thead>
      <tbody id="livraisons-body"><tr class="empty-row"><td colspan="6">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
