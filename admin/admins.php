<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Filtres
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "statut = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête principale
$query = "SELECT * FROM admins $where_clause ORDER BY date_creation DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$admins = $stmt->fetchAll();

// Compter le total pour la pagination
$count_query = "SELECT COUNT(*) as total FROM admins $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_admins = $count_stmt->fetch()['total'];
$total_pages = ceil($total_admins / $limit);

// Statistiques
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
    SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
    SUM(CASE WHEN derniere_connexion >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as actifs_semaine
FROM admins";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Gestion des Administrateurs</h1>
                <p class="content-subtitle">Administration des comptes administrateurs</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-outline-primary me-2" onclick="exportAdmins()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-plus me-1"></i>Nouvel Admin
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary me-3">
                                <i class="fas fa-user-shield text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Total Admins</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success me-3">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $stats['actifs']; ?></div>
                                <div class="stat-label">Actifs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning me-3">
                                <i class="fas fa-crown text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $stats['super_admins']; ?></div>
                                <div class="stat-label">Super Admins</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info me-3">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $stats['actifs_semaine']; ?></div>
                                <div class="stat-label">Actifs 7j</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rôle</label>
                        <select class="form-select" name="role">
                            <option value="">Tous les rôles</option>
                            <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="moderateur" <?php echo $role_filter === 'moderateur' ? 'selected' : ''; ?>>Modérateur</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="status">
                            <option value="">Tous</option>
                            <option value="actif" <?php echo $status_filter === 'actif' ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactif" <?php echo $status_filter === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="admins.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table des admins -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="card-title mb-0">Liste des Administrateurs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admin</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière Connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin_item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3">
                                            <?php if ($admin_item['photo_profil']): ?>
                                            <img src="<?php echo htmlspecialchars($admin_item['photo_profil']); ?>" class="avatar-img rounded-circle" alt="Avatar">
                                            <?php else: ?>
                                            <div class="avatar-initial bg-primary text-white rounded-circle">
                                                <?php echo strtoupper(substr($admin_item['prenom'], 0, 1) . substr($admin_item['nom'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($admin_item['prenom'] . ' ' . $admin_item['nom']); ?></div>
                                            <small class="text-muted">ID: <?php echo $admin_item['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($admin_item['email']); ?></td>
                                <td>
                                    <?php
                                    $role_badges = [
                                        'super_admin' => 'bg-danger',
                                        'admin' => 'bg-primary',
                                        'moderateur' => 'bg-info'
                                    ];
                                    $role_labels = [
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'moderateur' => 'Modérateur'
                                    ];
                                    $badge_class = $role_badges[$admin_item['role']] ?? 'bg-secondary';
                                    $role_label = $role_labels[$admin_item['role']] ?? $admin_item['role'];
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $role_label; ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $admin_item['statut'] === 'actif' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($admin_item['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($admin_item['derniere_connexion']): ?>
                                    <div><?php echo date('d/m/Y', strtotime($admin_item['derniere_connexion'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($admin_item['derniere_connexion'])); ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">Jamais</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editAdmin(<?php echo $admin_item['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Modifier
                                            </a></li>
                                            <?php if ($admin_item['id'] !== $_SESSION['admin_id']): ?>
                                            <li><a class="dropdown-item" href="#" onclick="toggleAdminStatus(<?php echo $admin_item['id']; ?>)">
                                                <i class="fas fa-toggle-on me-2"></i>Changer statut
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="resetPassword(<?php echo $admin_item['id']; ?>)">
                                                <i class="fas fa-key me-2"></i>Reset mot de passe
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteAdmin(<?php echo $admin_item['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>Supprimer
                                            </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvel Admin -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvel Administrateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle *</label>
                            <select class="form-select" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="moderateur">Modérateur</option>
                                <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveAdmin()">Créer</button>
            </div>
        </div>
    </div>
</div>

<script>
function editAdmin(id) {
    showToast('Chargement...', 'info');
}

function toggleAdminStatus(id) {
    if (confirm('Changer le statut de cet administrateur ?')) {
        showToast('Statut modifié', 'success');
        setTimeout(() => location.reload(), 1000);
    }
}

function resetPassword(id) {
    if (confirm('Réinitialiser le mot de passe ?')) {
        showToast('Nouveau mot de passe envoyé par email', 'success');
    }
}

function deleteAdmin(id) {
    if (confirm('Supprimer cet administrateur ? Cette action est irréversible.')) {
        showToast('Administrateur supprimé', 'success');
        setTimeout(() => location.reload(), 1000);
    }
}

function saveAdmin() {
    const form = document.getElementById('addAdminForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    showToast('Administrateur créé avec succès', 'success');
    bootstrap.Modal.getInstance(document.getElementById('addAdminModal')).hide();
    setTimeout(() => location.reload(), 1000);
}

function exportAdmins() {
    showToast('Export en cours...', 'info');
}
</script>

<?php include 'includes/footer.php'; ?>
