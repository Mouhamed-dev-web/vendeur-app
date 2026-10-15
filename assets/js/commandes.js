let commandesCache = [];
let clientsOptions = [];
let produitsOptions = [];
let currentStatut = '';
let ligneIndex = 0;

async function loadCommandes(query = '') {
  const tbody = document.getElementById('commandes-body');
  try {
    let url = 'api/commandes.php?q=' + encodeURIComponent(query);
    if (currentStatut) url += '&statut=' + currentStatut;
    const commandes = await apiFetch(url);
    commandesCache = commandes;
    if (commandes.length === 0) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="6">Aucune commande trouvée.</td></tr>';
      return;
    }
    tbody.innerHTML = commandes.map(c => `
      <tr>
        <td><strong>${escapeHtml(c.reference)}</strong></td>
        <td>${escapeHtml(c.client_nom)}</td>
        <td>${formatFcfa(c.total)}</td>
        <td><span class="badge badge-${badgeClass(c.statut)}">${statutLabel(c.statut)}</span></td>
        <td>${formatDate(c.created_at)}</td>
        <td style="white-space:nowrap;">
          <button class="icon-btn" onclick="viewCommande(${c.id})" title="Voir le détail">👁️</button>
          <button class="icon-btn" onclick="deleteCommande(${c.id})" title="Supprimer">🗑️</button>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="6">${escapeHtml(err.message)}</td></tr>`;
  }
}

function badgeClass(s) {
  return { en_attente: 'warning', validee: 'info', en_livraison: 'info', livree: 'success', annulee: 'danger' }[s] || 'neutral';
}
function statutLabel(s) {
  return { en_attente: 'En attente', validee: 'Validée', en_livraison: 'En livraison', livree: 'Livrée', annulee: 'Annulée' }[s] || s;
}

document.querySelectorAll('#statut-tabs .pill-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('#statut-tabs .pill-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentStatut = tab.dataset.statut;
    loadCommandes(document.getElementById('search-input').value);
  });
});

let searchTimer;
document.getElementById('search-input').addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadCommandes(e.target.value), 300);
});

/* ---------------- Nouvelle commande ---------------- */
async function openCommandeModal() {
  document.getElementById('commande-form').reset();
  document.getElementById('lignes-container').innerHTML = '';
  document.getElementById('commande-form-alert').innerHTML = '';
  ligneIndex = 0;

  try {
    [clientsOptions, produitsOptions] = await Promise.all([
      apiFetch('api/clients.php'),
      apiFetch('api/produits.php'),
    ]);
    const clientSelect = document.getElementById('client-select');
    if (clientsOptions.length === 0) {
      clientSelect.innerHTML = '<option value="">Aucun client — créez-en un d\'abord</option>';
    } else {
      clientSelect.innerHTML = clientsOptions.map(c => `<option value="${c.id}">${escapeHtml(c.nom)} (${escapeHtml(c.telephone)})</option>`).join('');
    }
    ajouterLigne();
    openModal('commande-modal');
  } catch (err) {
    toast(err.message, 'error');
  }
}

function ajouterLigne() {
  if (produitsOptions.length === 0) {
    toast('Ajoutez au moins un produit avant de créer une commande.', 'error');
    return;
  }
  const idx = ligneIndex++;
  const div = document.createElement('div');
  div.className = 'form-row';
  div.id = 'ligne-' + idx;
  div.innerHTML = `
    <label>Produit
      <select onchange="recalculerTotal()" data-role="produit">
        ${produitsOptions.map(p => `<option value="${p.id}" data-prix="${p.prix}" data-stock="${p.quantite_stock}">${escapeHtml(p.nom)} — ${formatFcfa(p.prix)} (stock : ${p.quantite_stock})</option>`).join('')}
      </select>
    </label>
    <label>Quantité
      <div style="display:flex; gap:8px;">
        <input type="number" min="1" value="1" data-role="quantite" oninput="recalculerTotal()">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('ligne-${idx}').remove(); recalculerTotal();">✕</button>
      </div>
    </label>`;
  document.getElementById('lignes-container').appendChild(div);
  recalculerTotal();
}

function recalculerTotal() {
  let total = 0;
  document.querySelectorAll('#lignes-container [id^="ligne-"]').forEach(row => {
    const select = row.querySelector('[data-role="produit"]');
    const qte = Number(row.querySelector('[data-role="quantite"]').value) || 0;
    const prix = Number(select.selectedOptions[0]?.dataset.prix || 0);
    total += prix * qte;
  });
  document.getElementById('commande-total').textContent = formatFcfa(total);
}

document.getElementById('commande-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const alertBox = document.getElementById('commande-form-alert');
  alertBox.innerHTML = '';

  const clientId = document.getElementById('client-select').value;
  const lignes = [];
  document.querySelectorAll('#lignes-container [id^="ligne-"]').forEach(row => {
    const produitId = row.querySelector('[data-role="produit"]').value;
    const quantite = Number(row.querySelector('[data-role="quantite"]').value);
    if (produitId && quantite > 0) lignes.push({ produit_id: Number(produitId), quantite });
  });

  if (!clientId || lignes.length === 0) {
    alertBox.innerHTML = '<div class="alert alert-error">Sélectionnez un client et au moins un produit.</div>';
    return;
  }

  const notes = e.target.notes.value;
  try {
    const res = await apiFetch('api/commandes.php', { method: 'POST', body: { client_id: Number(clientId), lignes, notes } });
    toast('Commande ' + res.reference + ' créée.');
    closeModal('commande-modal');
    loadCommandes();
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

async function deleteCommande(id) {
  if (!confirmAction('Supprimer cette commande ? Cette action est irréversible.')) return;
  try {
    await apiFetch('api/commandes.php?id=' + id, { method: 'DELETE' });
    toast('Commande supprimée.');
    loadCommandes(document.getElementById('search-input').value);
  } catch (err) {
    toast(err.message, 'error');
  }
}

/* ---------------- Détail commande ---------------- */
async function viewCommande(id) {
  try {
    const c = await apiFetch('api/commandes.php?id=' + id);
    document.getElementById('detail-titre').textContent = 'Commande ' + c.reference;

    const lignesHtml = c.lignes.map(l => `
      <tr><td>${escapeHtml(l.produit_nom)}</td><td>${l.quantite}</td><td>${formatFcfa(l.prix_unitaire)}</td><td>${formatFcfa(l.prix_unitaire * l.quantite)}</td></tr>
    `).join('');

    const messageFacture = `Bonjour ${c.client_nom}, voici le récapitulatif de votre commande ${c.reference} :\n` +
      c.lignes.map(l => `- ${l.produit_nom} x${l.quantite} : ${Math.round(l.prix_unitaire * l.quantite).toLocaleString('fr-FR')} FCFA`).join('\n') +
      `\nTotal : ${Math.round(c.total).toLocaleString('fr-FR')} FCFA\nMerci pour votre confiance !`;

    const statuts = ['en_attente', 'validee', 'en_livraison', 'livree', 'annulee'];

    document.getElementById('detail-body').innerHTML = `
      <p><strong>Client :</strong> ${escapeHtml(c.client_nom)} — ${escapeHtml(c.client_telephone)}</p>
      <p><strong>Statut actuel :</strong> <span class="badge badge-${badgeClass(c.statut)}">${statutLabel(c.statut)}</span></p>
      <div class="table-wrap">
        <table><thead><tr><th>Produit</th><th>Qté</th><th>PU</th><th>Sous-total</th></tr></thead>
        <tbody>${lignesHtml}</tbody></table>
      </div>
      <p style="text-align:right; font-weight:700;">Total : ${formatFcfa(c.total)}</p>

      <label>Changer le statut
        <select id="statut-select">
          ${statuts.map(s => `<option value="${s}" ${s === c.statut ? 'selected' : ''}>${statutLabel(s)}</option>`).join('')}
        </select>
      </label>
      <div class="form-actions" style="justify-content:space-between;">
        <a class="btn btn-whatsapp" target="_blank" href="${waLink(c.client_telephone, messageFacture)}">💬 Envoyer la facture sur WhatsApp</a>
        <button class="btn btn-primary" onclick="changerStatut(${c.id})">Mettre à jour le statut</button>
      </div>
    `;
    openModal('detail-modal');
  } catch (err) {
    toast(err.message, 'error');
  }
}

async function changerStatut(id) {
  const statut = document.getElementById('statut-select').value;
  try {
    await apiFetch('api/commandes.php?action=changer_statut', { method: 'POST', body: { id, statut } });
    toast('Statut mis à jour.');
    closeModal('detail-modal');
    loadCommandes(document.getElementById('search-input').value);
  } catch (err) {
    toast(err.message, 'error');
  }
}

loadCommandes();
