-- Script pour mettre à jour la table cotisations pour supporter les recharges et retraits
-- Exécuter ce script si la table cotisations existe déjà

-- Ajouter les nouvelles colonnes si elles n'existent pas
ALTER TABLE cotisations 
MODIFY COLUMN tontine_id INT NULL,
MODIFY COLUMN participation_id INT NULL,
MODIFY COLUMN date_cotisation DATE NULL;

-- Ajouter les nouveaux types de transaction
ALTER TABLE cotisations 
MODIFY COLUMN type_transaction ENUM('cotisation', 'remboursement', 'penalite', 'recharge', 'retrait', 'bonus') DEFAULT 'cotisation';

-- Ajouter les nouvelles colonnes pour les transactions financières
ALTER TABLE cotisations 
ADD COLUMN IF NOT EXISTS methode_paiement VARCHAR(50) NULL AFTER type_transaction,
ADD COLUMN IF NOT EXISTS numero_telephone VARCHAR(20) NULL AFTER methode_paiement,
ADD COLUMN IF NOT EXISTS compte_bancaire VARCHAR(50) NULL AFTER numero_telephone,
ADD COLUMN IF NOT EXISTS motif TEXT NULL AFTER compte_bancaire,
ADD COLUMN IF NOT EXISTS date_confirmation TIMESTAMP NULL AFTER date_paiement;

-- Supprimer l'ancienne colonne mode_paiement si elle existe
ALTER TABLE cotisations DROP COLUMN IF EXISTS mode_paiement;
