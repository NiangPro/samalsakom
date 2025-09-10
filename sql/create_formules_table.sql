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
