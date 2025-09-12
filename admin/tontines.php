<?php
$page_title = "Gestion des Tontines";
$breadcrumb = "Tontines";
include 'includes/header.php';

// Récupération des tontines avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    // Compter le total de tontines
    $count_query = "SELECT COUNT(*) as total FROM tontines";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_tontines = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_tontines / $limit);
    
    // Récupérer les tontines avec informations sur le créateur
    $query = "SELECT t.*, u.prenom, u.nom as createur_nom, u.email,
              (SELECT COUNT(*) FROM participations p WHERE p.tontine_id = t.id) as nb_participants
              FROM tontines t 
              LEFT JOIN users u ON t.createur_id = u.id 
              ORDER BY t.date_creation DESC 
              LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $tontines = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des tontines.";
}
?>

<div class="page-header">
    <h1 class="page-title">Gestion des Tontines</h1>
    <p class="page-subtitle">Gérez toutes les tontines de la plateforme SamalSakom</p>
</div>

<!-- Statistiques rapides -->
<div class="stats-grid mb-4">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_tontines); ?></div>
        <div class="stat-label">Total Tontines</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-play-circle"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $active_query = "SELECT COUNT(*) as count FROM tontines WHERE statut = 'active'";
            $active_stmt = $db->prepare($active_query);
            $active_stmt->execute();
            echo number_format($active_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Tontines Actives</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $pending_query = "SELECT COUNT(*) as count FROM tontines WHERE statut = 'en_attente'";
            $pending_stmt = $db->prepare($pending_query);
            $pending_stmt->execute();
            echo number_format($pending_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">En Attente</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $participants_query = "SELECT COUNT(*) as count FROM participations";
            $participants_stmt = $db->prepare($participants_query);
            $participants_stmt->execute();
            echo number_format($participants_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Total Participants</div>
    </div>
</div>

<!-- Filtres et actions -->
<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">Liste des Tontines</h3>
        <div class="table-actions">
            <div class="d-flex gap-2">
                <select class="form-select" id="statusFilter" style="width: auto;">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actives</option>
                    <option value="en_attente">En attente</option>
                    <option value="terminee">Terminées</option>
                    <option value="suspendue">Suspendues</option>
                </select>
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Rechercher une tontine..." id="searchTontines">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <button class="btn-admin btn-primary" data-bs-toggle="modal" data-bs-target="#addTontineModal">
                    <i class="fas fa-plus"></i> Nouvelle Tontine
                </button>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Tontine
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Créateur
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Participants
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Montant
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Statut
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Date Création
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tontines as $tontine): ?>
                <tr>
                    <td>
                        <div>
                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($tontine['nom']); ?></div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars(substr($tontine['description'], 0, 50)); ?>...
                            </small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium"><?php echo htmlspecialchars($tontine['prenom'] . ' ' . $tontine['createur_nom']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($tontine['email']); ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info me-2"><?php echo $tontine['nb_participants']; ?></span>
                            <small class="text-muted">/ <?php echo $tontine['nombre_participants']; ?> max</small>
                        </div>
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar" style="width: <?php echo ($tontine['nb_participants'] / $tontine['nombre_participants']) * 100; ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div class="fw-semibold"><?php echo number_format($tontine['montant_cotisation']); ?> FCFA</div>
                            <small class="text-muted">par personne</small>
                        </div>
                    </td>
                    <td>
                        <?php
                        $status_class = '';
                        switch($tontine['statut']) {
                            case 'active': $status_class = 'active'; break;
                            case 'en_attente': $status_class = 'pending'; break;
                            case 'terminee': $status_class = 'completed'; break;
                            case 'suspendue': $status_class = 'inactive'; break;
                            default: $status_class = 'inactive';
                        }
                        ?>
                        <span class="status-badge status-<?php echo $status_class; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $tontine['statut'])); ?>
                        </span>
                    </td>
                    <td>
                        <div>
                            <div><?php echo date('d/m/Y', strtotime($tontine['date_creation'])); ?></div>
                            <small class="text-muted"><?php echo date('H:i', strtotime($tontine['date_creation'])); ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewTontine(<?php echo $tontine['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" 
                                    onclick="editTontine(<?php echo $tontine['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="manageTontine(<?php echo $tontine['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Gérer participants">
                                <i class="fas fa-users-cog"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteTontine(<?php echo $tontine['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'active')">
                                        <i class="fas fa-play text-success me-2"></i>Activer
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'suspendue')">
                                        <i class="fas fa-pause text-warning me-2"></i>Suspendre
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'terminee')">
                                        <i class="fas fa-stop text-danger me-2"></i>Terminer
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteTontine(<?php echo $tontine['id']; ?>)">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3">
        <div class="text-muted">
            Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $limit, $total_tontines); ?> 
            sur <?php echo $total_tontines; ?> tontines
        </div>
        <nav>
            <ul class="pagination mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Précédent</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Suivant</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nouvelle Tontine -->
<div class="modal fade" id="addTontineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une Nouvelle Tontine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="ajax-form" action="actions/add_tontine.php" method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nom de la tontine *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Montant par personne (FCFA) *</label>
                            <input type="number" class="form-control" name="montant_par_personne" min="1000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de participants *</label>
                            <input type="number" class="form-control" name="nombre_participants" min="2" max="50" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fréquence *</label>
                            <select class="form-select" name="frequence" required>
                                <option value="">Choisir...</option>
                                <option value="hebdomadaire">Hebdomadaire</option>
                                <option value="mensuelle">Mensuelle</option>
                                <option value="trimestrielle">Trimestrielle</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de début</label>
                            <input type="date" class="form-control" name="date_debut">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Créateur (utilisateur) *</label>
                            <select class="form-select" name="createur_id" required>
                                <option value="">Sélectionner un utilisateur...</option>
                                <?php
                                $users_query = "SELECT id, prenom, nom, email FROM users WHERE statut = 'actif' ORDER BY prenom, nom";
                                $users_stmt = $db->prepare($users_query);
                                $users_stmt->execute();
                                $users_list = $users_stmt->fetchAll();
                                foreach ($users_list as $user):
                                ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut initial</label>
                            <select class="form-select" name="statut">
                                <option value="en_attente">En attente</option>
                                <option value="active">Active</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer la tontine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.progress {
    background-color: #e9ecef;
}

.progress-bar {
    background-color: var(--admin-primary);
}

.dropdown-menu {
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dropdown-item {
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
// Recherche et filtrage
document.getElementById('searchTontines').addEventListener('input', function() {
    filterTontines();
});

document.getElementById('statusFilter').addEventListener('change', function() {
    filterTontines();
});

function filterTontines() {
    const searchTerm = document.getElementById('searchTontines').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.admin-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const statusBadge = row.querySelector('.status-badge');
        const status = statusBadge ? statusBadge.textContent.toLowerCase().replace(' ', '_') : '';
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status.includes(statusFilter);
        
        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

// Fonctions pour les actions tontines
function viewTontine(tontineId) {
    window.location.href = `tontine-details.php?id=${tontineId}`;
}

function editTontine(tontineId) {
    showToast('Fonctionnalité d\'édition en cours de développement', 'info');
}

function manageTontine(tontineId) {
    window.location.href = `tontine-participants.php?id=${tontineId}`;
}

function changeTontineStatus(tontineId, newStatus) {
    const statusNames = {
        'active': 'activer',
        'suspendue': 'suspendre',
        'terminee': 'terminer'
    };
    
    if (confirm(`Êtes-vous sûr de vouloir ${statusNames[newStatus]} cette tontine ?`)) {
        fetch('actions/change_tontine_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tontine_id: tontineId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la modification', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}

function deleteTontine(tontineId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tontine ? Cette action est irréversible.')) {
        fetch('actions/delete_tontine.php', {
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
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la suppression', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
