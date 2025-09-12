<?php
// Démarrer la session seulement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Connexion à la base de données
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Récupération des informations utilisateur
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur récupération utilisateur: " . $e->getMessage());
    header('Location: ../login.php');
    exit;
}

// Récupération des notifications non lues
try {
    $notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND lu = 0";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->execute([$_SESSION['user_id']]);
    $notifications_count = $notif_stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    $notifications_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - SamalSakom</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sidebar -->
    <nav class="dashboard-sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-piggy-bank"></i>
                <span class="logo-text">SamalSakom</span>
            </div>
            <button class="sidebar-toggle d-lg-none" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-nav">
            <!-- Navigation principale -->
            <div class="nav-section">
                <div class="nav-section-title">Tableau de Bord</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-home"></i>
                            <span class="nav-text">Accueil</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="mes-tontines.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mes-tontines.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-piggy-bank"></i>
                            <span class="nav-text">Mes Tontines</span>
                            <?php
                            // Compter les tontines actives de l'utilisateur
                            try {
                                $count_tontines = "SELECT COUNT(*) as count FROM participations p 
                                                 JOIN tontines t ON p.tontine_id = t.id 
                                                 WHERE p.user_id = ? AND t.statut = 'active'";
                                $stmt_count = $db->prepare($count_tontines);
                                $stmt_count->execute([$_SESSION['user_id']]);
                                $mes_tontines_count = $stmt_count->fetch()['count'];
                                if ($mes_tontines_count > 0) {
                                    echo '<span class="nav-badge">' . $mes_tontines_count . '</span>';
                                }
                            } catch (PDOException $e) {}
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="decouvrir-tontines.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'decouvrir-tontines.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-search"></i>
                            <span class="nav-text">Découvrir</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Gestion financière -->
            <div class="nav-section">
                <div class="nav-section-title">Finances</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="paiements.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'paiements.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <span class="nav-text">Paiements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="historique.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'historique.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <span class="nav-text">Historique</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="portefeuille.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'portefeuille.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-wallet"></i>
                            <span class="nav-text">Portefeuille</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Compte -->
            <div class="nav-section">
                <div class="nav-section-title">Mon Compte</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="profil.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user"></i>
                            <span class="nav-text">Profil</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-bell"></i>
                            <span class="nav-text">Notifications</span>
                            <?php if ($notifications_count > 0): ?>
                                <span class="nav-badge"><?php echo $notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="parametres.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parametres.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <span class="nav-text">Paramètres</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Déconnexion -->
            <div class="nav-section nav-section-logout">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="actions/logout.php" class="nav-link nav-link-logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <span class="nav-text">Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contenu principal -->
    <div class="dashboard-content">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="breadcrumb-nav">
                    <a href="index.php">Dashboard</a>
                    <?php if (isset($breadcrumb)): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo $breadcrumb; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Recherche rapide -->
                <div class="header-search d-none d-md-block">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher une tontine..." id="quickSearch">
                </div>
                
                <!-- Notifications -->
                <div class="header-notifications dropdown">
                    <button class="notification-btn" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifications_count > 0): ?>
                            <span class="notification-badge"><?php echo $notifications_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <div class="dropdown-header">
                            <h6>Notifications</h6>
                            <a href="notifications.php" class="view-all">Tout voir</a>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <!-- Les notifications seront chargées via AJAX -->
                        </div>
                    </div>
                </div>
                
                <!-- Profil utilisateur -->
                <div class="header-profile dropdown">
                    <button class="profile-btn" data-bs-toggle="dropdown">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($current_user['prenom'], 0, 1) . substr($current_user['nom'], 0, 1)); ?>
                        </div>
                        <div class="profile-info d-none d-md-block">
                            <div class="profile-name"><?php echo htmlspecialchars($current_user['prenom'] . ' ' . $current_user['nom']); ?></div>
                            <div class="profile-status">En ligne</div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <div class="dropdown-header">
                            <div class="profile-avatar large">
                                <?php echo strtoupper(substr($current_user['prenom'], 0, 1) . substr($current_user['nom'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($current_user['prenom'] . ' ' . $current_user['nom']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($current_user['email']); ?></small>
                            </div>
                        </div>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a class="dropdown-item" href="profil.php">
                            <i class="fas fa-user me-2"></i>Mon Profil
                        </a>
                        <a class="dropdown-item" href="parametres.php">
                            <i class="fas fa-cog me-2"></i>Paramètres
                        </a>
                        <a class="dropdown-item" href="aide.php">
                            <i class="fas fa-question-circle me-2"></i>Aide
                        </a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a class="dropdown-item text-danger" href="actions/logout.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Zone de contenu -->
        <main class="main-content">
