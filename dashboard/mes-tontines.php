<?php
$page_title = "Mes Tontines";
$breadcrumb = "Mes Tontines";
include 'includes/header.php';

// Récupération des tontines de l'utilisateur
try {
    $query = "SELECT t.*, p.date_participation, p.statut as participation_statut,
                     COUNT(DISTINCT part.id) as participants_actuels,
                     u.prenom as createur_prenom, u.nom as createur_nom,
                     COALESCE(SUM(CASE WHEN c.statut = 'completed' THEN c.montant ELSE 0 END), 0) as total_cotise
              FROM tontines t 
              JOIN participations p ON t.id = p.tontine_id 
              LEFT JOIN participations part ON t.id = part.tontine_id AND part.statut != 'retire'
              LEFT JOIN users u ON t.createur_id = u.id
              LEFT JOIN cotisations c ON t.id = c.tontine_id AND c.user_id = ?
              WHERE p.user_id = ? AND p.statut != 'retire'
              GROUP BY t.id, p.id
              ORDER BY p.date_participation DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $mes_tontines = $stmt->fetchAll();
    
    // Récupérer les cotisations pour chaque tontine
    foreach ($mes_tontines as &$tontine) {
        $cotisations_query = "SELECT * FROM cotisations 
                             WHERE tontine_id = ? AND user_id = ? 
                             ORDER BY date_cotisation ASC";
        $stmt = $db->prepare($cotisations_query);
        $stmt->execute([$tontine['id'], $_SESSION['user_id']]);
        $tontine['cotisations'] = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des tontines.";
    $mes_tontines = [];
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Mes Tontines</h1>
    <p class="page-subtitle">Gérez toutes vos participations aux tontines</p>
</div>

<!-- Statistiques rapides -->
<div class="stats-grid mb-4" data-aos="fade-up">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo count($mes_tontines); ?></div>
        <div class="stat-label">Tontines Actives</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php 
            $total_epargne = array_sum(array_column($mes_tontines, 'total_cotise'));
            echo number_format($total_epargne, 0, ',', ' ');
            ?>
        </div>
        <div class="stat-label">Total Épargné (FCFA)</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php 
            $tontines_actives = array_filter($mes_tontines, function($t) { return $t['statut'] == 'active'; });
            echo count($tontines_actives);
            ?>
        </div>
        <div class="stat-label">En Cours</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-trophy"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php 
            $tontines_terminees = array_filter($mes_tontines, function($t) { return $t['statut'] == 'terminee'; });
            echo count($tontines_terminees);
            ?>
        </div>
        <div class="stat-label">Terminées</div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="100">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-body-modern">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="decouvrir-tontines.php" class="btn btn-primary-modern w-100">
                            <i class="fas fa-search"></i> Découvrir
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="creer-tontine.php" class="btn btn-success-modern w-100">
                            <i class="fas fa-plus"></i> Créer une Tontine
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="paiements.php" class="btn btn-warning-modern w-100">
                            <i class="fas fa-credit-card"></i> Paiements
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="historique.php" class="btn btn-outline-modern w-100">
                            <i class="fas fa-history"></i> Historique
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des tontines -->
<div class="row g-4" data-aos="fade-up" data-aos-delay="200">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Mes Participations</h3>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary active" data-filter="all">Toutes</button>
                    <button class="btn btn-outline-secondary" data-filter="active">Actives</button>
                    <button class="btn btn-outline-secondary" data-filter="terminee">Terminées</button>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($mes_tontines)): ?>
                    <div class="tontines-list">
                        <?php foreach ($mes_tontines as $tontine): ?>
                        <div class="tontine-item" data-status="<?php echo $tontine['statut']; ?>">
                            <div class="tontine-item-header">
                                <div class="tontine-info">
                                    <h5 class="tontine-name"><?php echo htmlspecialchars($tontine['nom']); ?></h5>
                                    <div class="tontine-meta">
                                        <span class="meta-badge">
                                            <i class="fas fa-coins"></i>
                                            <?php echo number_format($tontine['montant_cotisation'], 0, ',', ' '); ?> FCFA
                                        </span>
                                        <span class="meta-badge">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo ucfirst($tontine['frequence']); ?>
                                        </span>
                                        <span class="meta-badge">
                                            <i class="fas fa-users"></i>
                                            <?php echo $tontine['participants_actuels']; ?>/<?php echo $tontine['nombre_participants']; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php 
                                $cotisations_pending = array_filter($tontine['cotisations'] ?? [], function($c) {
                                    return $c['statut'] == 'pending';
                                });
                                $cotisations_retard = array_filter($cotisations_pending, function($c) {
                                    return strtotime($c['date_cotisation']) < time();
                                });
                                if (!empty($cotisations_retard)): 
                                ?>
                                <div class="alert alert-warning alert-dismissible fade show mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-3"></i>
                                        <div class="flex-grow-1">
                                            <strong>Cotisations en retard !</strong><br>
                                            <small>Vous avez <?= count($cotisations_retard) ?> cotisation(s) en retard pour cette tontine.</small>
                                            <div class="mt-2">
                                                <a href="paiements.php?tontine=<?= $tontine['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-credit-card me-1"></i>Payer maintenant
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php endif; ?>
                                <div class="tontine-status">
                                    <span class="status-badge status-<?php echo $tontine['statut'] == 'active' ? 'active' : ($tontine['statut'] == 'terminee' ? 'completed' : 'pending'); ?>">
                                        <?php echo ucfirst($tontine['statut']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="tontine-item-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="tontine-progress">
                                            <div class="progress-header">
                                                <span class="progress-label">Progression</span>
                                                <span class="progress-value">
                                                    <?php echo round(($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100); ?>%
                                                </span>
                                            </div>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo ($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="tontine-stats">
                                            <div class="stat-item">
                                                <span class="stat-label">Mes cotisations</span>
                                                <span class="stat-value text-success">
                                                    <?php echo number_format($tontine['total_cotise'], 0, ',', ' '); ?> FCFA
                                                </span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-label">Organisateur</span>
                                                <span class="stat-value">
                                                    <?php echo htmlspecialchars($tontine['createur_prenom'] . ' ' . $tontine['createur_nom']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="tontine-description">
                                            <p class="text-muted">
                                                <?php echo htmlspecialchars(substr($tontine['description'] ?? 'Aucune description', 0, 150)); ?>
                                                <?php echo strlen($tontine['description'] ?? '') > 150 ? '...' : ''; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="tontine-dates">
                                            <div class="date-item">
                                                <i class="fas fa-calendar-plus text-primary"></i>
                                                <span>Rejoint le <?php echo date('d/m/Y', strtotime($tontine['date_participation'])); ?></span>
                                            </div>
                                            <?php if ($tontine['date_debut']): ?>
                                            <div class="date-item">
                                                <i class="fas fa-play text-success"></i>
                                                <span>Début le <?php echo date('d/m/Y', strtotime($tontine['date_debut'])); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tontine-item-footer">
                                <div class="btn-group">
                                    <a href="tontine-details.php?id=<?php echo $tontine['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> Détails
                                    </a>
                                    <?php if ($tontine['statut'] == 'active'): ?>
                                    <a href="paiements.php?tontine=<?php echo $tontine['id']; ?>" 
                                       class="btn btn-success-modern btn-sm">
                                        <i class="fas fa-credit-card"></i> Payer
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="tontine-details.php?id=<?php echo $tontine['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>Voir détails
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="partager(<?php echo $tontine['id']; ?>)">
                                            <i class="fas fa-share me-2"></i>Partager
                                        </a></li>
                                        <?php if ($tontine['statut'] == 'active' && $tontine['participation_statut'] == 'confirme'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="quitterTontine(<?php echo $tontine['id']; ?>)">
                                            <i class="fas fa-sign-out-alt me-2"></i>Quitter
                                        </a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-piggy-bank fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">Aucune tontine pour le moment</h4>
                        <p class="text-muted mb-4">Commencez votre parcours d'épargne en rejoignant une tontine</p>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="decouvrir-tontines.php" class="btn btn-primary-modern">
                                <i class="fas fa-search"></i> Découvrir des Tontines
                            </a>
                            <a href="creer-tontine.php" class="btn btn-success-modern">
                                <i class="fas fa-plus"></i> Créer une Tontine
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.tontines-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.tontine-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    transition: var(--transition);
    overflow: hidden;
}

.tontine-item:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow);
}

.tontine-item-header {
    padding: 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.tontine-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.tontine-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.meta-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: white;
    border-radius: 15px;
    font-size: 0.85rem;
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
}

.tontine-item-body {
    padding: 1.5rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.progress-label {
    font-size: 0.9rem;
    color: var(--gray-600);
}

.progress-value {
    font-weight: 600;
    color: var(--success-color);
}

.tontine-stats {
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--gray-600);
}

.stat-value {
    font-weight: 600;
}

.tontine-description {
    margin-bottom: 1rem;
}

.tontine-dates {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.tontine-item-footer {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .tontine-item-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .tontine-meta {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
// Filtrage des tontines
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    const tontineItems = document.querySelectorAll('.tontine-item');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Mise à jour des boutons
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrage des éléments
            tontineItems.forEach(item => {
                const status = item.dataset.status;
                if (filter === 'all' || status === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

function partager(tontineId) {
    const url = `${window.location.origin}/dashboard/tontine-details.php?id=${tontineId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Rejoignez cette tontine sur SamalSakom',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Lien copié dans le presse-papiers !', 'success');
        });
    }
}

function quitterTontine(tontineId) {
    if (!confirm('Êtes-vous sûr de vouloir quitter cette tontine ? Cette action est irréversible.')) {
        return;
    }
    
    fetch('actions/quitter_tontine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            tontine_id: tontineId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Vous avez quitté la tontine', 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erreur lors de la désinscription', 'error');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
