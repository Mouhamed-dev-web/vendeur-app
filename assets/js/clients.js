let clientsCache = [];

async function loadClients(query = '') {
  const tbody = document.getElementById('clients-body');
  try {
    const clients = await apiFetch('api/clients.php?q=' + encodeURIComponent(query));
    clientsCache = clients;
    if (clients.length === 0) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Aucun client pour le moment. Ajoutez votre premier client.</td></tr>';
      return;
    }
    tbody.innerHTML = clients.map(c => `
      <tr>
        <td><strong>${escapeHtml(c.nom)}</strong></td>
        <td>${escapeHtml(c.telephone)}</td>
        <td>${escapeHtml(c.adresse || '—')}</td>
        <td>${c.nb_commandes}</td>
        <td>${formatDate(c.created_at)}</td>
        <td style="white-space:nowrap;">
          <a class="icon-btn" href="${waLink(c.telephone, 'Bonjour ' + c.nom + ', ')}" target="_blank" title="Contacter sur WhatsApp">💬</a>
          <button class="icon-btn" onclick="editClient(${c.id})" title="Modifier">✏️</button>
          <button class="icon-btn" onclick="deleteClient(${c.id})" title="Supprimer">🗑️</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="6">${escapeHtml(err.message)}</td></tr>`;
  }
}

function openClientModal() {
  document.getElementById('client-form').reset();
  document.getElementById('client-form').id.value = '';
  document.getElementById('client-modal-title').textContent = 'Nouveau client';
  document.getElementById('client-form-alert').innerHTML = '';
  openModal('client-modal');
}

function editClient(id) {
  const c = clientsCache.find(x => x.id === id);
  if (!c) return;
  const form = document.getElementById('client-form');
  form.id.value = c.id;
  form.nom.value = c.nom;
  form.telephone.value = c.telephone;
  form.adresse.value = c.adresse || '';
  form.notes.value = c.notes || '';
  document.getElementById('client-modal-title').textContent = 'Modifier le client';
  document.getElementById('client-form-alert').innerHTML = '';
  openModal('client-modal');
}

async function deleteClient(id) {
  if (!confirmAction('Supprimer ce client ? Cette action est irréversible.')) return;
  try {
    await apiFetch('api/clients.php?id=' + id, { method: 'DELETE' });
    toast('Client supprimé.');
    loadClients(document.getElementById('search-input').value);
  } catch (err) {
    toast(err.message, 'error');
  }
}

document.getElementById('client-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  const alertBox = document.getElementById('client-form-alert');
  alertBox.innerHTML = '';
  try {
    if (payload.id) {
      await apiFetch('api/clients.php', { method: 'PUT', body: payload });
      toast('Client mis à jour.');
    } else {
      await apiFetch('api/clients.php', { method: 'POST', body: payload });
      toast('Client ajouté.');
    }
    closeModal('client-modal');
    loadClients(document.getElementById('search-input').value);
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

let searchTimer;
document.getElementById('search-input').addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadClients(e.target.value), 300);
});

loadClients();
