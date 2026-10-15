const USER_ROLE = document.querySelector('meta[name="user-role"]')?.content || 'vendeur';
let currentStatut = '';
let livreursOptions = [];

async function loadLivreursOptions() {
  if (USER_ROLE === 'livreur') return;
  try {
    livreursOptions = await apiFetch('api/users.php?role=livreur');
  } catch (err) {
    livreursOptions = [];
  }
}

async function loadLivraisons() {
  const tbody = document.getElementById('livraisons-body');
  try {
    let url = 'api/livraisons.php';
    if (currentStatut) url += '?statut=' + currentStatut;
    const livraisons = await apiFetch(url);
    const colspan = USER_ROLE === 'livreur' ? 5 : 6;
    if (livraisons.length === 0) {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="${colspan}">Aucune livraison trouvée.</td></tr>`;
      return;
    }
    tbody.innerHTML = livraisons.map(l => {
      const livreurCell = USER_ROLE === 'livreur' ? '' : `<td>${livreurSelect(l)}</td>`;
      return `
      <tr>
        <td><strong>${escapeHtml(l.reference)}</strong></td>
        <td>${escapeHtml(l.client_nom)} <a class="icon-btn" href="${waLink(l.client_telephone)}" target="_blank" title="WhatsApp">💬</a></td>
        <td>${escapeHtml(l.adresse)}</td>
        ${livreurCell}
        <td><span class="badge badge-${badgeClass(l.statut)}">${statutLabel(l.statut)}</span></td>
        <td>${statutSelect(l)}</td>
      </tr>`;
    }).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="6">${escapeHtml(err.message)}</td></tr>`;
  }
}

function livreurSelect(l) {
  const options = ['<option value="">— Non assigné —</option>']
    .concat(livreursOptions.map(u => `<option value="${u.id}" ${l.livreur_id == u.id ? 'selected' : ''}>${escapeHtml(u.nom)}</option>`));
  return `<select onchange="assignerLivreur(${l.id}, this.value)">${options.join('')}</select>`;
}

function statutSelect(l) {
  const statuts = USER_ROLE === 'livreur'
    ? ['en_cours', 'livree', 'echouee']
    : ['en_attente', 'en_cours', 'livree', 'echouee'];
  const options = statuts.map(s => `<option value="${s}" ${s === l.statut ? 'selected' : ''}>${statutLabel(s)}</option>`).join('');
  return `<select onchange="changerStatutLivraison(${l.id}, this.value)">${options}</select>`;
}

function badgeClass(s) { return { en_attente: 'warning', en_cours: 'info', livree: 'success', echouee: 'danger' }[s] || 'neutral'; }
function statutLabel(s) { return { en_attente: 'En attente', en_cours: 'En cours', livree: 'Livrée', echouee: 'Échouée' }[s] || s; }

async function assignerLivreur(id, livreurId) {
  try {
    await apiFetch('api/livraisons.php', { method: 'PUT', body: { id, livreur_id: livreurId ? Number(livreurId) : null } });
    toast('Livreur assigné.');
    loadLivraisons();
  } catch (err) {
    toast(err.message, 'error');
  }
}

async function changerStatutLivraison(id, statut) {
  try {
    await apiFetch('api/livraisons.php', { method: 'PUT', body: { id, statut } });
    toast('Statut de livraison mis à jour.');
    loadLivraisons();
  } catch (err) {
    toast(err.message, 'error');
  }
}

document.querySelectorAll('#statut-tabs .pill-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('#statut-tabs .pill-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentStatut = tab.dataset.statut;
    loadLivraisons();
  });
});

(async function () {
  await loadLivreursOptions();
  await loadLivraisons();
})();
