-- Script pour créer des données de test pour la tontine "natt ouverture"
-- avec des paiements en retard pour tester l'affichage des retards

-- Insérer la tontine "natt ouverture" si elle n'existe pas
INSERT IGNORE INTO tontines (id, nom, description, montant_cotisation, frequence, nombre_participants, duree_mois, date_debut, statut, createur_id) VALUES
(4, 'natt ouverture', 'Tontine pour l\'ouverture de nouveaux commerces', 75000.00, 'mensuelle', 12, 18, '2024-01-15', 'active', 1);

-- Insérer une participation pour l'utilisateur 1 dans cette tontine
INSERT IGNORE INTO participations (id, tontine_id, user_id, position_tirage, statut) VALUES
(4, 4, 1, 4, 'confirme');

-- Insérer des cotisations EN RETARD pour la tontine "natt ouverture"
-- Ces cotisations ont des dates d'échéance dans le passé
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction) VALUES
-- Cotisation en retard de 15 jours (échéance: 2024-12-01)
(1, 4, 4, 75000.00, '2024-12-01', 'pending', 'cotisation'),
-- Cotisation en retard de 25 jours (échéance: 2024-11-22)
(1, 4, 4, 75000.00, '2024-11-22', 'pending', 'cotisation'),
-- Cotisation en retard de 35 jours (échéance: 2024-11-12)
(1, 4, 4, 75000.00, '2024-11-12', 'pending', 'cotisation');

-- Insérer aussi quelques cotisations à venir pour cette tontine
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction) VALUES
-- Cotisation future (échéance: 2025-01-15)
(1, 4, 4, 75000.00, '2025-01-15', 'pending', 'cotisation'),
-- Cotisation future (échéance: 2025-02-15)
(1, 4, 4, 75000.00, '2025-02-15', 'pending', 'cotisation');

-- Insérer quelques paiements complétés pour l'historique
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, methode_paiement, reference_paiement, date_paiement) VALUES
(1, 4, 4, 75000.00, '2024-10-15', 'completed', 'cotisation', 'orange_money', 'PAY_NATT_001', '2024-10-15 14:30:00'),
(1, 4, 4, 75000.00, '2024-09-15', 'completed', 'cotisation', 'wave', 'PAY_NATT_002', '2024-09-15 16:45:00');
