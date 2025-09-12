<?php
$page_title = "Tableau de Bord";
$breadcrumb = "Vue d'ensemble";
include 'includes/header.php';

// Récupération des statistiques
try {
    // Statistiques utilisateurs
    $query = "SELECT COUNT(*) as total FROM users WHERE statut = 'actif'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_users = $stmt->fetch()['total'];
    
    $query = "SELECT COUNT(*) as count FROM users WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $new_users_month = $stmt->fetch()['count'];
    
    // Statistiques tontines
    $query = "SELECT COUNT(*) as total FROM tontines WHERE statut = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_tontines = $stmt->fetch()['total'];
    
    // Statistiques financières (simulation)
    $total_epargne = 45750000; // 45.75M FCFA
    $transactions_mois = 1250;
    
    // Messages non lus
    $query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $messages_non_lus = $stmt->fetch()['count'];
    
    // Derniers utilisateurs
    $query = "SELECT * FROM users ORDER BY date_creation DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $derniers_users = $stmt->fetchAll();
    
    // Derniers messages
    $query = "SELECT * FROM contacts ORDER BY date_creation DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $derniers_messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données.";
}
?>

<div class="page-header">
    <h1 class="page-title">Tableau de Bord</h1>
    <p class="page-subtitle">Vue d'ensemble de votre plateforme SamalSakom</p>
</div>

<!-- Statistiques principales -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_users); ?></div>
        <div class="stat-label">Utilisateurs Actifs</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +<?php echo $new_users_month; ?> ce mois
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_tontines; ?></div>
        <div class="stat-label">Tontines Actives</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +15% ce mois
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_epargne / 1000000, 1); ?>M</div>
        <div class="stat-label">Épargne Totale (FCFA)</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +8.5% ce mois
        </div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div class="stat-icon danger">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($transactions_mois); ?></div>
        <div class="stat-label">Transactions ce Mois</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +12% vs mois dernier
        </div>
    </div>
</div>

<!-- Graphiques et tableaux -->
<div class="row g-4">
    <!-- Graphique des inscriptions -->
    <div class="col-lg-8">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Évolution des Inscriptions</h3>
                <div class="table-actions">
                    <select class="form-select form-select-sm" style="width: auto;">
                        <option>7 derniers jours</option>
                        <option>30 derniers jours</option>
                        <option>3 derniers mois</option>
                    </select>
                </div>
            </div>
            <div class="p-4">
                <canvas id="inscriptionsChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Activité récente -->
    <div class="col-lg-4">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Activité Récente</h3>
                <a href="notifications.php" class="btn-admin btn-outline btn-sm">
                    <i class="fas fa-eye"></i> Tout voir
                </a>
            </div>
            <div class="activity-feed p-3">
                <div class="activity-item">
                    <div class="activity-icon bg-success">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Nouvel utilisateur inscrit</div>
                        <div class="activity-time">Il y a 5 minutes</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon bg-primary">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Nouvelle tontine créée</div>
                        <div class="activity-time">Il y a 15 minutes</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon bg-warning">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Nouveau message reçu</div>
                        <div class="activity-time">Il y a 1 heure</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon bg-info">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Transaction effectuée</div>
                        <div class="activity-time">Il y a 2 heures</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tableaux de données -->
<div class="row g-4 mt-4">
    <!-- Derniers utilisateurs -->
    <div class="col-lg-6">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Derniers Utilisateurs</h3>
                <a href="users.php" class="btn-admin btn-primary btn-sm">
                    <i class="fas fa-users"></i> Gérer
                </a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniers_users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="profile-avatar me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['telephone']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $user['statut'] == 'actif' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst($user['statut']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Derniers messages -->
    <div class="col-lg-6">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Messages Récents</h3>
                <a href="messages.php" class="btn-admin btn-warning btn-sm">
                    <i class="fas fa-envelope"></i> Gérer
                    <?php if ($messages_non_lus > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $messages_non_lus; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Expéditeur</th>
                            <th>Sujet</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniers_messages as $message): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($message['nom']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($message['sujet']); ?>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($message['date_creation'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $message['statut'] == 'nouveau' ? 'pending' : ($message['statut'] == 'lu' ? 'active' : 'inactive'); ?>">
                                    <?php echo ucfirst($message['statut']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-weight: 500;
    color: var(--admin-primary);
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.8rem;
    color: #666;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
// Graphique des inscriptions
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [{
                label: 'Nouvelles inscriptions',
                data: [12, 19, 8, 15, 25, 22, 18],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
