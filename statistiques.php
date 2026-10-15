<?php
require_once __DIR__ . '/config/config.php';
require_role(['admin', 'vendeur']);

$pageTitle = 'Statistiques';
$activePage = 'statistiques';
$pageScript = 'assets/js/statistiques.js';
include __DIR__ . '/includes/layout_header.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Chiffre d'affaires total</div><div class="value accent" id="s-ca">—</div></div>
  <div class="stat-card"><div class="label">Total commandes</div><div class="value" id="s-commandes">—</div></div>
  <div class="stat-card"><div class="label">Clients enregistrés</div><div class="value" id="s-clients">—</div></div>
  <div class="stat-card"><div class="label">Produits en rupture</div><div class="value warn" id="s-rupture">—</div></div>
</div>

<div class="card">
  <h3 class="mt-0">Évolution des ventes (7 derniers jours)</h3>
  <canvas id="chart-ventes" height="90"></canvas>
</div>

<div class="card">
  <h3 class="mt-0">Top 5 des produits les plus vendus</h3>
  <canvas id="chart-produits" height="100"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
