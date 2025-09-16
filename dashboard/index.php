<?php
$page_title = "Dashboard";
$breadcrumb = "Accueil";
include 'includes/header.php';

// Récupération des statistiques utilisateur
try {
    // Mes tontines actives
    $query_mes_tontines = "SELECT COUNT(*) as count FROM participations p 
                          JOIN tontines t ON p.tontine_id = t.id 
                          WHERE p.user_id = ? AND t.statut = 'active'";
    $stmt = $db->prepare($query_mes_tontines);
    $stmt->execute([$_SESSION['user_id']]);
    $mes_tontines_actives = $stmt->fetch()['count'];
    
    // Total épargné
    $query_total_epargne = "SELECT COALESCE(SUM(montant), 0) as total FROM cotisations 
                           WHERE user_id = ? AND statut = 'completed'";
    $stmt = $db->prepare($query_total_epargne);
    $stmt->execute([$_SESSION['user_id']]);
    $total_epargne = $stmt->fetch()['total'];
    
    // Cotisations en attente
    $query_cotisations_pending = "SELECT COUNT(*) as count FROM cotisations 
                                 WHERE user_id = ? AND statut = 'pending'";
    $stmt = $db->prepare($query_cotisations_pending);
    $stmt->execute([$_SESSION['user_id']]);
    $cotisations_pending_count = $stmt->fetch()['count'];
    
    // Compter les retards
    $query_retards = "SELECT COUNT(*) as count FROM cotisations 
                      WHERE user_id = ? AND statut = 'pending' AND date_cotisation < CURDATE()";
    $stmt = $db->prepare($query_retards);
    $stmt->execute([$_SESSION['user_id']]);
    $retards_count = $stmt->fetch()['count'];
    
    // Prochaine échéance
    $query_prochaine_echeance = "SELECT MIN(date_cotisation) as prochaine FROM cotisations 
                                WHERE user_id = ? AND statut = 'pending' AND date_cotisation >= CURDATE()";
    $stmt = $db->prepare($query_prochaine_echeance);
    $stmt->execute([$_SESSION['user_id']]);
    $prochaine_echeance = $stmt->fetch()['prochaine'];
    
    // Mes dernières tontines
    $query_dernieres_tontines = "SELECT t.*, p.date_participation, p.statut as participation_statut
                                FROM tontines t 
                                JOIN participations p ON t.id = p.tontine_id 
                                WHERE p.user_id = ? 
                                ORDER BY p.date_participation DESC 
                                LIMIT 5";
    $stmt = $db->prepare($query_dernieres_tontines);
    $stmt->execute([$_SESSION['user_id']]);
    $dernieres_tontines = $stmt->fetchAll();
    
    // Historique récent des cotisations
    $query_historique = "SELECT c.*, t.nom as tontine_nom 
                        FROM cotisations c 
                        JOIN tontines t ON c.tontine_id = t.id 
                        WHERE c.user_id = ? 
                        ORDER BY c.date_creation DESC 
                        LIMIT 5";
    $stmt = $db->prepare($query_historique);
    $stmt->execute([$_SESSION['user_id']]);
    $historique_cotisations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données.";
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Bonjour, <?php echo htmlspecialchars($current_user['prenom']); ?> ! 👋</h1>
    <p class="page-subtitle">Voici un aperçu de vos activités d'épargne sur SamalSakom</p>
</div>

<!-- Statistiques principales -->
<div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $mes_tontines_actives; ?></div>
        <div class="stat-label">Tontines Actives</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            Participations en cours
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_epargne, 0, ',', ' '); ?></div>
        <div class="stat-label">Total Épargné (FCFA)</div>
        <div class="stat-change positive">
            <i class="fas fa-chart-line"></i>
            Toutes tontines confondues
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $cotisations_pending_count; ?></div>
        <div class="stat-label">Cotisations en Attente</div>
        <div class="stat-change <?php echo $cotisations_pending_count > 0 ? 'negative' : 'positive'; ?>">
            <i class="fas fa-<?php echo $cotisations_pending_count > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
            <?php echo $cotisations_pending_count > 0 ? 'À régler rapidement' : 'Tout est à jour'; ?>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php 
            if ($prochaine_echeance) {
                $date = new DateTime($prochaine_echeance);
                $now = new DateTime();
                $diff = $now->diff($date);
                echo $diff->days;
            } else {
                echo '-';
            }
            ?>
        </div>
        <div class="stat-label">Jours jusqu'à la prochaine échéance</div>
        <div class="stat-change <?php echo $prochaine_echeance ? 'negative' : 'positive'; ?>">
            <i class="fas fa-<?php echo $prochaine_echeance ? 'calendar-check' : 'smile'; ?>"></i>
            <?php echo $prochaine_echeance ? 'Préparez votre paiement' : 'Aucune échéance'; ?>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="200">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Actions Rapides</h3>
            </div>
            <div class="card-body-modern">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="decouvrir-tontines.php" class="btn btn-primary-modern w-100">
                            <i class="fas fa-search"></i>
                            Découvrir des Tontines
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="paiements.php" class="btn btn-success-modern w-100">
                            <i class="fas fa-credit-card"></i>
                            Effectuer un Paiement
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="mes-tontines.php" class="btn btn-warning-modern w-100">
                            <i class="fas fa-piggy-bank"></i>
                            Mes Tontines
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="profil.php" class="btn btn-outline-modern w-100">
                            <i class="fas fa-user"></i>
                            Mon Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cotisations en attente -->
<div class="row g-4 mt-4" data-aos="fade-up" data-aos-delay="350">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Cotisations à Payer
                </h3>
                <div class="d-flex gap-2">
                    <?php if ($retards_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $retards_count; ?> en retard</span>
                    <?php endif; ?>
                    <span class="badge bg-warning"><?php echo $cotisations_pending_count - $retards_count; ?> en attente</span>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if ($cotisations_pending_count > 0): ?>
                    <?php
                    // Récupérer les cotisations en attente avec calcul des retards
                    $query = "SELECT c.*, t.nom as tontine_nom,
                              CASE 
                                WHEN c.date_cotisation < CURDATE() THEN DATEDIFF(CURDATE(), c.date_cotisation)
                                ELSE 0
                              END as jours_retard,
                              CASE 
                                WHEN c.date_cotisation < CURDATE() THEN 'en_retard'
                                ELSE 'en_attente'
                              END as statut_echeance
                              FROM cotisations c 
                              JOIN tontines t ON c.tontine_id = t.id 
                              WHERE c.user_id = ? AND c.statut = 'pending'
                              ORDER BY c.date_cotisation ASC 
                              LIMIT 5";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_SESSION['user_id']]);
                    $cotisations_list = $stmt->fetchAll();
                    
                    // Compter les retards
                    $retards_count = count(array_filter($cotisations_list, function($c) {
                        return $c['statut_echeance'] === 'en_retard';
                    }));
                    ?>
                    <div class="row g-3">
                        <?php foreach ($cotisations_list as $cotisation): ?>
                        <?php 
                        $date_echeance = new DateTime($cotisation['date_cotisation']);
                        $now = new DateTime();
                        $diff = $now->diff($date_echeance);
                        $is_overdue = $date_echeance < $now;
                        ?>
                        <div class="col-md-4">
                            <div class="payment-card <?php echo $is_overdue ? 'overdue' : 'pending'; ?>">
                                <div class="payment-header">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($cotisation['tontine_nom']); ?></h6>
                                    <small class="text-muted"><?php echo ucfirst($cotisation['frequence']); ?></small>
                                </div>
                                <div class="payment-amount">
                                    <?php echo number_format($cotisation['montant'], 0, ',', ' '); ?> FCFA
                                </div>
                                <div class="payment-due">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo $date_echeance->format('d/m/Y'); ?>
                                    <?php if ($is_overdue): ?>
                                        <span class="text-danger ms-2">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Retard: <?php echo $diff->days; ?>j
                                        </span>
                                    <?php else: ?>
                                        <span class="text-warning ms-2">
                                            Dans <?php echo $diff->days; ?> jour(s)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="payment-actions mt-2">
                                    <a href="paiements.php" class="btn btn-success-modern btn-sm w-100">
                                        <i class="fas fa-credit-card me-1"></i>
                                        Payer maintenant
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($cotisations_pending_count > 3): ?>
                    <div class="text-center mt-3">
                        <a href="paiements.php" class="btn btn-outline-primary">
                            Voir les <?php echo $cotisations_pending_count - 3; ?> autres cotisations
                        </a>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Toutes vos cotisations sont à jour !</h5>
                        <p class="text-muted">Aucun paiement en attente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Activité récente -->
<div class="row g-4 mt-4" data-aos="fade-up" data-aos-delay="400">
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Mes Tontines Récentes</h3>
                <a href="tontines.php" class="btn btn-outline-modern btn-sm">Voir toutes</a>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($recent_tontines)): ?>
                    <div class="table-modern">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tontine</th>
                                    <th>Montant</th>
                                    <th>Fréquence</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tontines as $tontine): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($tontine['nom']); ?></div>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($tontine['date_creation'])); ?></small>
                                    </td>
                                    <td><?php echo number_format($tontine['montant_cotisation'], 0, ',', ' '); ?> FCFA</td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($tontine['frequence']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $tontine['statut'] == 'active' ? 'active' : ($tontine['statut'] == 'terminee' ? 'completed' : 'pending'); ?>">
                                            <?php echo ucfirst($tontine['statut']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="tontine-details.php?id=<?php echo $tontine['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($tontine['statut'] == 'active'): ?>
                                            <a href="paiements.php?tontine=<?php echo $tontine['id']; ?>" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>Aucune tontine</h5>
                        <p class="text-muted">Rejoignez une tontine pour commencer</p>
                        <a href="decouvrir-tontines.php" class="btn btn-primary-modern btn-sm">Découvrir</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Activité Récente -->
    <div class="col-lg-4" data-aos="fade-up" data-aos-delay="400">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Activité Récente</h3>
                <a href="historique.php" class="btn btn-outline-modern btn-sm">
                    <i class="fas fa-history"></i> Historique
                </a>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($historique_cotisations)): ?>
                    <div class="activity-feed">
                        <?php foreach ($historique_cotisations as $cotisation): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-<?php echo $cotisation['statut'] == 'completed' ? 'success' : ($cotisation['statut'] == 'pending' ? 'warning' : 'danger'); ?>">
                                <i class="fas fa-<?php echo $cotisation['statut'] == 'completed' ? 'check' : ($cotisation['statut'] == 'pending' ? 'clock' : 'times'); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <?php echo $cotisation['statut'] == 'completed' ? 'Cotisation payée' : ($cotisation['statut'] == 'pending' ? 'Cotisation en attente' : 'Cotisation échouée'); ?>
                                </div>
                                <div class="activity-meta">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($cotisation['tontine_nom']); ?></span>
                                    <span class="text-success">
                                        <?php echo number_format($cotisation['montant'], 0, ',', ' '); ?> FCFA
                                    </span>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('d/m/Y H:i', strtotime($cotisation['date_creation'])); ?>
                                </div>
                            </div>
                            <div class="activity-actions">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="voirDetailsSidebar(<?php echo $cotisation['id']; ?>)"
                                        title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-2x text-muted mb-3"></i>
                        <p class="text-muted">Aucune activité récente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Graphique des épargnes (optionnel) -->
<div class="row g-4 mt-4" data-aos="fade-up" data-aos-delay="500">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Évolution de mes Épargnes</h3>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary active" data-period="7">7 jours</button>
                    <button class="btn btn-outline-secondary" data-period="30">30 jours</button>
                    <button class="btn btn-outline-secondary" data-period="90">3 mois</button>
                </div>
            </div>
            <div class="card-body-modern">
                <canvas id="epargnesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
.payment-card {
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 1rem;
    background: white;
    transition: var(--transition-fast);
    height: 100%;
}

.payment-card.pending {
    border-color: #ffc107;
    background: rgba(255, 193, 7, 0.05);
}

.payment-card.overdue {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.payment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.payment-header h6 {
    color: var(--dark-color);
    font-weight: 600;
}

.payment-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0.5rem 0;
}

.payment-due {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.payment-actions .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.activity-item:hover {
    background-color: #f8f9fa;
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
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-actions {
    margin-left: 1rem;
    flex-shrink: 0;
}

.activity-text {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.875rem;
    color: #6c757d;
}


.activity-content {
    flex: 1;
}

.activity-text {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--gray-600);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des épargnes
    const ctx = document.getElementById('epargnesChart');
    if (ctx) {
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [{
                    label: 'Épargnes (FCFA)',
                    data: [<?php echo $total_epargne * 0.25; ?>, <?php echo $total_epargne * 0.5; ?>, <?php echo $total_epargne * 0.75; ?>, <?php echo $total_epargne; ?>],
                    borderColor: '#2E8B57',
                    backgroundColor: 'rgba(46, 139, 87, 0.1)',
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
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                            }
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
        
        // Gestion des boutons de période
        document.querySelectorAll('[data-period]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Ici vous pouvez recharger les données du graphique
            });
        });
    }
});

// Fonction pour voir les détails depuis le sidebar historique
function voirDetailsSidebar(transactionId) {
    fetch(`actions/get_transaction_details.php?id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Créer le modal s'il n'existe pas
                let modal = document.getElementById('detailsModalSidebar');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'detailsModalSidebar';
                    modal.setAttribute('tabindex', '-1');
                    modal.innerHTML = `
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-receipt me-2"></i>
                                        Détails de la Transaction
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="detailsModalBodySidebar">
                                    <!-- Contenu chargé dynamiquement -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
                
                document.getElementById('detailsModalBodySidebar').innerHTML = data.html;
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                showToast(data.message || 'Erreur lors du chargement des détails', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du chargement des détails', 'error');
        });
}

// Fonction pour afficher les notifications toast
function showToast(message, type = 'info') {
    // Créer le conteneur de toasts s'il n'existe pas
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Créer le toast
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'primary')} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : (type === 'success' ? 'check-circle' : 'info-circle')} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Initialiser et afficher le toast
    const bootstrapToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bootstrapToast.show();
    
    // Supprimer le toast du DOM après qu'il soit caché
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
