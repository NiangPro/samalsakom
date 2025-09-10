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
