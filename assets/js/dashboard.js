(async function () {
  try {
    const stats = await apiFetch('api/stats.php');

    document.getElementById('stat-ca').textContent = formatFcfa(stats.chiffre_affaires);
    document.getElementById('stat-commandes').textContent = stats.nb_commandes;
    document.getElementById('stat-attente').textContent = stats.nb_commandes_en_attente;
    document.getElementById('stat-clients').textContent = stats.nb_clients;
    document.getElementById('stat-rupture').textContent = stats.produits_rupture;

    // Construit les 7 derniers jours (même sans vente, pour un graphique continu)
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

    const ctx = document.getElementById('chart-ventes');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: jours,
        datasets: [{
          label: 'Ventes (FCFA)',
          data: valeurs,
          backgroundColor: '#0B6E4F',
          borderRadius: 6,
          maxBarThickness: 40,
        }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: (v) => v.toLocaleString('fr-FR') } } },
      },
    });

    const tbody = document.getElementById('top-produits-body');
    if (!stats.top_produits || stats.top_produits.length === 0) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="2">Aucune vente enregistrée pour le moment.</td></tr>';
    } else {
      tbody.innerHTML = stats.top_produits.map(p => `
        <tr><td>${escapeHtml(p.nom)}</td><td>${p.total_vendu}</td></tr>
      `).join('');
    }
  } catch (err) {
    toast(err.message, 'error');
  }
})();
