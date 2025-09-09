# SamalSakom - Plateforme de Gestion de Tontines

## 📋 Description

SamalSakom est une plateforme web moderne et sécurisée pour la gestion de tontines et de comptes d'épargne au Sénégal. Elle digitalise les pratiques traditionnelles de tontines avec une interface utilisateur moderne et ergonomique.

## 🚀 Fonctionnalités

### Site Visiteur
- **Page d'accueil** : Présentation de la plateforme avec animations
- **À propos** : Histoire et mission de SamalSakom
- **Services** : Description détaillée des services offerts
- **Contact** : Formulaire de contact fonctionnel
- **Inscription/Connexion** : Système d'authentification sécurisé

### Dashboard Administrateur
- **Tableau de bord** : Vue d'ensemble avec statistiques et graphiques
- **Gestion des utilisateurs** : CRUD complet avec recherche et filtres
- **Gestion des tontines** : Administration complète des tontines
- **Gestion des messages** : Interface de messagerie avec actions groupées
- **Pages de détails** : Vues détaillées pour utilisateurs et tontines
- **Authentification admin** : Système de connexion sécurisé

## 🛠️ Technologies Utilisées

- **Backend** : PHP 8+ avec PDO
- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Framework CSS** : Bootstrap 5.3
- **Base de données** : MySQL 8+
- **Icônes** : Font Awesome 6
- **Graphiques** : Chart.js
- **Animations** : AOS (Animate On Scroll)

## 📦 Installation

### Prérequis
- XAMPP (Apache, MySQL, PHP 8+)
- Navigateur web moderne
- Éditeur de code (recommandé : VS Code)

### Étapes d'installation

1. **Cloner ou télécharger le projet**
   ```bash
   git clone [URL_DU_REPO]
   # ou télécharger et extraire dans c:\xampp\htdocs\samalsakom
   ```

2. **Démarrer XAMPP**
   - Lancer XAMPP Control Panel
   - Démarrer Apache et MySQL

3. **Créer la base de données**
   - Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
   - Créer une base de données nommée `samalsakom`
   - Importer le fichier `sql/database_setup.sql`

4. **Configurer la base de données**
   - Ouvrir `config/database.php`
   - Vérifier les paramètres de connexion :
     ```php
     private $host = "localhost";
     private $db_name = "samalsakom";
     private $username = "root";
     private $password = "";
     ```

5. **Accéder à l'application**
   - Site visiteur : http://localhost/samalsakom
   - Admin : http://localhost/samalsakom/admin-login.php

## 🔐 Comptes de Test

### Administrateur
- **Email** : admin@samalsakom.sn
- **Mot de passe** : admin123

### Utilisateur Test
- **Email** : test@example.com
- **Mot de passe** : test123

## 📁 Structure du Projet

```
samalsakom/
├── admin/                      # Dashboard administrateur
│   ├── assets/
│   │   ├── css/
│   │   │   └── admin.css      # Styles admin
│   │   └── js/
│   │       └── admin.js       # Scripts admin
│   ├── includes/
│   │   ├── header.php         # En-tête admin
│   │   └── footer.php         # Pied de page admin
│   ├── actions/               # Actions AJAX
│   │   ├── toggle_user_status.php
│   │   ├── change_tontine_status.php
│   │   ├── get_message.php
│   │   └── ...
│   ├── index.php              # Dashboard principal
│   ├── users.php              # Gestion utilisateurs
│   ├── tontines.php           # Gestion tontines
│   ├── messages.php           # Gestion messages
│   ├── user-details.php       # Détails utilisateur
│   └── tontine-details.php    # Détails tontine
├── assets/
│   ├── css/
│   │   └── style.css          # Styles principaux
│   └── js/
│       └── main.js            # Scripts principaux
├── config/
│   └── database.php           # Configuration BDD
├── includes/
│   ├── header.php             # En-tête site
│   └── footer.php             # Pied de page site
├── sql/
│   └── database_setup.sql     # Script de création BDD
├── index.php                  # Page d'accueil
├── about.php                  # À propos
├── services.php               # Services
├── contact.php                # Contact
├── login.php                  # Connexion
├── register.php               # Inscription
├── admin-login.php            # Connexion admin
└── README.md                  # Ce fichier
```

## 🎨 Fonctionnalités Techniques

### Sécurité
- Mots de passe hachés avec `password_hash()`
- Requêtes préparées PDO contre l'injection SQL
- Validation côté client et serveur
- Sessions sécurisées
- Protection CSRF (à implémenter en production)

### Interface Utilisateur
- Design responsive (mobile-first)
- Animations fluides avec AOS
- Interface moderne avec Bootstrap 5
- Sidebar admin responsive
- Notifications toast
- Modales interactives

### Base de Données
- Structure normalisée
- Relations avec clés étrangères
- Index pour les performances
- Données de test incluses

## 🔧 Configuration Avancée

### Variables d'Environnement
Créer un fichier `.env` pour la production :
```env
DB_HOST=localhost
DB_NAME=samalsakom
DB_USER=root
DB_PASS=
ADMIN_EMAIL=admin@samalsakom.sn
```

### Optimisations Apache
Ajouter dans `.htaccess` :
```apache
# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

## 📊 Base de Données

### Tables Principales
- **users** : Utilisateurs de la plateforme
- **tontines** : Tontines créées
- **participations** : Participation aux tontines
- **cotisations** : Historique des cotisations
- **contacts** : Messages de contact
- **admins** : Administrateurs du système

### Relations
- Un utilisateur peut créer plusieurs tontines
- Un utilisateur peut participer à plusieurs tontines
- Une tontine peut avoir plusieurs participants
- Un participant peut avoir plusieurs cotisations

## 🚀 Déploiement en Production

### Checklist de Déploiement
- [ ] Changer les mots de passe par défaut
- [ ] Activer HTTPS
- [ ] Configurer les sauvegardes BDD
- [ ] Optimiser les performances
- [ ] Configurer les logs d'erreur
- [ ] Tester sur différents navigateurs
- [ ] Valider la sécurité

### Hébergement Recommandé
- **Serveur** : VPS avec PHP 8+, MySQL 8+
- **SSL** : Certificat Let's Encrypt
- **CDN** : Pour les assets statiques
- **Monitoring** : Uptime et performances

## 🐛 Dépannage

### Problèmes Courants

1. **Erreur de connexion BDD**
   - Vérifier que MySQL est démarré
   - Contrôler les paramètres dans `config/database.php`

2. **Page blanche**
   - Activer l'affichage des erreurs PHP
   - Vérifier les logs Apache

3. **CSS/JS non chargés**
   - Vérifier les chemins dans les includes
   - Contrôler les permissions des fichiers

4. **Session non persistante**
   - Vérifier la configuration PHP des sessions
   - Contrôler les cookies du navigateur

## 📈 Évolutions Futures

### Fonctionnalités Prévues
- [ ] API REST pour mobile
- [ ] Intégration Orange Money / Wave
- [ ] Notifications push
- [ ] Tableau de bord utilisateur
- [ ] Système de rapports avancés
- [ ] Module de gamification
- [ ] Chat en temps réel
- [ ] Application mobile

### Améliorations Techniques
- [ ] Migration vers PHP 8.2+
- [ ] Implémentation de tests unitaires
- [ ] Cache Redis
- [ ] Queue system pour les tâches lourdes
- [ ] Logging avancé
- [ ] Monitoring des performances

## 👥 Contribution

Pour contribuer au projet :
1. Fork le repository
2. Créer une branche feature
3. Commiter les changements
4. Pousser vers la branche
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou support :
- **Email** : support@samalsakom.sn
- **Documentation** : [Wiki du projet]
- **Issues** : [GitHub Issues]

---

**Développé avec ❤️ pour la communauté sénégalaise**

*SamalSakom - Ensemble, épargnons pour l'avenir*
