# GestionVendeur — Application de gestion pour vendeurs WhatsApp

Application web complète (PHP / MySQL / JavaScript) pour aider les vendeurs
qui gèrent leur activité sur WhatsApp : clients, produits & stock,
commandes, paiements, livraisons et statistiques — avec envoi de messages
et factures directement via des liens WhatsApp (wa.me).

## 1. Prérequis

- PHP 8.0 ou plus (extensions `pdo_mysql` activée)
- MySQL / MariaDB 5.7+
- Un serveur web (Apache avec `mod_rewrite`/`.htaccess`, ou LAMP/WAMP en local)

## 2. Installation

1. **Copier les fichiers** sur votre hébergement (ou dans le dossier de votre
   serveur local, ex: `htdocs/vendeur-app` pour WAMP/XAMPP).

2. **Créer la base de données** : importez `config/schema.sql`
   ```bash
   mysql -u root -p < config/schema.sql
   ```
   ou via phpMyAdmin : créez une base `vendeur_whatsapp` puis importez le fichier.

3. **Configurer la connexion** dans `config/config.php` :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'vendeur_whatsapp');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   define('APP_URL', 'https://votredomaine.com'); // adapter
   ```

4. **Créer le compte administrateur** : ouvrez dans votre navigateur
   `https://votredomaine.com/install/setup.php` et remplissez le formulaire.
   ⚠️ **Supprimez le dossier `/install` juste après**, pour éviter que
   quelqu'un d'autre ne puisse créer un compte admin.

5. **Se connecter** sur `login.php` avec le compte administrateur, ou créer
   un compte vendeur via `register.php`.

## 3. Rôles de l'application

| Rôle      | Accès |
|-----------|-------|
| **admin**   | Tout : supervision globale, gestion des comptes utilisateurs |
| **vendeur** | Ses propres clients, produits, commandes, paiements, livraisons, statistiques |
| **livreur** | Uniquement les livraisons qui lui sont assignées (mise à jour du statut) |

Un vendeur crée lui-même ses comptes livreurs depuis **Utilisateurs**.

## 4. Fonctionnalités livrées (MVP)

- Authentification sécurisée (bcrypt, CSRF, sessions, contrôle par rôle)
- Gestion des clients (recherche, notes, historique de commandes)
- Gestion des produits avec suivi de stock et historique des mouvements
  (entrée / sortie / ajustement), alerte de rupture par seuil
- Création de commandes multi-produits avec calcul automatique du total et
  décrémentation du stock, génération d'une référence unique
- Suivi des paiements (espèces, Orange Money, Wave, virement) avec statut
  (en attente / partiel / payé)
- Suivi des livraisons : assignation à un livreur, mise à jour du statut,
  mise à jour automatique de la commande une fois livrée
- Tableau de bord et page statistiques : chiffre d'affaires, commandes,
  ventes des 7 derniers jours, top produits vendus (graphiques Chart.js)
- Envoi de messages et factures récapitulatives directement sur WhatsApp
  via des liens `wa.me` (aucun compte Business API requis)
- Mode sombre / clair, interface responsive (mobile & desktop)

## 5. Roadmap suggérée (V2 / Premium — non incluses ici)

- V2 : rôles/permissions plus fins, export PDF des factures, notifications
  SMS/Email automatiques, relances clients, multi-boutiques
- Premium : API WhatsApp Business officielle (envoi automatique, réponses
  automatiques), suggestions IA (réapprovisionnement, prévisions de ventes),
  tableau de bord multi-vendeurs pour une franchise

## 6. Sécurité

- Mots de passe hashés (bcrypt), jamais stockés en clair
- Jetons CSRF sur toutes les requêtes qui modifient des données
- Requêtes SQL préparées (PDO) partout — aucune concaténation de requêtes
- Accès aux données strictement filtré par utilisateur (un vendeur ne voit
  jamais les données d'un autre vendeur)
- Dossiers `config/` et `includes/` protégés contre l'accès direct (.htaccess)

## 7. Structure du projet

```
vendeur-app/
├── api/            Points d'entrée JSON (clients, produits, commandes...)
├── assets/         CSS, JS, images
├── config/         Configuration + schema.sql (protégé)
├── includes/       Fonctions PHP partagées (protégé)
├── install/        Création du 1er compte admin (à supprimer après usage)
├── *.php           Pages de l'application (dashboard, clients, etc.)
```

## 8. Prochaines étapes possibles

Dites-moi si vous souhaitez que j'ajoute : export PDF des factures,
notifications par email, gestion multi-boutiques, ou l'intégration réelle
de l'API WhatsApp Business.
