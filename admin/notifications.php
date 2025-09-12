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

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Filtres
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';

// Simulation des notifications (en production, ces données viendraient d'une table notifications)
$notifications = [
    [
        'id' => 1,
        'type' => 'user_registration',
        'title' => 'Nouvelle inscription',
        'message' => 'Fatou Diop s\'est inscrite sur la plateforme',
        'priority' => 'medium',
        'status' => 'unread',
        'user_name' => 'Fatou Diop',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'icon' => 'fas fa-user-plus',
        'color' => 'primary'
    ],
    [
        'id' => 2,
        'type' => 'payment_received',
        'title' => 'Paiement reçu',
        'message' => 'Cotisation de 50,000 FCFA reçue pour la tontine "Épargne Solidaire"',
        'priority' => 'high',
        'status' => 'unread',
        'user_name' => 'Moussa Ndiaye',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'icon' => 'fas fa-money-bill-wave',
        'color' => 'success'
    ],
    [
        'id' => 3,
        'type' => 'tontine_completed',
        'title' => 'Tontine terminée',
        'message' => 'La tontine "Projet Immobilier" a été complétée avec succès',
        'priority' => 'high',
        'status' => 'read',
        'user_name' => 'Système',
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        'icon' => 'fas fa-check-circle',
        'color' => 'success'
    ],
    [
        'id' => 4,
        'type' => 'system_alert',
        'title' => 'Alerte système',
        'message' => 'Maintenance programmée demain de 02h00 à 04h00',
        'priority' => 'high',
        'status' => 'unread',
        'user_name' => 'Système',
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'icon' => 'fas fa-exclamation-triangle',
        'color' => 'warning'
    ],
    [
        'id' => 5,
        'type' => 'payment_overdue',
        'title' => 'Paiement en retard',
        'message' => 'Aissatou Ba a un paiement en retard de 3 jours',
        'priority' => 'high',
        'status' => 'unread',
        'user_name' => 'Aissatou Ba',
        'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'icon' => 'fas fa-clock',
        'color' => 'danger'
    ]
];

// Statistiques des notifications
$total_notifications = count($notifications);
$unread_count = count(array_filter($notifications, function($n) { return $n['status'] === 'unread'; }));
$high_priority_count = count(array_filter($notifications, function($n) { return $n['priority'] === 'high'; }));

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Centre de Notifications</h1>
                <p class="content-subtitle">Gestion des alertes et notifications système</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-outline-primary me-2" onclick="markAllAsRead()">
                    <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle Notification
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
                                <i class="fas fa-bell text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $total_notifications; ?></div>
                                <div class="stat-label">Total Notifications</div>
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
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $unread_count; ?></div>
                                <div class="stat-label">Non Lues</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger me-3">
                                <i class="fas fa-exclamation text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $high_priority_count; ?></div>
                                <div class="stat-label">Priorité Haute</div>
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
                                <i class="fas fa-chart-line text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value">94%</div>
                                <div class="stat-label">Taux de Lecture</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="typeFilter">
                            <option value="">Tous les types</option>
                            <option value="user_registration">Inscriptions</option>
                            <option value="payment_received">Paiements</option>
                            <option value="tontine_completed">Tontines</option>
                            <option value="system_alert">Alertes système</option>
                            <option value="payment_overdue">Retards</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Tous</option>
                            <option value="unread">Non lues</option>
                            <option value="read">Lues</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priorité</label>
                        <select class="form-select" id="priorityFilter">
                            <option value="">Toutes</option>
                            <option value="high">Haute</option>
                            <option value="medium">Moyenne</option>
                            <option value="low">Basse</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-filter me-1"></i>Filtrer
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Notifications Récentes</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                        <label class="form-check-label" for="autoRefresh">
                            Actualisation auto
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['status'] === 'unread' ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                        <div class="d-flex align-items-start p-3">
                            <div class="notification-icon me-3">
                                <div class="icon-wrapper bg-<?php echo $notification['color']; ?>">
                                    <i class="<?php echo $notification['icon']; ?> text-white"></i>
                                </div>
                            </div>
                            <div class="notification-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="notification-title mb-0"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <div class="notification-meta">
                                        <?php if ($notification['priority'] === 'high'): ?>
                                        <span class="badge bg-danger me-2">Urgent</span>
                                        <?php elseif ($notification['priority'] === 'medium'): ?>
                                        <span class="badge bg-warning me-2">Moyen</span>
                                        <?php endif; ?>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                </div>
                                <p class="notification-message mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="notification-footer">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($notification['user_name']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock me-1"></i><?php echo date('d/m/Y à H:i', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="notification-actions ms-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if ($notification['status'] === 'unread'): ?>
                                        <li><a class="dropdown-item" href="#" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-check me-2"></i>Marquer comme lue
                                        </a></li>
                                        <?php else: ?>
                                        <li><a class="dropdown-item" href="#" onclick="markAsUnread(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-envelope me-2"></i>Marquer comme non lue
                                        </a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item" href="#" onclick="viewDetails(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-eye me-2"></i>Voir détails
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Supprimer
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Configuration des notifications -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0">
                <h5 class="card-title mb-0">Paramètres de Notification</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">Notifications par Email</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="emailNewUser" checked>
                            <label class="form-check-label" for="emailNewUser">
                                Nouvelles inscriptions
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="emailPayments" checked>
                            <label class="form-check-label" for="emailPayments">
                                Paiements reçus
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="emailOverdue">
                            <label class="form-check-label" for="emailOverdue">
                                Paiements en retard
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">Notifications Push</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="pushUrgent" checked>
                            <label class="form-check-label" for="pushUrgent">
                                Alertes urgentes
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="pushSystem" checked>
                            <label class="form-check-label" for="pushSystem">
                                Alertes système
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="pushDaily">
                            <label class="form-check-label" for="pushDaily">
                                Résumé quotidien
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="saveNotificationSettings()">
                        <i class="fas fa-save me-1"></i>Enregistrer les Paramètres
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Créer Notification -->
<div class="modal fade" id="createNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createNotificationForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Sélectionner un type</option>
                                <option value="system_alert">Alerte système</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="announcement">Annonce</option>
                                <option value="reminder">Rappel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priorité *</label>
                            <select class="form-select" name="priority" required>
                                <option value="low">Basse</option>
                                <option value="medium" selected>Moyenne</option>
                                <option value="high">Haute</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Titre *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message *</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destinataires</label>
                            <select class="form-select" name="recipients">
                                <option value="all">Tous les utilisateurs</option>
                                <option value="admins">Administrateurs seulement</option>
                                <option value="users">Utilisateurs seulement</option>
                                <option value="active">Utilisateurs actifs</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programmation</label>
                            <select class="form-select" name="schedule">
                                <option value="now">Envoyer maintenant</option>
                                <option value="scheduled">Programmer l'envoi</option>
                            </select>
                        </div>
                        <div class="col-12" id="scheduleDateTime" style="display: none;">
                            <label class="form-label">Date et heure d'envoi</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="createNotification()">Créer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh des notifications
let autoRefreshInterval;

document.getElementById('autoRefresh').addEventListener('change', function() {
    if (this.checked) {
        autoRefreshInterval = setInterval(refreshNotifications, 30000); // 30 secondes
    } else {
        clearInterval(autoRefreshInterval);
    }
});

// Démarrer l'auto-refresh par défaut
autoRefreshInterval = setInterval(refreshNotifications, 30000);

function refreshNotifications() {
    // En production, faire un appel AJAX pour récupérer les nouvelles notifications
    console.log('Actualisation des notifications...');
}

function markAsRead(id) {
    const item = document.querySelector(`[data-id="${id}"]`);
    item.classList.remove('unread');
    showToast('Notification marquée comme lue', 'success');
}

function markAsUnread(id) {
    const item = document.querySelector(`[data-id="${id}"]`);
    item.classList.add('unread');
    showToast('Notification marquée comme non lue', 'info');
}

function markAllAsRead() {
    document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.classList.remove('unread');
    });
    showToast('Toutes les notifications ont été marquées comme lues', 'success');
}

function viewDetails(id) {
    showToast('Chargement des détails...', 'info');
    // Afficher une modal avec les détails complets
}

function deleteNotification(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
        const item = document.querySelector(`[data-id="${id}"]`);
        item.remove();
        showToast('Notification supprimée', 'success');
    }
}

function applyFilters() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    
    // Logique de filtrage
    showToast('Filtres appliqués', 'info');
}

function clearFilters() {
    document.getElementById('typeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    showToast('Filtres réinitialisés', 'info');
}

function createNotification() {
    const form = document.getElementById('createNotificationForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    showToast('Notification créée avec succès', 'success');
    bootstrap.Modal.getInstance(document.getElementById('createNotificationModal')).hide();
    form.reset();
}

function saveNotificationSettings() {
    showToast('Paramètres sauvegardés avec succès', 'success');
}

// Gestion de la programmation
document.querySelector('[name="schedule"]').addEventListener('change', function() {
    const scheduleDiv = document.getElementById('scheduleDateTime');
    scheduleDiv.style.display = this.value === 'scheduled' ? 'block' : 'none';
});
</script>

<style>
.notification-item {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.notification-icon .icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-title {
    font-weight: 600;
    color: #495057;
}

.notification-message {
    color: #6c757d;
    font-size: 0.9rem;
}

.notification-footer {
    font-size: 0.8rem;
}

.metric-item {
    text-align: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #495057;
}

.metric-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.metric-trend {
    font-size: 0.75rem;
    font-weight: 500;
}
</style>

<?php include 'includes/footer.php'; ?>
