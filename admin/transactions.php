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
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "c.statut = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "c.type_transaction = ?";
    $params[] = $type_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(c.date_creation) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR t.nom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête principale avec jointures
$query = "SELECT c.*, u.nom as user_nom, u.prenom as user_prenom, u.email as user_email, 
                 t.nom as tontine_nom, t.montant_cotisation
          FROM cotisations c
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN tontines t ON c.tontine_id = t.id
          $where_clause
          ORDER BY c.date_creation DESC
          LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Compter le total pour la pagination
$count_query = "SELECT COUNT(*) as total 
                FROM cotisations c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN tontines t ON c.tontine_id = t.id
                $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_transactions = $count_stmt->fetch()['total'];
$total_pages = ceil($total_transactions / $limit);

// Statistiques des transactions
$stats_query = "SELECT 
    COUNT(*) as total_transactions,
    SUM(CASE WHEN statut = 'completed' THEN montant ELSE 0 END) as total_completed,
    SUM(CASE WHEN statut = 'pending' THEN montant ELSE 0 END) as total_pending,
    SUM(CASE WHEN statut = 'failed' THEN montant ELSE 0 END) as total_failed,
    AVG(montant) as moyenne_transaction
FROM cotisations";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Gestion des Transactions</h1>
                <p class="content-subtitle">Historique complet des cotisations et paiements</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-outline-primary me-2" onclick="exportTransactions()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle Transaction
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
                                <i class="fas fa-receipt text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
                                <div class="stat-label">Total Transactions</div>
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
                                <div class="stat-value"><?php echo number_format($stats['total_completed']); ?> FCFA</div>
                                <div class="stat-label">Montant Validé</div>
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
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['total_pending']); ?> FCFA</div>
                                <div class="stat-label">En Attente</div>
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
                                <i class="fas fa-chart-bar text-white"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo number_format($stats['moyenne_transaction']); ?> FCFA</div>
                                <div class="stat-label">Montant Moyen</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Recherche</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, email, tontine...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="status">
                            <option value="">Tous</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Validé</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Échoué</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">Tous</option>
                            <option value="cotisation" <?php echo $type_filter === 'cotisation' ? 'selected' : ''; ?>>Cotisation</option>
                            <option value="remboursement" <?php echo $type_filter === 'remboursement' ? 'selected' : ''; ?>>Remboursement</option>
                            <option value="penalite" <?php echo $type_filter === 'penalite' ? 'selected' : ''; ?>>Pénalité</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                            <a href="transactions.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table des transactions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Liste des Transactions</h5>
                    <small class="text-muted"><?php echo number_format($total_transactions); ?> transactions au total</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Tontine</th>
                                <th>Montant</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold">#<?php echo $transaction['id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <div class="avatar-initial bg-light text-dark rounded-circle">
                                                <?php echo strtoupper(substr($transaction['user_prenom'], 0, 1) . substr($transaction['user_nom'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($transaction['user_prenom'] . ' ' . $transaction['user_nom']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($transaction['user_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($transaction['tontine_nom']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary"><?php echo number_format($transaction['montant']); ?> FCFA</span>
                                </td>
                                <td>
                                    <?php
                                    $type_badges = [
                                        'cotisation' => 'bg-primary',
                                        'remboursement' => 'bg-success',
                                        'penalite' => 'bg-danger'
                                    ];
                                    $type_labels = [
                                        'cotisation' => 'Cotisation',
                                        'remboursement' => 'Remboursement',
                                        'penalite' => 'Pénalité'
                                    ];
                                    $badge_class = $type_badges[$transaction['type_transaction']] ?? 'bg-secondary';
                                    $type_label = $type_labels[$transaction['type_transaction']] ?? $transaction['type_transaction'];
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $type_label; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'completed' => 'bg-success',
                                        'pending' => 'bg-warning',
                                        'failed' => 'bg-danger'
                                    ];
                                    $status_labels = [
                                        'completed' => 'Validé',
                                        'pending' => 'En attente',
                                        'failed' => 'Échoué'
                                    ];
                                    $badge_class = $status_badges[$transaction['statut']] ?? 'bg-secondary';
                                    $status_label = $status_labels[$transaction['statut']] ?? $transaction['statut'];
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $status_label; ?></span>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($transaction['date_creation'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($transaction['date_creation'])); ?></small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-eye me-2"></i>Voir détails
                                            </a></li>
                                            <?php if ($transaction['statut'] === 'pending'): ?>
                                            <li><a class="dropdown-item text-success" href="#" onclick="validateTransaction(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-check me-2"></i>Valider
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="rejectTransaction(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-times me-2"></i>Rejeter
                                            </a></li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="downloadReceipt(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-download me-2"></i>Télécharger reçu
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0">
                <nav aria-label="Pagination des transactions">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Précédent</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Suivant</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Transaction -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTransactionForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Utilisateur *</label>
                            <select class="form-select" name="user_id" required id="userSelect">
                                <option value="">Sélectionner un utilisateur</option>
                                <!-- Options chargées dynamiquement -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tontine *</label>
                            <select class="form-select" name="tontine_id" required id="tontineSelect">
                                <option value="">Sélectionner une tontine</option>
                                <!-- Options chargées dynamiquement -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Montant (FCFA) *</label>
                            <input type="number" class="form-control" name="montant" required min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de transaction *</label>
                            <select class="form-select" name="type_transaction" required>
                                <option value="cotisation">Cotisation</option>
                                <option value="remboursement">Remboursement</option>
                                <option value="penalite">Pénalité</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveTransaction()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les utilisateurs et tontines au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadUsersAndTontines();
});

function loadUsersAndTontines(userId = null) {
    const url = userId ? `actions/get_users_tontines.php?user_id=${userId}` : 'actions/get_users_tontines.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userSelect = document.getElementById('userSelect');
                const tontineSelect = document.getElementById('tontineSelect');
                
                // Charger les utilisateurs (seulement si pas de userId spécifique)
                if (!userId) {
                    userSelect.innerHTML = '<option value="">Sélectionner un utilisateur</option>';
                    data.data.users.forEach(user => {
                        userSelect.innerHTML += `<option value="${user.id}">${user.nom} (${user.email})</option>`;
                    });
                    
                    // Ajouter l'événement de changement pour l'utilisateur
                    userSelect.addEventListener('change', function() {
                        const selectedUserId = this.value;
                        if (selectedUserId) {
                            loadUsersAndTontines(selectedUserId);
                        } else {
                            loadUsersAndTontines();
                        }
                    });
                }
                
                // Charger les tontines (filtrées par utilisateur si spécifié)
                tontineSelect.innerHTML = '<option value="">Sélectionner une tontine</option>';
                data.data.tontines.forEach(tontine => {
                    tontineSelect.innerHTML += `<option value="${tontine.id}">${tontine.nom} - ${tontine.montant_cotisation} FCFA</option>`;
                });
                
                // Afficher un message si aucune tontine pour cet utilisateur
                if (userId && data.data.tontines.length === 0) {
                    tontineSelect.innerHTML = '<option value="">Aucune tontine pour cet utilisateur</option>';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du chargement des données', 'error');
        });
}

function viewTransaction(id) {
    showToast('Chargement des détails...', 'info');
    // TODO: Implémenter la vue détaillée
}

function validateTransaction(id) {
    if (confirm('Êtes-vous sûr de vouloir valider cette transaction ?')) {
        const formData = new FormData();
        formData.append('transaction_id', id);
        
        fetch('actions/validate_transaction.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors de la validation', 'error');
        });
    }
}

function rejectTransaction(id) {
    const raison = prompt('Raison du rejet (optionnel):');
    if (raison !== null) {
        const formData = new FormData();
        formData.append('transaction_id', id);
        formData.append('raison', raison);
        
        fetch('actions/reject_transaction.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du rejet', 'error');
        });
    }
}

function downloadReceipt(id) {
    showToast('Génération du reçu...', 'info');
    // TODO: Implémenter la génération de reçu
}

function exportTransactions() {
    showToast('Export en cours...', 'info');
    // TODO: Implémenter l'export
}

function saveTransaction() {
    const form = document.getElementById('addTransactionForm');
    const formData = new FormData(form);
    
    // Validation côté client
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Désactiver le bouton pour éviter les doubles soumissions
    const submitBtn = document.querySelector('#addTransactionModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enregistrement...';
    
    fetch('actions/add_transaction.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
            form.reset();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('Erreur lors de l\'enregistrement', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Fonction pour afficher les toasts
function showToast(message, type = 'info') {
    // Créer le toast s'il n'existe pas
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast_' + Date.now();
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Supprimer le toast après fermeture
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
