let produitsCache = [];
let currentFilter = 'tous';

async function loadProduits(query = '') {
  const tbody = document.getElementById('produits-body');
  try {
    let url = 'api/produits.php?q=' + encodeURIComponent(query);
    if (currentFilter === 'rupture') url += '&rupture=1';
    const produits = await apiFetch(url);
    produitsCache = produits;
    if (produits.length === 0) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Aucun produit trouvé.</td></tr>';
      return;
    }
    tbody.innerHTML = produits.map(p => {
      const bas = Number(p.quantite_stock) <= Number(p.seuil_alerte);
      return `
      <tr>
        <td><strong>${escapeHtml(p.nom)}</strong></td>
        <td>${escapeHtml(p.categorie || '—')}</td>
        <td>${formatFcfa(p.prix)}</td>
        <td class="${bas ? 'stock-low' : ''}">${p.quantite_stock} ${bas ? '⚠️' : ''}</td>
        <td style="white-space:nowrap;">
          <button class="icon-btn" onclick="openStockModal(${p.id})" title="Mouvement de stock">📦</button>
          <button class="icon-btn" onclick="editProduit(${p.id})" title="Modifier">✏️</button>
          <button class="icon-btn" onclick="deleteProduit(${p.id})" title="Supprimer">🗑️</button>
        </td>
      </tr>`;
    }).join('');
  } catch (err) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">${escapeHtml(err.message)}</td></tr>`;
  }
}

function setFilter(filter, el) {
  currentFilter = filter;
  document.querySelectorAll('.pill-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  loadProduits(document.getElementById('search-input').value);
}

function openProduitModal() {
  const form = document.getElementById('produit-form');
  form.reset();
  form.id.value = '';
  document.getElementById('stock-initial-row').style.display = '';
  document.getElementById('produit-modal-title').textContent = 'Nouveau produit';
  document.getElementById('produit-form-alert').innerHTML = '';
  openModal('produit-modal');
}

function editProduit(id) {
  const p = produitsCache.find(x => x.id === id);
  if (!p) return;
  const form = document.getElementById('produit-form');
  form.id.value = p.id;
  form.nom.value = p.nom;
  form.categorie.value = p.categorie || '';
  form.prix.value = p.prix;
  form.description.value = p.description || '';
  form.seuil_alerte.value = p.seuil_alerte;
  document.getElementById('stock-initial-row').style.display = 'none'; // le stock se gère via les mouvements
  document.getElementById('produit-modal-title').textContent = 'Modifier le produit';
  document.getElementById('produit-form-alert').innerHTML = '';
  openModal('produit-modal');
}

async function deleteProduit(id) {
  if (!confirmAction('Supprimer ce produit ? Son historique de stock sera également supprimé.')) return;
  try {
    await apiFetch('api/produits.php?id=' + id, { method: 'DELETE' });
    toast('Produit supprimé.');
    loadProduits(document.getElementById('search-input').value);
  } catch (err) {
    toast(err.message, 'error');
  }
}

document.getElementById('produit-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  const alertBox = document.getElementById('produit-form-alert');
  alertBox.innerHTML = '';
  try {
    if (payload.id) {
      await apiFetch('api/produits.php', { method: 'PUT', body: payload });
      toast('Produit mis à jour.');
    } else {
      await apiFetch('api/produits.php', { method: 'POST', body: payload });
      toast('Produit ajouté.');
    }
    closeModal('produit-modal');
    loadProduits(document.getElementById('search-input').value);
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

function openStockModal(produitId) {
  const p = produitsCache.find(x => x.id === produitId);
  if (!p) return;
  document.getElementById('stock-produit-nom').textContent = p.nom + ' (stock actuel : ' + p.quantite_stock + ')';
  const form = document.getElementById('stock-form');
  form.reset();
  form.produit_id.value = produitId;
  document.getElementById('stock-form-alert').innerHTML = '';
  openModal('stock-modal');
}

document.getElementById('stock-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  const alertBox = document.getElementById('stock-form-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/produits.php?action=mouvement', { method: 'POST', body: payload });
    toast('Stock mis à jour.');
    closeModal('stock-modal');
    loadProduits(document.getElementById('search-input').value);
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

let searchTimer;
document.getElementById('search-input').addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadProduits(e.target.value), 300);
});

loadProduits();
