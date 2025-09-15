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
    tontine_id INT NULL,
    participation_id INT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_cotisation DATE NULL,
    statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    type_transaction ENUM('cotisation', 'remboursement', 'penalite', 'recharge', 'retrait', 'bonus') DEFAULT 'cotisation',
    methode_paiement VARCHAR(50) NULL,
    numero_telephone VARCHAR(20) NULL,
    compte_bancaire VARCHAR(50) NULL,
    motif TEXT NULL,
    reference_paiement VARCHAR(100),
    date_paiement TIMESTAMP NULL,
    date_confirmation TIMESTAMP NULL,
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
