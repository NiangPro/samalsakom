<?php
$page_title = "Gestion des Utilisateurs";
$breadcrumb = "Utilisateurs";
include 'includes/header.php';

// Récupération des utilisateurs avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // Compter le total d'utilisateurs
    $count_query = "SELECT COUNT(*) as total FROM users";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_users = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_users / $limit);
    
    // Récupérer les utilisateurs
    $query = "SELECT * FROM users ORDER BY date_creation DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des utilisateurs.";
}
?>

<div class="page-header">
    <h1 class="page-title">Gestion des Utilisateurs</h1>
    <p class="page-subtitle">Gérez tous les utilisateurs de la plateforme SamalSakom</p>
</div>

<!-- Statistiques rapides -->
<div class="stats-grid mb-4">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_users); ?></div>
        <div class="stat-label">Total Utilisateurs</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $active_query = "SELECT COUNT(*) as count FROM users WHERE statut = 'actif'";
            $active_stmt = $db->prepare($active_query);
            $active_stmt->execute();
            echo number_format($active_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Utilisateurs Actifs</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $today_query = "SELECT COUNT(*) as count FROM users WHERE DATE(date_creation) = CURDATE()";
            $today_stmt = $db->prepare($today_query);
            $today_stmt->execute();
            echo number_format($today_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Inscriptions Aujourd'hui</div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-header">
            <div class="stat-icon danger">
                <i class="fas fa-user-times"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $inactive_query = "SELECT COUNT(*) as count FROM users WHERE statut != 'actif'";
            $inactive_stmt = $db->prepare($inactive_query);
            $inactive_stmt->execute();
            echo number_format($inactive_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Comptes Inactifs</div>
    </div>
</div>

<!-- Table des utilisateurs -->
<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">Liste des Utilisateurs</h3>
        <div class="table-actions">
            <div class="input-group me-3" style="width: 300px;">
                <input type="text" class="form-control" placeholder="Rechercher un utilisateur..." id="searchUsers">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <button class="btn-admin btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Ajouter Utilisateur
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Utilisateur
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Contact
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Inscription
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Statut
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="profile-avatar me-3" style="width: 45px; height: 45px;">
                                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                                <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($user['telephone']); ?></small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></div>
                            <small class="text-muted"><?php echo date('H:i', strtotime($user['date_creation'])); ?></small>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $user['statut'] == 'actif' ? 'active' : 'inactive'; ?>">
                            <?php echo ucfirst($user['statut']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewUser(<?php echo $user['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" 
                                    onclick="editUser(<?php echo $user['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-<?php echo $user['statut'] == 'actif' ? 'danger' : 'success'; ?>" 
                                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['statut']; ?>')"
                                    data-bs-toggle="tooltip" title="<?php echo $user['statut'] == 'actif' ? 'Désactiver' : 'Activer'; ?>">
                                <i class="fas fa-<?php echo $user['statut'] == 'actif' ? 'ban' : 'check'; ?>"></i>
                            </button>
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
            Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $limit, $total_users); ?> 
            sur <?php echo $total_users; ?> utilisateurs
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

<!-- Modal Ajouter Utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="ajax-form" action="actions/add_user.php" method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone *</label>
                            <input type="tel" class="form-control" name="telephone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" name="date_naissance">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="adresse">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" name="mot_de_passe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut">
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: #f8f9fa;
}

.sortable.asc::after {
    content: ' ↑';
    color: var(--admin-primary);
}

.sortable.desc::after {
    content: ' ↓';
    color: var(--admin-primary);
}

.btn-group .btn {
    border-radius: 4px;
    margin-right: 2px;
}

.pagination .page-link {
    color: var(--admin-primary);
    border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
    background-color: var(--admin-primary);
    border-color: var(--admin-primary);
}
</style>

<script>
// Recherche en temps réel
document.getElementById('searchUsers').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.admin-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Fonctions pour les actions utilisateur
function viewUser(userId) {
    // Redirection vers la page de détails
    window.location.href = `user-details.php?id=${userId}`;
}

function editUser(userId) {
    // Ouvrir modal d'édition (à implémenter)
    showToast('Fonctionnalité d\'édition en cours de développement', 'info');
}

function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'actif' ? 'inactif' : 'actif';
    const action = newStatus === 'actif' ? 'activer' : 'désactiver';
    
    if (confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) {
        fetch('actions/toggle_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
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
</script>

<?php include 'includes/footer.php'; ?>
