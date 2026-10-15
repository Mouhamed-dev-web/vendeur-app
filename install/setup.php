<?php
/**
 * À exécuter UNE SEULE FOIS après avoir importé config/schema.sql,
 * pour créer le premier compte administrateur.
 * Supprimez ce fichier (ou tout le dossier /install) une fois terminé.
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$adminExists = (int) $stmt->fetchColumn() > 0;

$message = '';
$success = false;

if ($adminExists) {
    $message = 'Un compte administrateur existe déjà. Supprimez ce fichier pour des raisons de sécurité.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = clean_str($_POST['nom'] ?? '');
    $email = clean_str($_POST['email'] ?? '');
    $telephone = clean_str($_POST['telephone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($nom === '' || $email === '' || strlen($password) < 8) {
        $message = 'Merci de remplir tous les champs (mot de passe : 8 caractères minimum).';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (nom, email, telephone, password_hash, role) VALUES (?, ?, ?, ?, "admin")');
        $stmt->execute([$nom, $email, $telephone, $hash]);
        $success = true;
        $message = 'Compte administrateur créé avec succès. Supprimez maintenant ce dossier /install, puis connectez-vous.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation - <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <h1>Installation initiale</h1>
    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (!$adminExists && !$success): ?>
    <form method="post">
        <label>Nom complet<input type="text" name="nom" required></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Téléphone<input type="text" name="telephone" required></label>
        <label>Mot de passe<input type="password" name="password" minlength="8" required></label>
        <button type="submit" class="btn btn-primary">Créer le compte administrateur</button>
    </form>
    <?php else: ?>
        <a href="../login.php" class="btn btn-primary">Aller à la connexion</a>
    <?php endif; ?>
</div>
</body>
</html>
