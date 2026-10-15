(async function () {
  try {
    const stats = await apiFetch('api/stats.php');

    document.getElementById('s-ca').textContent = formatFcfa(stats.chiffre_affaires);
    document.getElementById('s-commandes').textContent = stats.nb_commandes;
    document.getElementById('s-clients').textContent = stats.nb_clients;
    document.getElementById('s-rupture').textContent = stats.produits_rupture;

    const jours = [];
    const valeurs = [];
    const parJour = Object.fromEntries((stats.ventes_par_jour || []).map(v => [v.jour, Number(v.montant)]));
    for (let i = 6; i >= 0; i--) {
      const d = new Date();
      d.setDate(d.getDate() - i);
      const key = d.toISOString().slice(0, 10);
      jours.push(d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' }));
      valeurs.push(parJour[key] || 0);
    }

    new Chart(document.getElementById('chart-ventes'), {
      type: 'line',
      data: { labels: jours, datasets: [{ label: 'Ventes (FCFA)', data: valeurs, borderColor: '#0B6E4F', backgroundColor: 'rgba(11,110,79,.12)', fill: true, tension: .3 }] },
      options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });

    const topProduits = stats.top_produits || [];
    new Chart(document.getElementById('chart-produits'), {
      type: 'bar',
      data: {
        labels: topProduits.map(p => p.nom),
        datasets: [{ label: 'Quantité vendue', data: topProduits.map(p => p.total_vendu), backgroundColor: '#E2A63B', borderRadius: 6 }],
      },
      options: { indexAxis: 'y', plugins: { legend: { display: false } } },
    });
  } catch (err) {
    toast(err.message, 'error');
  }
})();
