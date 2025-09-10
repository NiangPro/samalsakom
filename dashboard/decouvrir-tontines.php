<?php
$page_title = "Découvrir des Tontines";
$breadcrumb = "Découvrir";
include 'includes/header.php';

// Paramètres de recherche et filtres
$search = $_GET['search'] ?? '';
$statut_filter = $_GET['statut'] ?? 'active';
$montant_min = $_GET['montant_min'] ?? '';
$montant_max = $_GET['montant_max'] ?? '';
$frequence_filter = $_GET['frequence'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    // Construction de la requête avec filtres
    $where_conditions = ["t.statut = 'active'"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(t.nom LIKE ? OR t.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($montant_min)) {
        $where_conditions[] = "t.montant_cotisation >= ?";
        $params[] = (int)$montant_min;
    }
    
    if (!empty($montant_max)) {
        $where_conditions[] = "t.montant_cotisation <= ?";
        $params[] = (int)$montant_max;
    }
    
    if (!empty($frequence_filter)) {
        $where_conditions[] = "t.frequence = ?";
        $params[] = $frequence_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Compter le total
    $count_query = "SELECT COUNT(*) as total FROM tontines t WHERE $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_tontines = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_tontines / $limit);
    
    // Récupérer les tontines avec informations du créateur et nombre de participants
    $query = "SELECT t.*, 
                     u.prenom as createur_prenom, u.nom as createur_nom,
                     COUNT(p.id) as participants_actuels,
                     (t.nombre_participants - COUNT(p.id)) as places_restantes,
                     CASE WHEN up.user_id IS NOT NULL THEN 1 ELSE 0 END as deja_participant
              FROM tontines t 
              LEFT JOIN users u ON t.createur_id = u.id
              LEFT JOIN participations p ON t.id = p.tontine_id AND p.statut != 'retire'
              LEFT JOIN participations up ON t.id = up.tontine_id AND up.user_id = ?
              WHERE $where_clause
              GROUP BY t.id
              ORDER BY t.date_creation DESC 
              LIMIT ? OFFSET ?";
    
    $final_params = array_merge([$_SESSION['user_id']], $params, [$limit, $offset]);
    $stmt = $db->prepare($query);
    $stmt->execute($final_params);
    $tontines = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des tontines.";
    $tontines = [];
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Découvrir des Tontines</h1>
    <p class="page-subtitle">Trouvez la tontine parfaite pour vos objectifs d'épargne</p>
</div>

<!-- Filtres de recherche -->
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-body-modern">
                <form method="GET" class="form-modern" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Rechercher</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Nom ou description...">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Montant Min</label>
                            <input type="number" class="form-control" name="montant_min" 
                                   value="<?php echo htmlspecialchars($montant_min); ?>"
                                   placeholder="Ex: 5000">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Montant Max</label>
                            <input type="number" class="form-control" name="montant_max" 
                                   value="<?php echo htmlspecialchars($montant_max); ?>"
                                   placeholder="Ex: 50000">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Fréquence</label>
                            <select class="form-control" name="frequence">
                                <option value="">Toutes</option>
                                <option value="hebdomadaire" <?php echo $frequence_filter == 'hebdomadaire' ? 'selected' : ''; ?>>Hebdomadaire</option>
                                <option value="mensuelle" <?php echo $frequence_filter == 'mensuelle' ? 'selected' : ''; ?>>Mensuelle</option>
                                <option value="trimestrielle" <?php echo $frequence_filter == 'trimestrielle' ? 'selected' : ''; ?>>Trimestrielle</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary-modern w-100">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Résultats -->
<div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="100">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php echo $total_tontines; ?> tontine<?php echo $total_tontines > 1 ? 's' : ''; ?> trouvée<?php echo $total_tontines > 1 ? 's' : ''; ?>
            </h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary active" data-view="grid">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn btn-outline-secondary" data-view="list">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Grille des tontines -->
<div class="tontines-grid" id="tontinesGrid" data-aos="fade-up" data-aos-delay="200">
    <?php if (!empty($tontines)): ?>
        <?php foreach ($tontines as $tontine): ?>
        <div class="tontine-card">
            <div class="tontine-header">
                <div class="tontine-status">
                    <span class="status-badge status-active">Active</span>
                    <?php if ($tontine['deja_participant']): ?>
                        <span class="status-badge status-completed">Participant</span>
                    <?php endif; ?>
                </div>
                <div class="tontine-actions dropdown">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="voirDetails(<?php echo $tontine['id']; ?>)">
                            <i class="fas fa-eye me-2"></i>Voir détails
                        </a></li>
                        <?php if (!$tontine['deja_participant'] && $tontine['places_restantes'] > 0): ?>
                        <li><a class="dropdown-item" href="#" onclick="rejoindre(<?php echo $tontine['id']; ?>)">
                            <i class="fas fa-plus me-2"></i>Rejoindre
                        </a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="#" onclick="partager(<?php echo $tontine['id']; ?>)">
                            <i class="fas fa-share me-2"></i>Partager
                        </a></li>
                    </ul>
                </div>
            </div>
            
            <div class="tontine-body">
                <h5 class="tontine-title"><?php echo htmlspecialchars($tontine['nom']); ?></h5>
                <p class="tontine-description">
                    <?php echo htmlspecialchars(substr($tontine['description'] ?? 'Aucune description', 0, 100)); ?>
                    <?php echo strlen($tontine['description'] ?? '') > 100 ? '...' : ''; ?>
                </p>
                
                <div class="tontine-meta">
                    <div class="meta-item">
                        <i class="fas fa-coins text-success"></i>
                        <span class="fw-bold"><?php echo number_format($tontine['montant_cotisation'], 0, ',', ' '); ?> FCFA</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar text-info"></i>
                        <span><?php echo ucfirst($tontine['frequence']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users text-primary"></i>
                        <span><?php echo $tontine['participants_actuels']; ?>/<?php echo $tontine['nombre_participants']; ?> participants</span>
                    </div>
                </div>
                
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" 
                         style="width: <?php echo ($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100; ?>%">
                    </div>
                </div>
                
                <div class="tontine-creator">
                    <div class="creator-avatar">
                        <?php echo strtoupper(substr($tontine['createur_prenom'], 0, 1) . substr($tontine['createur_nom'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="creator-name"><?php echo htmlspecialchars($tontine['createur_prenom'] . ' ' . $tontine['createur_nom']); ?></div>
                        <small class="text-muted">Organisateur</small>
                    </div>
                </div>
            </div>
            
            <div class="tontine-footer">
                <?php if ($tontine['deja_participant']): ?>
                    <button class="btn btn-outline-success w-100" disabled>
                        <i class="fas fa-check"></i> Déjà participant
                    </button>
                <?php elseif ($tontine['places_restantes'] <= 0): ?>
                    <button class="btn btn-outline-secondary w-100" disabled>
                        <i class="fas fa-users"></i> Complet
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary-modern w-100" onclick="rejoindre(<?php echo $tontine['id']; ?>)">
                        <i class="fas fa-plus"></i> Rejoindre (<?php echo $tontine['places_restantes']; ?> place<?php echo $tontine['places_restantes'] > 1 ? 's' : ''; ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">Aucune tontine trouvée</h4>
                <p class="text-muted mb-4">Essayez de modifier vos critères de recherche ou créez votre propre tontine</p>
                <a href="creer-tontine.php" class="btn btn-primary-modern">
                    <i class="fas fa-plus"></i> Créer une Tontine
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="row" data-aos="fade-up" data-aos-delay="300">
    <div class="col-12">
        <nav aria-label="Navigation des tontines">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<?php endif; ?>

<!-- Modal de détails -->
<div class="modal fade" id="tontineDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Tontine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tontineDetailsContent">
                <!-- Contenu chargé via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de participation -->
<div class="modal fade" id="rejoindreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejoindre la Tontine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rejoindreContent">
                    <!-- Contenu chargé via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tontines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.tontine-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
    overflow: hidden;
}

.tontine-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.tontine-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tontine-status {
    display: flex;
    gap: 0.5rem;
}

.tontine-body {
    padding: 1.5rem;
}

.tontine-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.75rem;
}

.tontine-description {
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.tontine-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.tontine-creator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1rem;
}

.creator-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.9rem;
}

.creator-name {
    font-weight: 600;
    color: var(--dark-color);
}

.tontine-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.tontines-grid.list-view {
    display: block;
}

.tontines-grid.list-view .tontine-card {
    display: flex;
    margin-bottom: 1rem;
}

.tontines-grid.list-view .tontine-body {
    flex: 1;
}

@media (max-width: 768px) {
    .tontines-grid {
        grid-template-columns: 1fr;
    }
    
    .tontine-meta {
        flex-direction: column;
    }
}
</style>

<script>
function voirDetails(tontineId) {
    fetch(`actions/get_tontine_details.php?id=${tontineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('tontineDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('tontineDetailsModal')).show();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors du chargement des détails', 'error');
        });
}

function rejoindre(tontineId) {
    fetch(`actions/get_rejoindre_form.php?id=${tontineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('rejoindreContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('rejoindreModal')).show();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors du chargement du formulaire', 'error');
        });
}

function confirmerParticipation(tontineId) {
    if (!confirm('Êtes-vous sûr de vouloir rejoindre cette tontine ?')) {
        return;
    }
    
    fetch('actions/rejoindre_tontine.php', {
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
            showToast('Vous avez rejoint la tontine avec succès !', 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erreur lors de la participation', 'error');
    });
}

function partager(tontineId) {
    const url = `${window.location.origin}/dashboard/tontine-details.php?id=${tontineId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Rejoignez cette tontine sur SamalSakom',
            url: url
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas l'API Web Share
        navigator.clipboard.writeText(url).then(() => {
            showToast('Lien copié dans le presse-papiers !', 'success');
        });
    }
}

// Gestion des vues grille/liste
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('[data-view]');
    const grid = document.getElementById('tontinesGrid');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.dataset.view;
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
