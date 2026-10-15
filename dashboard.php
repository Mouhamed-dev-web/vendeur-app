<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Tableau de bord';
$activePage = 'dashboard';
$pageScript = 'assets/js/dashboard.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="stat-grid" id="stat-grid">
  <div class="stat-card"><div class="label">Chiffre d'affaires</div><div class="value accent" id="stat-ca">—</div></div>
  <div class="stat-card"><div class="label">Commandes</div><div class="value" id="stat-commandes">—</div></div>
  <div class="stat-card"><div class="label">En attente</div><div class="value warn" id="stat-attente">—</div></div>
  <div class="stat-card"><div class="label">Clients</div><div class="value" id="stat-clients">—</div></div>
  <div class="stat-card"><div class="label">Produits en rupture</div><div class="value warn" id="stat-rupture">—</div></div>
</div>

<div class="card">
  <div class="card-head"><h3 class="mt-0">Ventes des 7 derniers jours</h3></div>
  <canvas id="chart-ventes" height="90"></canvas>
</div>

<div class="card">
  <div class="card-head"><h3 class="mt-0">Produits les plus vendus</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Produit</th><th>Quantité vendue</th></tr></thead>
      <tbody id="top-produits-body"><tr class="empty-row"><td colspan="2">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
