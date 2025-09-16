-- Script pour créer des données de test pour les cotisations
-- Exécuter ce script pour tester la section paiements

-- Insérer des tontines de test si elles n'existent pas
INSERT IGNORE INTO tontines (id, nom, description, montant_cotisation, frequence, nombre_participants, duree_mois, date_debut, statut, createur_id) VALUES
(1, 'Tontine Solidarité', 'Tontine mensuelle pour l\'épargne collective', 50000.00, 'mensuelle', 10, 12, '2024-01-01', 'active', 1),
(2, 'Tontine Commerce', 'Tontine pour les commerçants', 25000.00, 'hebdomadaire', 8, 6, '2024-02-01', 'active', 1),
(3, 'Tontine Famille', 'Tontine familiale trimestrielle', 100000.00, 'trimestrielle', 6, 12, '2024-03-01', 'active', 1);

-- Insérer des participations de test
INSERT IGNORE INTO participations (id, tontine_id, user_id, position_tirage, statut) VALUES
(1, 1, 1, 1, 'confirme'),
(2, 2, 1, 2, 'confirme'),
(3, 3, 1, 3, 'confirme');

-- Insérer des cotisations en attente (pending)
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction) VALUES
(1, 1, 1, 50000.00, '2024-12-20', 'pending', 'cotisation'),
(1, 2, 2, 25000.00, '2024-12-18', 'pending', 'cotisation'),
(1, 3, 3, 100000.00, '2024-12-25', 'pending', 'cotisation'),
(1, 1, 1, 50000.00, '2024-11-20', 'pending', 'cotisation');

-- Insérer des cotisations payées (completed) pour l'historique
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, methode_paiement, reference_paiement, date_paiement) VALUES
(1, 1, 1, 50000.00, '2024-10-20', 'completed', 'cotisation', 'orange_money', 'PAY_1701234567_1', '2024-10-20 14:30:00'),
(1, 2, 2, 25000.00, '2024-10-15', 'completed', 'cotisation', 'wave', 'PAY_1701234568_2', '2024-10-15 10:15:00'),
(1, 3, 3, 100000.00, '2024-09-25', 'completed', 'cotisation', 'virement', 'PAY_1701234569_3', '2024-09-25 16:45:00'),
(1, 1, 1, 50000.00, '2024-09-20', 'completed', 'cotisation', 'orange_money', 'PAY_1701234570_4', '2024-09-20 11:20:00');

-- Insérer quelques paiements échoués pour tester
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, methode_paiement, reference_paiement, date_paiement) VALUES
(1, 2, 2, 25000.00, '2024-08-15', 'failed', 'cotisation', 'orange_money', 'PAY_1701234571_5', '2024-08-15 09:30:00');

-- Insérer des transactions de portefeuille (recharge/retrait) pour l'historique
INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, methode_paiement, reference_paiement, date_paiement, motif) VALUES
(1, NULL, NULL, 75000.00, NULL, 'completed', 'recharge', 'orange_money', 'RCH_1701234572_6', '2024-11-15 13:45:00', 'Recharge de portefeuille'),
(1, NULL, NULL, 30000.00, NULL, 'completed', 'retrait', 'wave', 'RET_1701234573_7', '2024-11-10 15:20:00', 'Retrait de fonds');
