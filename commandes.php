<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Commandes';
$activePage = 'commandes';
$pageScript = 'assets/js/commandes.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="pill-tabs" id="statut-tabs">
  <div class="pill-tab active" data-statut="">Toutes</div>
  <div class="pill-tab" data-statut="en_attente">En attente</div>
  <div class="pill-tab" data-statut="validee">Validées</div>
  <div class="pill-tab" data-statut="en_livraison">En livraison</div>
  <div class="pill-tab" data-statut="livree">Livrées</div>
  <div class="pill-tab" data-statut="annulee">Annulées</div>
</div>

<div class="card">
  <div class="card-head">
    <input type="search" id="search-input" class="search-input" placeholder="Rechercher (référence, client)…">
    <button class="btn btn-primary" onclick="openCommandeModal()">+ Nouvelle commande</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Référence</th><th>Client</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
      <tbody id="commandes-body"><tr class="empty-row"><td colspan="6">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Modale nouvelle commande -->
<div class="modal-backdrop" id="commande-modal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-head">
      <h3 class="mt-0">Nouvelle commande</h3>
      <button class="modal-close" onclick="closeModal('commande-modal')">×</button>
    </div>
    <div id="commande-form-alert"></div>
    <form id="commande-form">
      <label>Client
        <select name="client_id" id="client-select" required></select>
      </label>

      <div id="lignes-container"></div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="ajouterLigne()">+ Ajouter un produit</button>

      <p style="text-align:right; font-weight:700; margin-top:14px;">Total : <span id="commande-total">0 FCFA</span></p>

      <label>Notes<textarea name="notes"></textarea></label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('commande-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer la commande</button>
      </div>
    </form>
  </div>
</div>

<!-- Modale détail commande -->
<div class="modal-backdrop" id="detail-modal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-head">
      <h3 class="mt-0" id="detail-titre">Commande</h3>
      <button class="modal-close" onclick="closeModal('detail-modal')">×</button>
    </div>
    <div id="detail-body"></div>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
