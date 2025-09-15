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
