document.getElementById('infos-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const alertBox = document.getElementById('infos-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/profile.php?action=infos', { method: 'PUT', body: Object.fromEntries(fd.entries()) });
    alertBox.innerHTML = '<div class="alert alert-success">Profil mis à jour.</div>';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});

document.getElementById('password-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const alertBox = document.getElementById('password-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/profile.php?action=password', { method: 'PUT', body: Object.fromEntries(fd.entries()) });
    alertBox.innerHTML = '<div class="alert alert-success">Mot de passe modifié.</div>';
    e.target.reset();
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});
