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
<title>Créer un compte · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <h1>Créer votre compte</h1>
  <p class="auth-sub">Commencez à gérer vos ventes WhatsApp en quelques minutes.</p>
  <div id="form-alert"></div>
  <form id="register-form">
    <label>Nom complet
      <input type="text" name="nom" required autofocus>
    </label>
    <label>Nom de la boutique (optionnel)
      <input type="text" name="boutique">
    </label>
    <label>Téléphone
      <input type="text" name="telephone" placeholder="77 123 45 67" required>
    </label>
    <label>Adresse email
      <input type="email" name="email" required>
    </label>
    <label>Mot de passe (6 caractères minimum)
      <input type="password" name="password" minlength="6" required>
    </label>
    <button type="submit" class="btn btn-primary">Créer mon compte</button>
  </form>
  <div class="auth-switch">Déjà un compte ? <a href="login.php">Se connecter</a></div>
</div>
<script src="assets/js/app.js"></script>
<script>
document.getElementById('register-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const alertBox = document.getElementById('form-alert');
  alertBox.innerHTML = '';
  try {
    await apiFetch('api/auth.php?action=register', {
      method: 'POST',
      body: Object.fromEntries(fd.entries()),
    });
    alertBox.innerHTML = `<div class="alert alert-success">Compte créé ! Redirection vers la connexion…</div>`;
    setTimeout(() => window.location.href = 'login.php', 1200);
  } catch (err) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
  }
});
</script>
</body>
</html>
