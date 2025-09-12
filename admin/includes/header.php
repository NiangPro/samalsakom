<?php
// Vérification de la session admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Récupération des infos admin
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin SamalSakom</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">
                <i class="fas fa-coins"></i>
                <span class="logo-text">SamalSakom</span>
            </a>
        </div>
        
        <div class="sidebar-nav">
            <!-- Section Tableau de Bord -->
            <div class="nav-section">
                <div class="nav-section-title">Tableau de Bord</div>
                <div class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <span class="nav-text">Vue d'ensemble</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="analytics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <span class="nav-text">Analytiques</span>
                    </a>
                </div>
            </div>
            
            <!-- Section Gestion -->
            <div class="nav-section">
                <div class="nav-section-title">Gestion</div>
                <div class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <span class="nav-text">Utilisateurs</span>
                        <?php
                        // Compter les nouveaux utilisateurs (dernières 24h)
                        $query = "SELECT COUNT(*) as count FROM users WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $new_users = $stmt->fetch()['count'];
                        if ($new_users > 0): ?>
                            <span class="nav-badge"><?php echo $new_users; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="tontines.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tontines.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-piggy-bank"></i>
                        <span class="nav-text">Tontines</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="transactions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        <span class="nav-text">Transactions</span>
                    </a>
                </div>
            </div>
            
            <!-- Section Communication -->
            <div class="nav-section">
                <div class="nav-section-title">Communication</div>
                <div class="nav-item">
                    <a href="messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-envelope"></i>
                        <span class="nav-text">Messages</span>
                        <?php
                        // Compter les messages non lus
                        $query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $unread_messages = $stmt->fetch()['count'];
                        if ($unread_messages > 0): ?>
                            <span class="nav-badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-bell"></i>
                        <span class="nav-text">Notifications</span>
                    </a>
                </div>
            </div>
            
            <!-- Section Contenu -->
            <div class="nav-section">
                <div class="nav-section-title">Contenu</div>
                <div class="nav-item">
                    <a href="formules.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'formules.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tags"></i>
                        <span class="nav-text">Formules Services</span>
                    </a>
                </div>
            </div>
            
            <!-- Section Système -->
            <div class="nav-section">
                <div class="nav-section-title">Système</div>
                <div class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <span class="nav-text">Paramètres</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <span class="nav-text">Administrateurs</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <span class="nav-text">Logs</span>
                    </a>
                </div>
            </div>
            
            <!-- Section Déconnexion -->
            <div class="nav-section nav-section-logout">
                <div class="nav-item">
                    <a href="logout.php" class="nav-link nav-link-logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <span class="nav-text">Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Contenu principal -->
    <div class="admin-content">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <nav class="breadcrumb-nav">
                    <a href="index.php">Dashboard</a>
                    <?php if (isset($breadcrumb)): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo $breadcrumb; ?></span>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="header-right">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher..." id="globalSearch">
                </div>
                
                <div class="header-notifications" id="notificationDropdown">
                    <i class="fas fa-bell notification-icon"></i>
                    <?php if ($unread_messages > 0): ?>
                        <span class="notification-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-link admin-profile dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; text-decoration: none;">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></div>
                            <div class="profile-role"><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <div class="profile-avatar me-2">
                                        <?php echo strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Mon Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        
        <!-- Zone de contenu -->
        <main class="main-content">

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-info-circle text-primary me-2"></i>
            <strong class="me-auto">SamalSakom Admin</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <!-- Message sera injecté ici -->
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin JS -->
<script src="assets/js/admin.js"></script>

<script>
// Initialisation Bootstrap Dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Force l'initialisation des dropdowns
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
    
    console.log('Dropdowns initialisés:', dropdownList.length);
});
</script>
