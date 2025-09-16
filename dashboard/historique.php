<?php
// Démarrer la session seulement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
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
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Filtres
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    
    // Construction de la requête avec filtres
    $where_conditions = ["c.user_id = ?"];
    $params = [$user_id];
    
    if (!empty($type_filter)) {
        $where_conditions[] = "c.type_transaction = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "c.statut = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($date_filter)) {
        switch ($date_filter) {
            case '7days':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '3months':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
        }
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Récupérer les transactions avec pagination
    $query = "SELECT c.*, t.nom as tontine_nom, t.description as tontine_description
              FROM cotisations c
              LEFT JOIN tontines t ON c.tontine_id = t.id
              WHERE $where_clause
              ORDER BY c.date_creation DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Compter le total pour la pagination
    $count_query = "SELECT COUNT(*) as total FROM cotisations c WHERE " . implode(' AND ', $where_conditions);
    $count_params = array_slice($params, 0, count($params) - 2); // Enlever limit et offset
    $stmt = $db->prepare($count_query);
    $stmt->execute($count_params);
    $total_transactions = $stmt->fetch()['total'];
    $total_pages = ceil($total_transactions / $limit);
    
    // Statistiques rapides
    $stats_query = "SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(CASE WHEN statut = 'completed' THEN montant ELSE 0 END), 0) as total_paye,
        COALESCE(SUM(CASE WHEN statut = 'pending' THEN montant ELSE 0 END), 0) as total_pending,
        COUNT(CASE WHEN statut = 'completed' THEN 1 END) as transactions_reussies,
        COUNT(CASE WHEN statut = 'failed' THEN 1 END) as transactions_echouees
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Erreur historique: " . $e->getMessage());
    $error_message = "Erreur de chargement des données";
    
    // Initialiser les variables par défaut en cas d'erreur
    $transactions = [];
    $total_transactions = 0;
    $total_pages = 0;
    $stats = [
        'total_transactions' => 0,
        'total_paye' => 0,
        'total_pending' => 0,
        'transactions_reussies' => 0,
        'transactions_echouees' => 0
    ];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- En-tête de page -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-history me-3"></i>
                        Historique des Transactions
                    </h1>
                    <p class="page-description">Consultez l'historique complet de vos cotisations et paiements</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" onclick="exportHistorique()">
                        <i class="fas fa-download me-2"></i>
                        Exporter
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['total_transactions'] ?></div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['transactions_reussies'] ?></div>
                        <div class="stat-label">Paiements Réussis</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['total_transactions'] - $stats['transactions_reussies'] - $stats['transactions_echouees'] ?></div>
                        <div class="stat-label">En Attente</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['transactions_echouees'] ?></div>
                        <div class="stat-label">Échecs</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_paye'], 0, ',', ' ') ?></div>
                        <div class="stat-label">FCFA Payés</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_pending'], 0, ',', ' ') ?></div>
                        <div class="stat-label">FCFA En Attente</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et raccourcis -->
        <div class="card modern-card mb-4">
            <div class="card-body">
                <!-- Raccourcis rapides -->
                <div class="mb-3">
                    <h6 class="mb-2">Filtres rapides :</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="historique.php" class="btn <?= empty($status_filter) ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="fas fa-list me-1"></i>Tous
                        </a>
                        <a href="historique.php?status=completed" class="btn <?= $status_filter === 'completed' ? 'btn-success' : 'btn-outline-success' ?>">
                            <i class="fas fa-check-circle me-1"></i>Paiements effectués
                        </a>
                        <a href="historique.php?status=pending" class="btn <?= $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
                            <i class="fas fa-clock me-1"></i>Non réglés
                        </a>
                        <a href="historique.php?status=failed" class="btn <?= $status_filter === 'failed' ? 'btn-danger' : 'btn-outline-danger' ?>">
                            <i class="fas fa-times-circle me-1"></i>Échecs
                        </a>
                    </div>
                </div>
                
                <hr>
                
                <!-- Filtres détaillés -->
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type de transaction</label>
                        <select name="type" class="form-select">
                            <option value="">Tous les types</option>
                            <option value="cotisation" <?= $type_filter === 'cotisation' ? 'selected' : '' ?>>Cotisations</option>
                            <option value="recharge" <?= $type_filter === 'recharge' ? 'selected' : '' ?>>Recharges</option>
                            <option value="retrait" <?= $type_filter === 'retrait' ? 'selected' : '' ?>>Retraits</option>
                            <option value="bonus" <?= $type_filter === 'bonus' ? 'selected' : '' ?>>Bonus</option>
                            <option value="remboursement" <?= $type_filter === 'remboursement' ? 'selected' : '' ?>>Remboursements</option>
                            <option value="penalite" <?= $type_filter === 'penalite' ? 'selected' : '' ?>>Pénalités</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut de paiement</label>
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>
                                <i class="fas fa-clock"></i> En attente (Non réglé)
                            </option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>
                                <i class="fas fa-check"></i> Complété (Payé)
                            </option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>
                                <i class="fas fa-times"></i> Échoué
                            </option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>
                                <i class="fas fa-ban"></i> Annulé
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Période</label>
                        <select name="date" class="form-select">
                            <option value="">Toutes les dates</option>
                            <option value="7days" <?= $date_filter === '7days' ? 'selected' : '' ?>>7 derniers jours</option>
                            <option value="30days" <?= $date_filter === '30days' ? 'selected' : '' ?>>30 derniers jours</option>
                            <option value="3months" <?= $date_filter === '3months' ? 'selected' : '' ?>>3 derniers mois</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filtrer
                            </button>
                            <a href="historique.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des transactions -->
        <div class="card modern-card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Historique des Transactions
                    <span class="badge bg-primary ms-2"><?= $total_transactions ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($transactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Tontine</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Mode de Paiement</th>
                                    <th>Statut</th>
                                    <th>Référence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= date('d/m/Y', strtotime($transaction['date_creation'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($transaction['date_creation'])) ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold text-primary"><?= htmlspecialchars($transaction['tontine_nom'] ?? 'N/A') ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($transaction['tontine_description'] ?? '', 0, 30)) ?>
                                                    <?= strlen($transaction['tontine_description'] ?? '') > 30 ? '...' : '' ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= ucfirst($transaction['type_transaction']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $montant_class = 'text-success';
                                            $montant_icon = 'fa-plus';
                                            if (in_array($transaction['type_transaction'], ['retrait', 'penalite'])) {
                                                $montant_class = 'text-danger';
                                                $montant_icon = 'fa-minus';
                                            }
                                            ?>
                                            <span class="fw-bold <?= $montant_class ?>">
                                                <i class="fas <?= $montant_icon ?> me-1"></i>
                                                <?= number_format($transaction['montant'], 0, ',', ' ') ?> FCFA
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($transaction['methode_paiement'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas <?= getPaymentIcon($transaction['methode_paiement']) ?> me-2"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['methode_paiement'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_config = [
                                                'pending' => ['class' => 'warning', 'text' => 'En attente', 'icon' => 'fa-clock'],
                                                'completed' => ['class' => 'success', 'text' => 'Payé', 'icon' => 'fa-check-circle'],
                                                'failed' => ['class' => 'danger', 'text' => 'Échoué', 'icon' => 'fa-times-circle'],
                                                'cancelled' => ['class' => 'secondary', 'text' => 'Annulé', 'icon' => 'fa-ban']
                                            ];
                                            
                                            $config = $status_config[$transaction['statut']] ?? ['class' => 'secondary', 'text' => $transaction['statut'], 'icon' => 'fa-question'];
                                            ?>
                                            <span class="badge bg-<?= $config['class'] ?> d-flex align-items-center gap-1" style="width: fit-content;">
                                                <i class="fas <?= $config['icon'] ?>"></i>
                                                <?= $config['text'] ?>
                                            </span>
                                            <?php if ($transaction['statut'] === 'pending'): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Paiement non réglé
                                                    </small>
                                                </div>
                                            <?php elseif ($transaction['statut'] === 'completed'): ?>
                                                <div class="mt-1">
                                                    <small class="text-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        Paiement effectué
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($transaction['reference_paiement'])): ?>
                                                <code class="small"><?= htmlspecialchars($transaction['reference_paiement']) ?></code>
                                            <?php else: ?>
                                                <?php 
                                                // Générer une référence basée sur l'ID et la date
                                                $ref = 'TXN' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT) . date('ymd', strtotime($transaction['date_creation']));
                                                ?>
                                                <code class="small text-muted"><?= $ref ?></code>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="voirDetails(<?= $transaction['id'] ?>)"
                                                        data-bs-toggle="tooltip" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($transaction['statut'] === 'completed' && $transaction['reference_paiement']): ?>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="telechargerRecu(<?= $transaction['id'] ?>)"
                                                            data-bs-toggle="tooltip" title="Télécharger reçu">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                <?php elseif ($transaction['statut'] === 'pending'): ?>
                                                    <button class="btn btn-outline-warning" 
                                                            onclick="relancerPaiement(<?= $transaction['id'] ?>)"
                                                            data-bs-toggle="tooltip" title="Relancer le paiement">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Pagination des transactions">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm me-1" onclick="voirDetails(<?php echo $transaction['id']; ?>)">
                                                <i class="fas fa-eye"></i> Voir détails
                                            </button>
                                            <?php if ($transaction['statut'] == 'completed'): ?>
                                                <a href="actions/generate_invoice.php?id=<?php echo $transaction['id']; ?>" 
                                                   class="btn btn-outline-success btn-sm" 
                                                   target="_blank"
                                                   title="Télécharger la facture">
                                                    <i class="fas fa-download"></i> Facture
                                                </a>
                                            <?php endif; ?>
                                        </td>
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
                        <?php if (!empty($status_filter)): ?>
                            <?php if ($status_filter === 'pending'): ?>
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="text-muted">Aucun paiement en attente</h5>
                                <p class="text-muted">Tous vos paiements sont à jour ! Félicitations.</p>
                            <?php elseif ($status_filter === 'completed'): ?>
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">Aucun paiement effectué</h5>
                                <p class="text-muted">Vous n'avez pas encore effectué de paiements.</p>
                            <?php elseif ($status_filter === 'failed'): ?>
                                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                <h5 class="text-muted">Aucun paiement échoué</h5>
                                <p class="text-muted">Parfait ! Tous vos paiements se sont bien déroulés.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune transaction trouvée</h5>
                            <p class="text-muted">Vos transactions apparaîtront ici une fois que vous aurez effectué des paiements.</p>
                        <?php endif; ?>
                        <a href="decouvrir-tontines.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Découvrir les tontines
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails transaction -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<?php
function getPaymentIcon($mode) {
    switch ($mode) {
        case 'orange_money': return 'fa-mobile-alt';
        case 'wave': return 'fa-wave-square';
        case 'virement': return 'fa-university';
        default: return 'fa-credit-card';
    }
}
?>

<script>
function voirDetails(transactionId) {
    fetch(`actions/get_transaction_details.php?id=${transactionId}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    document.getElementById('detailsModalBody').innerHTML = data.html;
                    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                    modal.show();
                } else {
                    showToast(data.message || 'Erreur inconnue', 'error');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Raw response:', text);
                showToast('Erreur de format de réponse du serveur', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showToast('Erreur lors du chargement des détails', 'error');
        });
}

function telechargerRecu(transactionId) {
    window.open(`actions/generate_receipt.php?id=${transactionId}`, '_blank');
}

function exportHistorique() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open(`actions/export_historique.php?${params.toString()}`, '_blank');
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

function relancerPaiement(transactionId) {
    if (confirm('Voulez-vous relancer ce paiement en attente ?')) {
        fetch(`actions/relancer_paiement.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                transaction_id: transactionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Recharger la page après 2 secondes
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors de la relance du paiement', 'error');
        });
    }
}

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
