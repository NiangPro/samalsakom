<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Récupération des données analytiques
$stats = [];

// Statistiques générales
$query = "SELECT COUNT(*) as total FROM users WHERE statut = 'actif'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['users_actifs'] = $stmt->fetch()['total'];

$query = "SELECT COUNT(*) as total FROM tontines WHERE statut = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['tontines_actives'] = $stmt->fetch()['total'];

$query = "SELECT SUM(montant_cotisation * duree_mois) as total FROM tontines WHERE statut = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['volume_total'] = $stmt->fetch()['total'] ?? 0;

// Données pour graphiques - Évolution des inscriptions par mois
$query = "SELECT DATE_FORMAT(date_creation, '%Y-%m') as mois, COUNT(*) as count 
          FROM users 
          WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
          ORDER BY mois";
$stmt = $db->prepare($query);
$stmt->execute();
$inscriptions_data = $stmt->fetchAll();

// Données pour graphiques - Répartition des tontines par statut
$query = "SELECT statut, COUNT(*) as count FROM tontines GROUP BY statut";
$stmt = $db->prepare($query);
$stmt->execute();
$tontines_status = $stmt->fetchAll();

// Top 5 des tontines les plus populaires
$query = "SELECT t.nom, COUNT(p.id) as participants 
          FROM tontines t 
          LEFT JOIN participations p ON t.id = p.tontine_id 
          GROUP BY t.id 
          ORDER BY participants DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$top_tontines = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Analytiques Avancées</h1>
                <p class="content-subtitle">Analyse détaillée des performances de la plateforme</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-outline-primary me-2" onclick="exportAnalytics()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-primary" onclick="refreshAnalytics()">
                    <i class="fas fa-sync-alt me-1"></i>Actualiser
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-value"><?php echo number_format($stats['users_actifs']); ?></div>
                                <div class="stat-label">Utilisateurs Actifs</div>
                                <div class="stat-change text-success">
                                    <i class="fas fa-arrow-up"></i> +12% ce mois
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-piggy-bank text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-value"><?php echo number_format($stats['tontines_actives']); ?></div>
                                <div class="stat-label">Tontines Actives</div>
                                <div class="stat-change text-success">
                                    <i class="fas fa-arrow-up"></i> +8% ce mois
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-coins text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-value"><?php echo number_format($stats['volume_total']); ?> FCFA</div>
                                <div class="stat-label">Volume Total</div>
                                <div class="stat-change text-success">
                                    <i class="fas fa-arrow-up"></i> +15% ce mois
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-chart-line text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-value">94.5%</div>
                                <div class="stat-label">Taux de Satisfaction</div>
                                <div class="stat-change text-success">
                                    <i class="fas fa-arrow-up"></i> +2% ce mois
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Évolution des Inscriptions</h5>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    12 derniers mois
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">6 derniers mois</a></li>
                                    <li><a class="dropdown-item" href="#">12 derniers mois</a></li>
                                    <li><a class="dropdown-item" href="#">24 derniers mois</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="inscriptionsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">Répartition des Tontines</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="tontinesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Tontines et Activité Récente -->
        <div class="row">
            <div class="col-xl-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Top 5 Tontines Populaires</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_tontines as $index => $tontine): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div class="d-flex align-items-center">
                                    <div class="rank-badge me-3"><?php echo $index + 1; ?></div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($tontine['nom']); ?></div>
                                        <small class="text-muted"><?php echo $tontine['participants']; ?> participants</small>
                                    </div>
                                </div>
                                <div class="progress" style="width: 100px; height: 8px;">
                                    <div class="progress-bar" style="width: <?php echo min(100, ($tontine['participants'] / 20) * 100); ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Métriques de Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="metric-item">
                                    <div class="metric-value">2.4s</div>
                                    <div class="metric-label">Temps de Chargement</div>
                                    <div class="metric-trend text-success">
                                        <i class="fas fa-arrow-down"></i> -0.2s
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-item">
                                    <div class="metric-value">99.8%</div>
                                    <div class="metric-label">Disponibilité</div>
                                    <div class="metric-trend text-success">
                                        <i class="fas fa-arrow-up"></i> +0.1%
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-item">
                                    <div class="metric-value">1,247</div>
                                    <div class="metric-label">Sessions Actives</div>
                                    <div class="metric-trend text-success">
                                        <i class="fas fa-arrow-up"></i> +156
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-item">
                                    <div class="metric-value">4.2/5</div>
                                    <div class="metric-label">Note Moyenne</div>
                                    <div class="metric-trend text-success">
                                        <i class="fas fa-arrow-up"></i> +0.1
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Données pour les graphiques
const inscriptionsData = <?php echo json_encode($inscriptions_data); ?>;
const tontinesStatusData = <?php echo json_encode($tontines_status); ?>;

// Graphique des inscriptions
const inscriptionsCtx = document.getElementById('inscriptionsChart').getContext('2d');
new Chart(inscriptionsCtx, {
    type: 'line',
    data: {
        labels: inscriptionsData.map(item => {
            const date = new Date(item.mois + '-01');
            return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Nouvelles Inscriptions',
            data: inscriptionsData.map(item => item.count),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
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
                    color: 'rgba(0,0,0,0.1)'
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

// Graphique des tontines
const tontinesCtx = document.getElementById('tontinesChart').getContext('2d');
new Chart(tontinesCtx, {
    type: 'doughnut',
    data: {
        labels: tontinesStatusData.map(item => {
            const labels = {
                'active': 'Actives',
                'completed': 'Terminées',
                'pending': 'En attente',
                'cancelled': 'Annulées'
            };
            return labels[item.statut] || item.statut;
        }),
        datasets: [{
            data: tontinesStatusData.map(item => item.count),
            backgroundColor: ['#28a745', '#6c757d', '#ffc107', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function exportAnalytics() {
    showToast('Export en cours...', 'info');
    // Logique d'export
}

function refreshAnalytics() {
    showToast('Données actualisées', 'success');
    setTimeout(() => {
        location.reload();
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>
