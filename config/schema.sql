-- =========================================================
-- Application de gestion pour vendeurs WhatsApp
-- Schéma de base de données MySQL
-- =========================================================

CREATE DATABASE IF NOT EXISTS vendeur_whatsapp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE vendeur_whatsapp;

-- ---------------------------------------------------------
-- Utilisateurs (vendeur / livreur / admin)
-- ---------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  telephone VARCHAR(30) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','vendeur','livreur') NOT NULL DEFAULT 'vendeur',
  boutique_nom VARCHAR(150) DEFAULT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Clients d'un vendeur
-- ---------------------------------------------------------
CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  adresse VARCHAR(255) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_clients_user (user_id),
  INDEX idx_clients_telephone (telephone)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Produits
-- ---------------------------------------------------------
CREATE TABLE produits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  categorie VARCHAR(100) DEFAULT NULL,
  prix DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantite_stock INT NOT NULL DEFAULT 0,
  seuil_alerte INT NOT NULL DEFAULT 5,
  image VARCHAR(255) DEFAULT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_produits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_produits_user (user_id),
  INDEX idx_produits_categorie (categorie)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Mouvements de stock (historique entrées / sorties)
-- ---------------------------------------------------------
CREATE TABLE stock_mouvements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id INT UNSIGNED NOT NULL,
  type ENUM('entree','sortie','ajustement') NOT NULL,
  quantite INT NOT NULL,
  motif VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mouv_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
  INDEX idx_mouv_produit (produit_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Commandes
-- ---------------------------------------------------------
CREATE TABLE commandes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  client_id INT UNSIGNED NOT NULL,
  reference VARCHAR(30) NOT NULL UNIQUE,
  statut ENUM('en_attente','validee','en_livraison','livree','annulee') NOT NULL DEFAULT 'en_attente',
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_commandes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_commandes_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_commandes_user (user_id),
  INDEX idx_commandes_statut (statut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Lignes de commande
-- ---------------------------------------------------------
CREATE TABLE commande_lignes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL,
  produit_id INT UNSIGNED NOT NULL,
  quantite INT NOT NULL,
  prix_unitaire DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_lignes_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_lignes_produit FOREIGN KEY (produit_id) REFERENCES produits(id),
  INDEX idx_lignes_commande (commande_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Paiements
-- ---------------------------------------------------------
CREATE TABLE paiements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL,
  montant DECIMAL(12,2) NOT NULL,
  mode ENUM('especes','orange_money','wave','virement','autre') NOT NULL DEFAULT 'especes',
  statut ENUM('en_attente','partiel','paye') NOT NULL DEFAULT 'en_attente',
  date_paiement DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_paiements_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  INDEX idx_paiements_commande (commande_id),
  INDEX idx_paiements_statut (statut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Livraisons
-- ---------------------------------------------------------
CREATE TABLE livraisons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL,
  livreur_id INT UNSIGNED DEFAULT NULL,
  adresse VARCHAR(255) NOT NULL,
  statut ENUM('en_attente','en_cours','livree','echouee') NOT NULL DEFAULT 'en_attente',
  date_prevue DATE DEFAULT NULL,
  date_livraison DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_livraisons_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_livraisons_livreur FOREIGN KEY (livreur_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_livraisons_statut (statut),
  INDEX idx_livraisons_livreur (livreur_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Le compte administrateur n'est PAS inséré ici (pour ne jamais
-- livrer de mot de passe par défaut connu). Il est créé une seule
-- fois via install/setup.php après la mise en ligne, voir le README.
-- ---------------------------------------------------------
