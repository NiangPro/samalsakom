-- Base de données SamalSakom - Gestion de Tontines et Épargne
-- Création de la base de données et des tables principales

CREATE DATABASE IF NOT EXISTS samalsakom_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE samalsakom_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(20) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    photo_profil VARCHAR(255) DEFAULT NULL,
    date_naissance DATE,
    adresse TEXT,
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des tontines
CREATE TABLE tontines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    montant_cotisation DECIMAL(10,2) NOT NULL,
    frequence ENUM('hebdomadaire', 'mensuelle', 'trimestrielle') NOT NULL,
    nombre_participants INT NOT NULL,
    duree_mois INT NOT NULL DEFAULT 12,
    date_debut DATE NOT NULL,
    date_fin DATE,
    statut ENUM('en_attente', 'active', 'terminee', 'suspendue') DEFAULT 'en_attente',
    createur_id INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (createur_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des participations aux tontines
CREATE TABLE participations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tontine_id INT NOT NULL,
    user_id INT NOT NULL,
    position_tirage INT,
    statut ENUM('en_attente', 'confirme', 'retire') DEFAULT 'en_attente',
    date_participation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tontine_id) REFERENCES tontines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (tontine_id, user_id)
);

-- Table des cotisations
CREATE TABLE cotisations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tontine_id INT NOT NULL,
    participation_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_cotisation DATE NOT NULL,
    statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    type_transaction ENUM('cotisation', 'remboursement', 'penalite') DEFAULT 'cotisation',
    mode_paiement ENUM('orange_money', 'wave', 'virement', 'especes') DEFAULT 'orange_money',
    reference_paiement VARCHAR(100),
    date_paiement TIMESTAMP NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tontine_id) REFERENCES tontines(id) ON DELETE CASCADE,
    FOREIGN KEY (participation_id) REFERENCES participations(id) ON DELETE CASCADE
);

-- Table des contacts/messages
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(20),
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau', 'lu', 'traite') DEFAULT 'nouveau',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des administrateurs
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderateur') DEFAULT 'admin',
    photo_profil VARCHAR(255) DEFAULT NULL,
    derniere_connexion TIMESTAMP NULL,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion de données de test
INSERT INTO users (nom, prenom, email, telephone, mot_de_passe) VALUES
('Diop', 'Fatou', 'fatou.diop@email.com', '771234567', '$2y$10$example_hash'),
('Ndiaye', 'Moussa', 'moussa.ndiaye@email.com', '772345678', '$2y$10$example_hash'),
('Ba', 'Aissatou', 'aissatou.ba@email.com', '773456789', '$2y$10$example_hash');

-- Insertion d'un admin d'exemple (mot de passe: admin123)
INSERT INTO admins (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'SamalSakom', 'admin@samalsakom.sn', '$2y$10$7BVawMEW6iMnutb40qK3b.fENdxcAnxqfLymsiAYzz/rK/3rGMdP2', 'super_admin');

-- Script pour corriger la table cotisations et ajouter les colonnes manquantes

-- Ajouter la colonne description à la table cotisations
ALTER TABLE cotisations ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER reference_paiement;

-- Ajouter la colonne raison_rejet à la table cotisations
ALTER TABLE cotisations ADD COLUMN IF NOT EXISTS raison_rejet TEXT NULL AFTER description;

-- Ajouter la colonne date_validation à la table cotisations
ALTER TABLE cotisations ADD COLUMN IF NOT EXISTS date_validation TIMESTAMP NULL AFTER raison_rejet;

-- Créer la table admin_logs si elle n'existe pas
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Vérifier la structure de la table cotisations
DESCRIBE cotisations;

-- Ajouter la table notifications si elle n'existe pas
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'payment', 'tontine') DEFAULT 'info',
    lu TINYINT(1) DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, lu, date_creation)
);

-- Ajouter quelques notifications de test
INSERT INTO notifications (user_id, titre, message, type, lu) VALUES
(1, 'Bienvenue sur SamalSakom', 'Votre compte a été créé avec succès. Découvrez nos tontines disponibles !', 'success', 0),
(1, 'Nouvelle tontine disponible', 'Une nouvelle tontine "Épargne Solidaire" vient d\'être créée. Rejoignez-la dès maintenant !', 'tontine', 0),
(2, 'Paiement en attente', 'Votre cotisation pour la tontine "Épargne Famille" est due dans 3 jours.', 'warning', 0);

-- Ajouter la colonne duree_mois à la table tontines si elle n'existe pas
ALTER TABLE tontines ADD COLUMN IF NOT EXISTS duree_mois INT DEFAULT 12 COMMENT 'Durée de la tontine en mois';

-- Mettre à jour les tontines existantes avec une durée par défaut
UPDATE tontines SET duree_mois = 12 WHERE duree_mois IS NULL;

-- Ajouter la colonne date_retrait à la table participations si elle n'existe pas
ALTER TABLE participations ADD COLUMN IF NOT EXISTS date_retrait TIMESTAMP NULL COMMENT 'Date de retrait de la tontine';

-- Modifier la table cotisations pour ajouter de nouveaux statuts et colonnes
ALTER TABLE cotisations MODIFY COLUMN statut ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending';

-- Ajouter la colonne description à la table cotisations
ALTER TABLE cotisations ADD COLUMN description TEXT NULL AFTER reference_paiement;

-- Ajouter la colonne raison_rejet à la table cotisations
ALTER TABLE cotisations ADD COLUMN raison_rejet TEXT NULL AFTER description;

-- Ajouter la colonne date_validation à la table cotisations
ALTER TABLE cotisations ADD COLUMN date_validation TIMESTAMP NULL AFTER raison_rejet;

-- Création de la table pour gérer les formules de services dynamiquement

CREATE TABLE IF NOT EXISTS formules_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0,
    devise VARCHAR(10) DEFAULT 'FCFA',
    periode VARCHAR(50) DEFAULT 'mois',
    description TEXT,
    couleur VARCHAR(20) DEFAULT 'primary',
    populaire BOOLEAN DEFAULT FALSE,
    ordre_affichage INT DEFAULT 0,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Création de la table pour les fonctionnalités des formules
CREATE TABLE IF NOT EXISTS formule_fonctionnalites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formule_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    inclus BOOLEAN DEFAULT TRUE,
    icone VARCHAR(50) DEFAULT 'fas fa-check',
    ordre_affichage INT DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formule_id) REFERENCES formules_services(id) ON DELETE CASCADE
);

-- Insertion des formules existantes
INSERT INTO formules_services (nom, prix, periode, description, couleur, populaire, ordre_affichage) VALUES
('Basique', 0, 'Gratuit', 'Pour débuter', 'outline-primary', FALSE, 1),
('Premium', 2500, 'mois', 'Par mois', 'primary', TRUE, 2),
('Business', 5000, 'mois', 'Par mois', 'secondary', FALSE, 3);

-- Insertion des fonctionnalités pour la formule Basique (ID = 1)
INSERT INTO formule_fonctionnalites (formule_id, nom, inclus, ordre_affichage) VALUES
(1, 'Jusqu\'à 2 tontines', TRUE, 1),
(1, '10 participants max par tontine', TRUE, 2),
(1, 'Notifications SMS', TRUE, 3),
(1, 'Support par email', TRUE, 4),
(1, 'Épargne automatique', FALSE, 5),
(1, 'Microcrédits', FALSE, 6);

-- Insertion des fonctionnalités pour la formule Premium (ID = 2)
INSERT INTO formule_fonctionnalites (formule_id, nom, inclus, ordre_affichage) VALUES
(2, 'Tontines illimitées', TRUE, 1),
(2, '50 participants max par tontine', TRUE, 2),
(2, 'Épargne automatique', TRUE, 3),
(2, 'Tableaux de bord avancés', TRUE, 4),
(2, 'Formation financière', TRUE, 5),
(2, 'Support prioritaire', TRUE, 6);

-- Insertion des fonctionnalités pour la formule Business (ID = 3)
INSERT INTO formule_fonctionnalites (formule_id, nom, inclus, ordre_affichage) VALUES
(3, 'Toutes les fonctionnalités Premium', TRUE, 1),
(3, 'Participants illimités', TRUE, 2),
(3, 'Microcrédits jusqu\'à 500 000 FCFA', TRUE, 3),
(3, 'API pour intégrations', TRUE, 4),
(3, 'Gestionnaire de compte dédié', TRUE, 5),
(3, 'Rapports personnalisés', TRUE, 6);
