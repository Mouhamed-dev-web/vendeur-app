<?php
/**
 * Configuration générale de l'application.
 * Adaptez les constantes DB_* à votre hébergement avant mise en ligne.
 */

// ---- Base de données ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'vendeur_whatsapp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Application ----
define('APP_NAME', 'GestionVendeur');
define('APP_URL', 'http://localhost/vendeur-app'); // à adapter en production
define('DEVISE', 'FCFA');

// ---- Sécurité session ----
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// Activez la ligne suivante en production (HTTPS obligatoire) :
// ini_set('session.cookie_secure', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('vendeur_session');
    session_start();
}

date_default_timezone_set('Africa/Dakar');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
