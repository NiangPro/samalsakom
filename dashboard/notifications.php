<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Paramètres de pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Filtres
    $type_filter = $_GET['type'] ?? '';
    $lu_filter = $_GET['lu'] ?? '';
    
    // Construction de la requête avec filtres
    $where_conditions = ["user_id = ?"];
    $params = [$user_id];
    
    if (!empty($type_filter)) {
        $where_conditions[] = "type = ?";
        $params[] = $type_filter;
    }
    
    if ($lu_filter !== '') {
        $where_conditions[] = "lu = ?";
        $params[] = (int)$lu_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Récupérer les notifications
    $query = "SELECT * FROM notifications 
              WHERE $where_clause 
              ORDER BY date_creation DESC 
              LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    // Compter le total
    $count_query = "SELECT COUNT(*) as total FROM notifications WHERE " . implode(' AND ', array_slice($where_conditions, 0, -2));
    $count_params = array_slice($params, 0, -2);
    $stmt = $db->prepare($count_query);
    $stmt->execute($count_params);
    $total_notifications = $stmt->fetch()['total'];
    $total_pages = ceil($total_notifications / $limit);
    
    // Statistiques
    $stats_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN lu = 0 THEN 1 END) as non_lues,
        COUNT(CASE WHEN type = 'success' THEN 1 END) as success,
        COUNT(CASE WHEN type = 'warning' THEN 1 END) as warning,
        COUNT(CASE WHEN type = 'error' THEN 1 END) as error
        FROM notifications WHERE user_id = ?";
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Erreur notifications: " . $e->getMessage());
    
    // Initialiser les variables par défaut en cas d'erreur
    $notifications = [];
    $total_notifications = 0;
    $total_pages = 0;
    $stats = [
        'total' => 0,
        'non_lues' => 0,
        'success' => 0,
        'warning' => 0,
        'error' => 0
    ];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-bell me-3"></i>
                        Notifications
                    </h1>
                    <p class="page-description">Gérez toutes vos notifications et alertes</p>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="marquerToutLu()">
                            <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                        </button>
                        <button class="btn btn-outline-danger" onclick="supprimerToutLu()">
                            <i class="fas fa-trash me-2"></i>Supprimer lues
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['non_lues'] ?></div>
                        <div class="stat-label">Non Lues</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['success'] ?></div>
                        <div class="stat-label">Succès</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['error'] ?></div>
                        <div class="stat-label">Erreurs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card modern-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type de notification</label>
                        <select name="type" class="form-select">
                            <option value="">Tous les types</option>
                            <option value="info" <?= $type_filter === 'info' ? 'selected' : '' ?>>Information</option>
                            <option value="success" <?= $type_filter === 'success' ? 'selected' : '' ?>>Succès</option>
                            <option value="warning" <?= $type_filter === 'warning' ? 'selected' : '' ?>>Avertissement</option>
                            <option value="error" <?= $type_filter === 'error' ? 'selected' : '' ?>>Erreur</option>
                            <option value="payment" <?= $type_filter === 'payment' ? 'selected' : '' ?>>Paiement</option>
                            <option value="tontine" <?= $type_filter === 'tontine' ? 'selected' : '' ?>>Tontine</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut de lecture</label>
                        <select name="lu" class="form-select">
                            <option value="">Toutes</option>
                            <option value="0" <?= $lu_filter === '0' ? 'selected' : '' ?>>Non lues</option>
                            <option value="1" <?= $lu_filter === '1' ? 'selected' : '' ?>>Lues</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filtrer
                            </button>
                            <a href="notifications.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="card modern-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Mes Notifications
                    <span class="badge bg-primary ms-2"><?= $total_notifications ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($notifications)): ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= $notification['lu'] == 0 ? 'unread' : '' ?>" 
                                 data-id="<?= $notification['id'] ?>"
                                 onclick="marquerCommeLu(<?= $notification['id'] ?>)">
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <div class="notification-icon bg-<?= getNotificationColor($notification['type']) ?>">
                                            <i class="fas <?= getNotificationIcon($notification['type']) ?>"></i>
                                        </div>
                                        <div class="notification-meta">
                                            <h6 class="notification-title"><?= htmlspecialchars($notification['titre']) ?></h6>
                                            <div class="notification-time">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= formatTimeAgo($notification['date_creation']) ?>
                                            </div>
                                        </div>
                                        <div class="notification-actions">
                                            <?php if ($notification['lu'] == 0): ?>
                                                <span class="badge bg-primary">Nouveau</span>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="event.stopPropagation(); supprimerNotification(<?= $notification['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="notification-message">
                                        <?= htmlspecialchars($notification['message']) ?>
                                    </div>
                                    <div class="notification-type">
                                        <span class="badge bg-<?= getNotificationColor($notification['type']) ?>">
                                            <?= ucfirst($notification['type']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Pagination des notifications">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($_GET) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($_GET) ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune notification</h5>
                        <p class="text-muted">Vous n'avez aucune notification pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function getNotificationColor($type) {
    switch($type) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        case 'payment': return 'info';
        case 'tontine': return 'primary';
        default: return 'secondary';
    }
}

function getNotificationIcon($type) {
    switch($type) {
        case 'success': return 'fa-check-circle';
        case 'warning': return 'fa-exclamation-triangle';
        case 'error': return 'fa-times-circle';
        case 'payment': return 'fa-credit-card';
        case 'tontine': return 'fa-piggy-bank';
        default: return 'fa-info-circle';
    }
}

function formatTimeAgo($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days > 0) {
        return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'À l\'instant';
    }
}
?>

<style>
.notifications-list {
    max-height: 600px;
    overflow-y: auto;
}

.notification-item {
    padding: 1.25rem;
    border-bottom: 1px solid var(--gray-200);
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-item:hover {
    background: var(--gray-50);
}

.notification-item.unread {
    background: rgba(46, 139, 87, 0.05);
    border-left: 4px solid var(--primary-color);
}

.notification-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.notification-meta {
    flex: 1;
}

.notification-title {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.notification-time {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.notification-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-message {
    color: var(--gray-700);
    line-height: 1.5;
    margin-bottom: 0.75rem;
}

.notification-type {
    display: flex;
    justify-content: flex-end;
}
</style>

<script>
function marquerCommeLu(notificationId) {
    fetch('actions/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                const badge = item.querySelector('.badge');
                if (badge && badge.textContent === 'Nouveau') {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}

function supprimerNotification(notificationId) {
    if (confirm('Voulez-vous vraiment supprimer cette notification ?')) {
        fetch('actions/delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors de la suppression', 'error');
        });
    }
}

function marquerToutLu() {
    if (confirm('Marquer toutes les notifications comme lues ?')) {
        fetch('actions/mark_all_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors de la mise à jour', 'error');
        });
    }
}

function supprimerToutLu() {
    if (confirm('Supprimer toutes les notifications lues ? Cette action est irréversible.')) {
        fetch('actions/delete_read_notifications.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors de la suppression', 'error');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
