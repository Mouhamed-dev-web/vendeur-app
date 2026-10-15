/* =========================================================
   GestionVendeur — JS partagé (toutes les pages)
   ========================================================= */

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

/** Appelle un endpoint de l'API et retourne data (lève une erreur avec .message si échec). */
async function apiFetch(url, { method = 'GET', body = null } = {}) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (method !== 'GET') opts.headers['X-CSRF-Token'] = CSRF_TOKEN;
  if (body !== null) opts.body = JSON.stringify(body);

  const res = await fetch(url, opts);
  let json;
  try { json = await res.json(); }
  catch { throw new Error('Réponse invalide du serveur.'); }

  if (!json.success) {
    const err = new Error(json.message || 'Une erreur est survenue.');
    err.payload = json;
    throw err;
  }
  return json.data;
}

/** Affiche une notification toast en haut à droite. */
function toast(message, type = 'success') {
  let zone = document.getElementById('toast-zone');
  if (!zone) {
    zone = document.createElement('div');
    zone.id = 'toast-zone';
    document.body.appendChild(zone);
  }
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = message;
  zone.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

/** Échappe du texte pour une insertion sûre dans du HTML généré en JS. */
function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

function formatFcfa(n) {
  return Math.round(Number(n) || 0).toLocaleString('fr-FR') + ' FCFA';
}

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso.replace(' ', 'T'));
  if (isNaN(d)) return iso;
  return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

/* ---------------- Thème clair / sombre ---------------- */
(function initTheme() {
  const saved = localStorage.getItem('gv_theme');
  const theme = saved || 'light';
  document.documentElement.setAttribute('data-theme', theme);
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('theme-toggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  });
})();

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('gv_theme', next);
  const btn = document.getElementById('theme-toggle');
  if (btn) btn.textContent = next === 'dark' ? '☀️' : '🌙';
}

/* ---------------- Sidebar mobile ---------------- */
function toggleSidebar() {
  document.querySelector('.sidebar')?.classList.toggle('open');
}

/* ---------------- Modales ---------------- */
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-backdrop')) e.target.classList.remove('open');
});

/* ---------------- Confirmation suppression ---------------- */
function confirmAction(message) {
  return window.confirm(message);
}

/* ---------------- Lien WhatsApp ---------------- */
function waLink(phone, message = '') {
  let digits = String(phone).replace(/\D/g, '');
  if (digits.length === 9) digits = '221' + digits;
  let url = 'https://wa.me/' + digits;
  if (message) url += '?text=' + encodeURIComponent(message);
  return url;
}
