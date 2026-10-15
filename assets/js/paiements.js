let currentStatut = '';

async function loadPaiements() {
  const tbody = document.getElementById('paiements-body');
  try {
    let url = 'api/paiements.php';
    if (currentStatut) url += '?statut=' + currentStatut;
    const paiements = await apiFetch(url);
    if (paiements.length === 0) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="7">Aucun paiement trouvé.</td></tr>';
      return;
    }
    tbody.innerHTML = paiements.map(p => `
      <tr>
        <td><strong>${escapeHtml(p.reference)}</strong></td>
        <td>${escapeHtml(p.client_nom)}</td>
        <td>${formatFcfa(p.montant)}</td>
        <td>${modeLabel(p.mode)}</td>
        <td><span class="badge badge-${badgeClass(p.statut)}">${statutLabel(p.statut)}</span></td>
        <td>${p.date_paiement ? formatDate(p.date_paiement) : '—'}</td>
        <td>
          ${p.statut !== 'paye' ? `<button class="btn btn-secondary btn-sm" onclick="marquerPaye(${p.id})">Marquer payé</button>` : ''}
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="7">${escapeHtml(err.message)}</td></tr>`;
  }
}

function badgeClass(s) { return { en_attente: 'warning', partiel: 'warning', paye: 'success' }[s] || 'neutral'; }
function statutLabel(s) { return { en_attente: 'En attente', partiel: 'Partiel', paye: 'Payé' }[s] || s; }
function modeLabel(m) { return { especes: 'Espèces', orange_money: 'Orange Money', wave: 'Wave', virement: 'Virement', autre: 'Autre' }[m] || m; }

document.querySelectorAll('#statut-tabs .pill-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('#statut-tabs .pill-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentStatut = tab.dataset.statut;
    loadPaiements();
  });
});

async function marquerPaye(id) {
  try {
    await apiFetch('api/paiements.php', { method: 'PUT', body: { id, statut: 'paye' } });
    toast('Paiement marqué comme payé.');
    loadPaiements();
  } catch (err) {
    toast(err.message, 'error');
  }
}

async function openPaiementModal() {
  document.getElementById('paiement-form').reset();
  document.getElementById('paiement-form-alert').innerHTML = '';
  try {
    const commandes = await apiFetch('api/commandes.php');
    const select = document.getElementById('commande-select');
    select.innerHTML = commandes.map(c => `<option value="${c.id}">${escapeHtml(c.reference)} — ${escapeHtml(c.client_nom)} (${formatFcfa(c.total)})</option>`).join('');
    openModal('paiement-modal');
  } catch (err) {
    toast(err.message, 'error');
  }
}

document.getElementById('paiement-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  payload.commande_id = Number(payload.commande_id);
  payload.montant = Number(payload.montant);
  const alertBox = document.getElementById('paiement-form-alert');
  try {
    await apiFetch('api/paiements.php', { method: 'POST', body: payload });
    toast('Paiement enregistré.');
    closeModal('paiement-modal');
    loadPaiements();
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

loadPaiements();
