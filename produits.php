<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Produits & Stock';
$activePage = 'produits';
$pageScript = 'assets/js/produits.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="pill-tabs">
  <div class="pill-tab active" data-filter="tous" onclick="setFilter('tous', this)">Tous les produits</div>
  <div class="pill-tab" data-filter="rupture" onclick="setFilter('rupture', this)">⚠️ En rupture / seuil bas</div>
</div>

<div class="card">
  <div class="card-head">
    <input type="search" id="search-input" class="search-input" placeholder="Rechercher un produit…">
    <button class="btn btn-primary" onclick="openProduitModal()">+ Nouveau produit</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
      <tbody id="produits-body"><tr class="empty-row"><td colspan="5">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Modale ajout / édition produit -->
<div class="modal-backdrop" id="produit-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="mt-0" id="produit-modal-title">Nouveau produit</h3>
      <button class="modal-close" onclick="closeModal('produit-modal')">×</button>
    </div>
    <div id="produit-form-alert"></div>
    <form id="produit-form">
      <input type="hidden" name="id">
      <label>Nom du produit<input type="text" name="nom" required></label>
      <div class="form-row">
        <label>Catégorie<input type="text" name="categorie"></label>
        <label>Prix unitaire (FCFA)<input type="number" name="prix" min="1" step="1" required></label>
      </div>
      <div class="form-row" id="stock-initial-row">
        <label>Stock initial<input type="number" name="quantite_stock" min="0" step="1" value="0"></label>
        <label>Seuil d'alerte<input type="number" name="seuil_alerte" min="0" step="1" value="5"></label>
      </div>
      <label>Description<textarea name="description"></textarea></label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('produit-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modale mouvement de stock -->
<div class="modal-backdrop" id="stock-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 class="mt-0">Mouvement de stock — <span id="stock-produit-nom"></span></h3>
      <button class="modal-close" onclick="closeModal('stock-modal')">×</button>
    </div>
    <div id="stock-form-alert"></div>
    <form id="stock-form">
      <input type="hidden" name="produit_id">
      <label>Type de mouvement
        <select name="type">
          <option value="entree">Entrée (réapprovisionnement)</option>
          <option value="sortie">Sortie (perte, casse…)</option>
          <option value="ajustement">Ajustement (fixer la quantité exacte)</option>
        </select>
      </label>
      <label>Quantité<input type="number" name="quantite" min="1" required></label>
      <label>Motif (optionnel)<input type="text" name="motif" placeholder="Ex : réappro fournisseur"></label>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal('stock-modal')">Annuler</button>
        <button type="submit" class="btn btn-primary">Valider</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_footer.php'; ?>
