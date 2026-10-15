<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Connexion · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <h1><?= e(APP_NAME) ?></h1>
  <p class="auth-sub">Gérez vos clients, commandes et livraisons WhatsApp au même endroit.</p>
  <div id="form-alert"></div>
  <form id="login-form">
    <label>Adresse email
      <input type="email" name="email" required autofocus>
    </label>
    <label>Mot de passe
      <input type="password" name="password" required>
    </label>
    <button type="submit" class="btn btn-primary">Se connecter</button>
  </form>
  <div class="auth-switch">Pas encore de compte ? <a href="register.php">Créer un compte vendeur</a></div>
</div>
<script src="assets/js/app.js"></script>
<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const alertBox = document.getElementById('form-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/auth.php?action=login', {
      method: 'POST',
      body: { email: fd.get('email'), password: fd.get('password') },
    });
    window.location.href = 'dashboard.php';
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});
</script>
</body>
</html>
