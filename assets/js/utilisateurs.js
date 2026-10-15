const IS_ADMIN = document.querySelector('meta[name="user-role"]')?.content === 'admin';

async function loadLivreurs() {
  const tbody = document.getElementById('livreurs-body');
  try {
    const livreurs = await apiFetch('api/users.php?role=livreur');
    if (livreurs.length === 0) {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="${IS_ADMIN ? 5 : 3}">Aucun livreur enregistré pour le moment.</td></tr>`;
      return;
    }
    tbody.innerHTML = livreurs.map(l => `
      <tr>
        <td><strong>${escapeHtml(l.nom)}</strong></td>
        <td>${escapeHtml(l.email)}</td>
        <td>${escapeHtml(l.telephone)}</td>
        ${IS_ADMIN ? `
          <td><span class="badge badge-${l.actif == 1 ? 'success' : 'neutral'}">${l.actif == 1 ? 'Actif' : 'Désactivé'}</span></td>
          <td><button class="btn btn-secondary btn-sm" onclick="toggleActif(${l.id}, ${l.actif == 1 ? 0 : 1})">${l.actif == 1 ? 'Désactiver' : 'Activer'}</button></td>
        ` : ''}
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">${escapeHtml(err.message)}</td></tr>`;
  }
}

async function toggleActif(id, actif) {
  try {
    await apiFetch('api/users.php', { method: 'PUT', body: { id, actif } });
    toast('Compte mis à jour.');
    loadLivreurs();
  } catch (err) {
    toast(err.message, 'error');
  }
}

function openLivreurModal() {
  document.getElementById('livreur-form').reset();
  document.getElementById('livreur-form-alert').innerHTML = '';
  openModal('livreur-modal');
}

document.getElementById('livreur-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const alertBox = document.getElementById('livreur-form-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/users.php', { method: 'POST', body: Object.fromEntries(fd.entries()) });
    toast('Livreur ajouté.');
    closeModal('livreur-modal');
    loadLivreurs();
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

loadLivreurs();
