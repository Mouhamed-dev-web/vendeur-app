<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Clients';
$activePage = 'clients';
$pageScript = 'assets/js/clients.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="card">
  <div class="card-head">
    <input type="search" id="search-input" class="search-input" placeholder="Rechercher un client (nom, téléphone)…">
    <button class="btn btn-primary" onclick="openClientModal()">+ Nouveau client</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nom</th><th>Téléphone</th><th>Adresse</th><th>Commandes</th><th>Ajouté le</th><th></th></tr></thead>
      <tbody id="clients-body"><tr class="empty-row"><td colspan="6">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Modale ajout / édition -->
<div class="modal-backdrop" id="client-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="mt-0" id="client-modal-title">Nouveau client</h3>
      <button class="modal-close" onclick="closeModal('client-modal')">×</button>
    </div>
    <div id="client-form-alert"></div>
    <form id="client-form">
      <input type="hidden" name="id">
      <label>Nom complet<input type="text" name="nom" required></label>
      <label>Téléphone (WhatsApp)<input type="text" name="telephone" placeholder="77 123 45 67" required></label>
      <label>Adresse<input type="text" name="adresse"></label>
      <label>Notes<textarea name="notes"></textarea></label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('client-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
