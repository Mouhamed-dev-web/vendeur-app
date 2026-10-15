<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Paiements';
$activePage = 'paiements';
$pageScript = 'assets/js/paiements.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="pill-tabs" id="statut-tabs">
  <div class="pill-tab active" data-statut="">Tous</div>
  <div class="pill-tab" data-statut="en_attente">En attente</div>
  <div class="pill-tab" data-statut="partiel">Partiels</div>
  <div class="pill-tab" data-statut="paye">Payés</div>
</div>

<div class="card">
  <div class="card-head">
    <h3 class="mt-0">Suivi des paiements</h3>
    <button class="btn btn-primary" onclick="openPaiementModal()">+ Enregistrer un paiement</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Commande</th><th>Client</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Date</th><th></th></tr></thead>
      <tbody id="paiements-body"><tr class="empty-row"><td colspan="7">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Modale enregistrement paiement -->
<div class="modal-backdrop" id="paiement-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="mt-0">Enregistrer un paiement</h3>
      <button class="modal-close" onclick="closeModal('paiement-modal')">×</button>
    </div>
    <div id="paiement-form-alert"></div>
    <form id="paiement-form">
      <label>Commande
        <select name="commande_id" id="commande-select" required></select>
      </label>
      <div class="form-row">
        <label>Montant (FCFA)<input type="number" name="montant" min="1" required></label>
        <label>Mode de paiement
          <select name="mode">
            <option value="especes">Espèces</option>
            <option value="orange_money">Orange Money</option>
            <option value="wave">Wave</option>
            <option value="virement">Virement</option>
            <option value="autre">Autre</option>
          </select>
        </label>
      </div>
      <label>Statut
        <select name="statut">
          <option value="paye">Payé (intégralement)</option>
          <option value="partiel">Partiel</option>
          <option value="en_attente">En attente</option>
        </select>
      </label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('paiement-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
