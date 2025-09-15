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
<<<<<<< HEAD
                    <button class="btn btn-outline-primary" onclick="exportHistorique()">
                        <i class="fas fa-download me-2"></i>
                        Exporter
                    </button>
=======
                    <button class="btn btn-outline-primary me-2" onclick="exportHistorique()">
                        <i class="fas fa-download me-2"></i>
                        Exporter
                    </button>
                    <button class="btn btn-primary" onclick="rechargerPortefeuille()">
                        <i class="fas fa-plus me-2"></i>
                        Recharger
                    </button>
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
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
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_paye'], 0, ',', ' ') ?></div>
                        <div class="stat-label">FCFA Payés</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_pending'], 0, ',', ' ') ?></div>
                        <div class="stat-label">FCFA En Attente</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?= $stats['total_transactions'] > 0 ? round(($stats['transactions_reussies'] / $stats['total_transactions']) * 100) : 0 ?>%
                        </div>
                        <div class="stat-label">Taux de Réussite</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card modern-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type de transaction</label>
                        <select name="type" class="form-select">
                            <option value="">Tous les types</option>
                            <option value="cotisation" <?= $type_filter === 'cotisation' ? 'selected' : '' ?>>Cotisations</option>
<<<<<<< HEAD
                            <option value="recharge" <?= $type_filter === 'recharge' ? 'selected' : '' ?>>Recharges</option>
                            <option value="retrait" <?= $type_filter === 'retrait' ? 'selected' : '' ?>>Retraits</option>
                            <option value="bonus" <?= $type_filter === 'bonus' ? 'selected' : '' ?>>Bonus</option>
                            <option value="remboursement" <?= $type_filter === 'remboursement' ? 'selected' : '' ?>>Remboursements</option>
                            <option value="penalite" <?= $type_filter === 'penalite' ? 'selected' : '' ?>>Pénalités</option>
=======
                            <option value="retrait" <?= $type_filter === 'retrait' ? 'selected' : '' ?>>Retraits</option>
                            <option value="bonus" <?= $type_filter === 'bonus' ? 'selected' : '' ?>>Bonus</option>
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>En attente</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Complété</option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Échoué</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
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
                                            <span class="fw-bold text-success">
                                                <?= number_format($transaction['montant'], 0, ',', ' ') ?> FCFA
                                            </span>
                                        </td>
                                        <td>
<<<<<<< HEAD
                                            <?php if (!empty($transaction['methode_paiement'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas <?= getPaymentIcon($transaction['methode_paiement']) ?> me-2"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['methode_paiement'])) ?>
=======
                                            <?php if ($transaction['mode_paiement']): ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas <?= getPaymentIcon($transaction['mode_paiement']) ?> me-2"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['mode_paiement'])) ?>
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ][$transaction['statut']] ?? 'secondary';
                                            
                                            $status_text = [
                                                'pending' => 'En attente',
                                                'completed' => 'Complété',
                                                'failed' => 'Échoué',
                                                'cancelled' => 'Annulé'
                                            ][$transaction['statut']] ?? $transaction['statut'];
                                            ?>
                                            <span class="badge bg-<?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td>
<<<<<<< HEAD
                                            <?php if (!empty($transaction['reference_paiement'])): ?>
                                                <code class="small"><?= htmlspecialchars($transaction['reference_paiement']) ?></code>
                                            <?php else: ?>
                                                <?php 
                                                // Générer une référence basée sur l'ID et la date
                                                $ref = 'TXN' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT) . date('ymd', strtotime($transaction['date_creation']));
                                                ?>
                                                <code class="small text-muted"><?= $ref ?></code>
=======
                                            <?php if ($transaction['reference_paiement']): ?>
                                                <code class="small"><?= htmlspecialchars($transaction['reference_paiement']) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
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
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune transaction trouvée</h5>
                        <p class="text-muted">Vos transactions apparaîtront ici une fois que vous aurez effectué des paiements.</p>
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('detailsModalBody').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
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

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<<<<<<< HEAD
=======
<!-- Modal Recharge -->
<div class="modal fade" id="walletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="walletModalTitle">Recharger le Portefeuille</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="walletModalBody"></div>
        </div>
    </div>
    </div>

<script>
// Recharge depuis l'historique
function rechargerPortefeuille() {
    fetch('actions/get_recharge_form.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('walletModalTitle').textContent = 'Recharger le Portefeuille';
                document.getElementById('walletModalBody').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('walletModal')).show();
            } else {
                showToast(data.message || 'Erreur de chargement', 'danger');
            }
        })
        .catch(() => showToast('Erreur de connexion', 'danger'));
}

function confirmerRecharge() {
    const form = document.getElementById('rechargeForm');
    if (!form || !form.reportValidity()) return;
    const formData = new FormData(form);
    fetch('actions/process_recharge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            montant: parseInt(formData.get('montant'), 10),
            mode_paiement: formData.get('mode_paiement')
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast(d.message, 'success');
            const m = bootstrap.Modal.getInstance(document.getElementById('walletModal'));
            if (m) m.hide();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Erreur lors de la recharge', 'danger');
        }
    })
    .catch(() => showToast('Erreur de connexion', 'danger'));
}
</script>

>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
<?php include 'includes/footer.php'; ?>
