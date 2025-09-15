<?php
session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Créer la table admin_logs si elle n'existe pas
    $create_table = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_id (admin_id),
        INDEX idx_date (date_creation),
        INDEX idx_action (action)
    )";
    $db->exec($create_table);
    
    // Paramètres de pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Filtres
    $action_filter = $_GET['action'] ?? '';
    $admin_filter = $_GET['admin'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    
    // Construction de la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if (!empty($action_filter)) {
        $where_conditions[] = "l.action LIKE ?";
        $params[] = "%{$action_filter}%";
    }
    
    if (!empty($admin_filter)) {
        $where_conditions[] = "a.nom LIKE ? OR a.prenom LIKE ?";
        $params[] = "%{$admin_filter}%";
        $params[] = "%{$admin_filter}%";
    }
    
    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(l.date_creation) = ?";
        $params[] = $date_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Compter le total des logs
    $count_query = "SELECT COUNT(*) as total 
                    FROM admin_logs l 
                    LEFT JOIN admins a ON l.admin_id = a.id 
                    {$where_clause}";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total_logs = $stmt->fetch()['total'];
    $total_pages = ceil($total_logs / $per_page);
    
    // Récupérer les logs avec pagination
    $logs_query = "SELECT l.*, a.nom as admin_nom, a.prenom as admin_prenom 
                   FROM admin_logs l 
                   LEFT JOIN admins a ON l.admin_id = a.id 
                   {$where_clause}
                   ORDER BY l.date_creation DESC 
                   LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $db->prepare($logs_query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Récupérer la liste des actions pour le filtre
    $actions_query = "SELECT DISTINCT action FROM admin_logs ORDER BY action";
    $stmt = $db->prepare($actions_query);
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Récupérer la liste des admins pour le filtre
    $admins_query = "SELECT DISTINCT a.id, a.nom, a.prenom 
                     FROM admins a 
                     INNER JOIN admin_logs l ON a.id = l.admin_id 
                     ORDER BY a.nom, a.prenom";
    $stmt = $db->prepare($admins_query);
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erreur logs admin: " . $e->getMessage());
    $logs = [];
    $total_logs = 0;
    $total_pages = 0;
    $actions = [];
    $admins = [];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-list-alt me-3"></i>
                        Logs d'Administration
                    </h1>
                    <p class="page-description">Historique des actions administratives</p>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" onclick="clearLogs()" title="Vider les logs">
                            <i class="fas fa-trash me-2"></i>Vider les logs
                        </button>
                        <button class="btn btn-outline-primary" onclick="exportLogs()" title="Exporter les logs">
                            <i class="fas fa-download me-2"></i>Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card modern-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary me-3">
                                <i class="fas fa-list"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= number_format($total_logs) ?></h3>
                                <p class="text-muted mb-0">Total Logs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card modern-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success me-3">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <?php
                                $today_query = "SELECT COUNT(*) as count FROM admin_logs WHERE DATE(date_creation) = CURDATE()";
                                $stmt = $db->prepare($today_query);
                                $stmt->execute();
                                $today_count = $stmt->fetch()['count'] ?? 0;
                                ?>
                                <h3 class="mb-0"><?= $today_count ?></h3>
                                <p class="text-muted mb-0">Aujourd'hui</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card modern-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning me-3">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div>
                                <?php
                                $week_query = "SELECT COUNT(*) as count FROM admin_logs WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                                $stmt = $db->prepare($week_query);
                                $stmt->execute();
                                $week_count = $stmt->fetch()['count'] ?? 0;
                                ?>
                                <h3 class="mb-0"><?= $week_count ?></h3>
                                <p class="text-muted mb-0">Cette semaine</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card modern-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <?php
                                $active_admins_query = "SELECT COUNT(DISTINCT admin_id) as count FROM admin_logs WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                                $stmt = $db->prepare($active_admins_query);
                                $stmt->execute();
                                $active_admins = $stmt->fetch()['count'] ?? 0;
                                ?>
                                <h3 class="mb-0"><?= $active_admins ?></h3>
                                <p class="text-muted mb-0">Admins actifs (24h)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card modern-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select">
                            <option value="">Toutes les actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= htmlspecialchars($action) ?>" <?= $action_filter === $action ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $action)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Administrateur</label>
                        <select name="admin" class="form-select">
                            <option value="">Tous les admins</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= htmlspecialchars($admin['nom']) ?>" <?= $admin_filter === $admin['nom'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrer
                            </button>
                            <a href="logs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des logs -->
        <div class="card modern-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historique des Actions (<?= number_format($total_logs) ?> entrées)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun log trouvé</h5>
                        <p class="text-muted">Aucune action administrative n'a été enregistrée avec ces critères.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date/Heure</th>
                                    <th>Administrateur</th>
                                    <th>Action</th>
                                    <th>Détails</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= date('d/m/Y', strtotime($log['date_creation'])) ?></div>
                                            <small class="text-muted"><?= date('H:i:s', strtotime($log['date_creation'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <?= strtoupper(substr($log['admin_prenom'] ?? 'A', 0, 1) . substr($log['admin_nom'] ?? 'D', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars(($log['admin_prenom'] ?? '') . ' ' . ($log['admin_nom'] ?? '')) ?></div>
                                                    <small class="text-muted">ID: <?= $log['admin_id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $action_icons = [
                                                'add_transaction' => 'fa-plus-circle text-success',
                                                'validate_transaction' => 'fa-check-circle text-success',
                                                'reject_transaction' => 'fa-times-circle text-danger',
                                                'login' => 'fa-sign-in-alt text-info',
                                                'logout' => 'fa-sign-out-alt text-secondary',
                                                'create_user' => 'fa-user-plus text-primary',
                                                'update_user' => 'fa-user-edit text-warning',
                                                'delete_user' => 'fa-user-times text-danger',
                                                'create_tontine' => 'fa-piggy-bank text-success',
                                                'update_tontine' => 'fa-edit text-warning',
                                                'delete_tontine' => 'fa-trash text-danger'
                                            ];
                                            $icon = $action_icons[$log['action']] ?? 'fa-cog text-muted';
                                            ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas <?= $icon ?> me-1"></i>
                                                <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="log-details">
                                                <?= htmlspecialchars($log['details']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Pagination des logs" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&action=<?= urlencode($action_filter) ?>&admin=<?= urlencode($admin_filter) ?>&date=<?= urlencode($date_filter) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&action=<?= urlencode($action_filter) ?>&admin=<?= urlencode($admin_filter) ?>&date=<?= urlencode($date_filter) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&action=<?= urlencode($action_filter) ?>&admin=<?= urlencode($admin_filter) ?>&date=<?= urlencode($date_filter) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function clearLogs() {
    if (confirm('Êtes-vous sûr de vouloir vider tous les logs ? Cette action est irréversible.')) {
        fetch('actions/clear_logs.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Logs vidés avec succès', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Erreur lors du vidage des logs', 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors du vidage des logs', 'error');
        });
    }
}

function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('actions/export_logs.php?' + params.toString(), '_blank');
}

function showToast(message, type) {
    // Créer un toast Bootstrap
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Ajouter le toast au container
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialiser et afficher le toast
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Supprimer le toast après fermeture
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
</script>

<style>
.log-details {
    max-width: 300px;
    word-wrap: break-word;
    font-size: 0.9em;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.75rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
</style>

<?php include 'includes/footer.php'; ?>
