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
    
    // Calculer le solde total
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction IN ('cotisation', 'recharge', 'bonus') THEN montant ELSE 0 END), 0) as total_entrees,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($solde_query);
    $stmt->execute([$user_id]);
    $solde_data = $stmt->fetch();
    
    $solde_disponible = $solde_data['total_entrees'] - $solde_data['total_retire'];
    
    // Détail pour l'affichage
    $detail_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'recharge' THEN montant ELSE 0 END), 0) as total_recharge,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'bonus' THEN montant ELSE 0 END), 0) as total_bonus,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($detail_query);
    $stmt->execute([$user_id]);
    $detail_data = $stmt->fetch();
    
    // Récupérer les tontines actives
    $tontines_query = "SELECT t.*, p.date_participation, 
        COUNT(p2.id) as participants_actuels,
        COALESCE(SUM(CASE WHEN c.statut = 'completed' THEN c.montant ELSE 0 END), 0) as mes_cotisations
        FROM tontines t
        JOIN participations p ON t.id = p.tontine_id AND p.user_id = ? AND p.statut != 'retire'
        LEFT JOIN participations p2 ON t.id = p2.tontine_id AND p2.statut != 'retire'
        LEFT JOIN cotisations c ON t.id = c.tontine_id AND c.user_id = ? AND c.statut = 'completed'
        WHERE t.statut = 'active'
        GROUP BY t.id
        ORDER BY p.date_participation DESC";
    $stmt = $db->prepare($tontines_query);
    $stmt->execute([$user_id, $user_id]);
    $mes_tontines = $stmt->fetchAll();
    
    // Statistiques des paiements
    $stats_query = "SELECT 
        COUNT(*) as total_paiements,
        COUNT(CASE WHEN statut = 'completed' THEN 1 END) as paiements_regles,
        COUNT(CASE WHEN statut = 'pending' THEN 1 END) as paiements_en_attente,
        COUNT(CASE WHEN statut = 'failed' THEN 1 END) as paiements_echoues,
        COALESCE(SUM(CASE WHEN statut = 'completed' THEN montant ELSE 0 END), 0) as montant_total_regle,
        COALESCE(SUM(CASE WHEN statut = 'pending' THEN montant ELSE 0 END), 0) as montant_en_attente
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id]);
    $stats_paiements = $stmt->fetch();
    
    // Transactions récentes avec plus de détails
    $transactions_query = "SELECT c.*, t.nom as tontine_nom, c.methode_paiement, c.reference_paiement
        FROM cotisations c 
        LEFT JOIN tontines t ON c.tontine_id = t.id 
        WHERE c.user_id = ? 
        ORDER BY c.date_creation DESC 
        LIMIT 10";
    $stmt = $db->prepare($transactions_query);
    $stmt->execute([$user_id]);
    $transactions_recentes = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erreur portefeuille: " . $e->getMessage());
    
    // Initialiser les variables par défaut en cas d'erreur
    $solde_data = [
        'total_cotise' => 0,
        'total_retire' => 0,
        'total_bonus' => 0
    ];
    $solde_disponible = 0;
    $mes_tontines = [];
    $transactions_recentes = [];
    $stats_paiements = [
        'total_paiements' => 0,
        'paiements_regles' => 0,
        'paiements_en_attente' => 0,
        'paiements_echoues' => 0,
        'montant_total_regle' => 0,
        'montant_en_attente' => 0
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
                        <i class="fas fa-wallet me-3"></i>
                        Mon Portefeuille
                    </h1>
                    <p class="page-description">Gérez vos finances et suivez vos investissements</p>
                </div>
            </div>
        </div>

        <!-- Solde principal -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card modern-card wallet-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-white mb-1">Solde Disponible</h3>
                                <h1 class="text-white fw-bold mb-3 solde-amount"><?= number_format($solde_disponible, 0, ',', ' ') ?> FCFA</h1>
                                <div class="row text-white-50">
                                    <div class="col-3">
                                        <small>Cotisé</small>
                                        <div class="fw-semibold cotise-amount"><?= number_format($detail_data['total_cotise'], 0, ',', ' ') ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small>Rechargé</small>
                                        <div class="fw-semibold recharge-amount"><?= number_format($detail_data['total_recharge'], 0, ',', ' ') ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small>Bonus</small>
                                        <div class="fw-semibold bonus-amount"><?= number_format($detail_data['total_bonus'], 0, ',', ' ') ?></div>
                                    </div>
                                    <div class="col-3">
                                        <small>Retiré</small>
                                        <div class="fw-semibold retire-amount"><?= number_format($detail_data['total_retire'], 0, ',', ' ') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="wallet-actions">
                                    <button class="btn btn-light btn-sm mb-2" onclick="demanderRetrait()">
                                        <i class="fas fa-arrow-up me-2"></i>Retirer
                                    </button>
                                    <button class="btn btn-outline-light btn-sm" onclick="rechargerPortefeuille()">
                                        <i class="fas fa-plus me-2"></i>Recharger
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card modern-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-piggy-bank fa-3x text-primary"></i>
                        </div>
                        <h5>Mes Tontines</h5>
                        <h2 class="text-primary"><?= count($mes_tontines) ?></h2>
                        <p class="text-muted mb-0">Tontines actives</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mes tontines -->
            <div class="col-lg-8">
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-piggy-bank me-2"></i>
                            Mes Tontines Actives
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mes_tontines)): ?>
                            <?php foreach ($mes_tontines as $tontine): ?>
                                <div class="tontine-item mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1"><?= htmlspecialchars($tontine['nom']) ?></h6>
                                            <small class="text-muted"><?= $tontine['participants_actuels'] ?>/<?= $tontine['nombre_participants'] ?> participants</small>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="fw-bold text-success"><?= number_format($tontine['mes_cotisations'], 0, ',', ' ') ?> FCFA</div>
                                            <small class="text-muted">Mes cotisations</small>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="progress mb-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?= ($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100 ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= round(($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100) ?>% complet</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Vous ne participez à aucune tontine actuellement</p>
                                <a href="decouvrir-tontines.php" class="btn btn-primary">Découvrir les tontines</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistiques des paiements -->
            <div class="col-lg-4">
                <div class="card modern-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Statistiques Paiements
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-success"><?= $stats_paiements['paiements_regles'] ?></div>
                                    <div class="stat-label">Réglés</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-warning"><?= $stats_paiements['paiements_en_attente'] ?></div>
                                    <div class="stat-label">En attente</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-danger"><?= $stats_paiements['paiements_echoues'] ?></div>
                                    <div class="stat-label">Échoués</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-primary"><?= $stats_paiements['total_paiements'] ?></div>
                                    <div class="stat-label">Total</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Montant réglé</span>
                                <span class="fw-bold text-success"><?= number_format($stats_paiements['montant_total_regle'], 0, ',', ' ') ?> FCFA</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">En attente</span>
                                <span class="fw-bold text-warning"><?= number_format($stats_paiements['montant_en_attente'], 0, ',', ' ') ?> FCFA</span>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="paiements.php" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-credit-card me-1"></i>Paiements
                            </a>
                            <a href="historique.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-history me-1"></i>Historique
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Paiements Détaillés -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card modern-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Mes Paiements
                            </h5>
                            <div class="d-flex gap-2">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="statusFilter" id="filterAll" value="all" checked>
                                    <label class="btn btn-outline-secondary" for="filterAll">
                                        Tous (<?= $stats_paiements['total_paiements'] ?>)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="filterCompleted" value="completed">
                                    <label class="btn btn-outline-success" for="filterCompleted">
                                        Réglés (<?= $stats_paiements['paiements_regles'] ?>)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="filterPending" value="pending">
                                    <label class="btn btn-outline-warning" for="filterPending">
                                        En attente (<?= $stats_paiements['paiements_en_attente'] ?>)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="filterFailed" value="failed">
                                    <label class="btn btn-outline-danger" for="filterFailed">
                                        Échoués (<?= $stats_paiements['paiements_echoues'] ?>)
                                    </label>
                                </div>
                                <a href="paiements.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Nouveau paiement
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($transactions_recentes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Tontine</th>
                                            <th>Type</th>
                                            <th>Montant</th>
                                            <th>Mode</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentsTableBody">
                                        <?php foreach ($transactions_recentes as $transaction): ?>
                                        <tr class="payment-row" data-status="<?= $transaction['statut'] ?>">
                                            <td>
                                                <div class="fw-semibold"><?= date('d/m/Y', strtotime($transaction['date_creation'])) ?></div>
                                                <small class="text-muted"><?= date('H:i', strtotime($transaction['date_creation'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($transaction['tontine_nom'] ?? 'N/A') ?></div>
                                                <small class="text-muted">ID: #<?= $transaction['id'] ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['type_transaction'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold <?= $transaction['type_transaction'] === 'retrait' ? 'text-danger' : 'text-success' ?>">
                                                    <?= $transaction['type_transaction'] === 'retrait' ? '-' : '+' ?>
                                                    <?= number_format($transaction['montant'], 0, ',', ' ') ?> FCFA
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (isset($transaction['methode_paiement']) && $transaction['methode_paiement']): ?>
                                                    <span class="badge bg-secondary">
                                                        <?= ucfirst(str_replace('_', ' ', $transaction['methode_paiement'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                $status_text = '';
                                                
                                                switch ($transaction['statut']) {
                                                    case 'completed':
                                                        $status_class = 'bg-success';
                                                        $status_icon = 'fa-check';
                                                        $status_text = 'Réglé';
                                                        break;
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        $status_icon = 'fa-clock';
                                                        $status_text = 'En attente';
                                                        break;
                                                    case 'failed':
                                                        $status_class = 'bg-danger';
                                                        $status_icon = 'fa-times';
                                                        $status_text = 'Échoué';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-secondary';
                                                        $status_icon = 'fa-ban';
                                                        $status_text = 'Annulé';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                        $status_icon = 'fa-question';
                                                        $status_text = ucfirst($transaction['statut']);
                                                }
                                                ?>
                                                <span class="badge <?= $status_class ?>">
                                                    <i class="fas <?= $status_icon ?> me-1"></i>
                                                    <?= $status_text ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            onclick="voirDetails(<?= $transaction['id'] ?>)"
                                                            title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($transaction['statut'] === 'pending'): ?>
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="relancerPaiement(<?= $transaction['id'] ?>)"
                                                            title="Relancer le paiement">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($transaction['statut'] === 'completed' && isset($transaction['reference_paiement']) && $transaction['reference_paiement']): ?>
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="telechargerRecu(<?= $transaction['id'] ?>)"
                                                            title="Télécharger reçu">
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
                            
                            <?php if (count($transactions_recentes) >= 10): ?>
                            <div class="text-center mt-3">
                                <a href="historique.php" class="btn btn-outline-primary">
                                    <i class="fas fa-history me-1"></i>
                                    Voir tout l'historique
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun paiement</h5>
                                <p class="text-muted mb-4">Vous n'avez effectué aucun paiement pour le moment</p>
                                <a href="paiements.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Effectuer un paiement
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails Transaction -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>
                    Détails de la Transaction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recharger -->
<div class="modal fade" id="rechargerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Recharger le Portefeuille
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rechargerForm">
                    <div class="mb-3">
                        <label class="form-label">Montant à recharger (FCFA)</label>
                        <input type="number" class="form-control" id="montant_recharge" min="1000" step="500" required>
                        <small class="form-text text-muted">Montant minimum : 1000 FCFA</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Méthode de paiement</label>
                        <select class="form-control" id="methode_paiement" required>
                            <option value="">Choisir une méthode</option>
                            <option value="orange_money">Orange Money</option>
                            <option value="mtn_money">MTN Mobile Money</option>
                            <option value="moov_money">Moov Money</option>
                            <option value="wave">Wave</option>
                        </select>
                    </div>
                    <div class="mb-3" id="numero_telephone_group" style="display: none;">
                        <label class="form-label">Numéro de téléphone</label>
                        <input type="tel" class="form-control" id="numero_telephone" placeholder="Ex: 77123456">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions :</strong>
                        <ol class="mb-0 mt-2">
                            <li>Composez le code de votre opérateur</li>
                            <li>Suivez les instructions pour autoriser le paiement</li>
                            <li>Votre solde sera mis à jour automatiquement</li>
                        </ol>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmerRecharge()">
                    <i class="fas fa-credit-card me-2"></i>Procéder au paiement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Retirer -->
<div class="modal fade" id="retirerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up me-2"></i>Demander un Retrait
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="retirerForm">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Solde disponible :</strong> <?= number_format($solde_disponible, 0, ',', ' ') ?> FCFA
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant à retirer (FCFA)</label>
                        <input type="number" class="form-control" id="montant_retrait" 
                               min="1000" max="<?= $solde_disponible ?>" step="500" required>
                        <small class="form-text text-muted">
                            Montant minimum : 1000 FCFA | Maximum : <?= number_format($solde_disponible, 0, ',', ' ') ?> FCFA
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Méthode de retrait</label>
                        <select class="form-control" id="methode_retrait" required>
                            <option value="">Choisir une méthode</option>
                            <option value="orange_money">Orange Money</option>
                            <option value="mtn_money">MTN Mobile Money</option>
                            <option value="moov_money">Moov Money</option>
                            <option value="wave">Wave</option>
                            <option value="virement">Virement bancaire</option>
                        </select>
                    </div>
                    <div class="mb-3" id="numero_retrait_group" style="display: none;">
                        <label class="form-label">Numéro de téléphone</label>
                        <input type="tel" class="form-control" id="numero_retrait" placeholder="Ex: 77123456">
                    </div>
                    <div class="mb-3" id="compte_bancaire_group" style="display: none;">
                        <label class="form-label">Numéro de compte bancaire</label>
                        <input type="text" class="form-control" id="compte_bancaire" placeholder="Ex: 12345678901234567890">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif du retrait</label>
                        <textarea class="form-control" id="motif_retrait" rows="2" 
                                  placeholder="Expliquez brièvement la raison de ce retrait..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Délai de traitement :</strong> Les demandes de retrait sont traitées sous 24-48h ouvrables.
                        Des frais de traitement de 2% peuvent s'appliquer.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" onclick="confirmerRetrait()">
                    <i class="fas fa-paper-plane me-2"></i>Soumettre la demande
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.wallet-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.tontine-item {
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    background: var(--gray-50);
}

.transaction-icon {
    width: 40px;
    height: 40px;
    background: var(--gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-item {
    padding: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.payment-row {
    transition: background-color 0.2s ease;
}

.payment-row:hover {
    background-color: var(--gray-50);
}
</style>

<script>
function demanderRetrait() {
    new bootstrap.Modal(document.getElementById('retirerModal')).show();
}

function rechargerPortefeuille() {
    new bootstrap.Modal(document.getElementById('rechargerModal')).show();
}

function confirmerRecharge() {
    const montant = document.getElementById('montant_recharge').value;
    const methode = document.getElementById('methode_paiement').value;
    const numero = document.getElementById('numero_telephone').value;
    
    if (!montant || !methode) {
        showToast('Veuillez remplir tous les champs requis', 'error');
        return;
    }
    
    if (parseInt(montant) < 1000) {
        showToast('Le montant minimum est de 1000 FCFA', 'error');
        return;
    }
    
    if (methode !== 'wave' && !numero) {
        showToast('Veuillez saisir votre numéro de téléphone', 'error');
        return;
    }
    
    // Envoyer la demande au serveur
    fetch('actions/process_recharge.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            montant: parseInt(montant),
            methode: methode,
            numero: numero
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('rechargerModal'));
            modal.hide();
            
            showToast(data.message, 'success');
            
            // Mettre à jour le solde en temps réel
            updateSolde();
            
            // Réinitialiser le formulaire
            document.getElementById('rechargerForm').reset();
            document.getElementById('numero_telephone_group').style.display = 'none';
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erreur lors de la recharge', 'error');
    });
}

function confirmerRetrait() {
    const montant = document.getElementById('montant_retrait').value;
    const methode = document.getElementById('methode_retrait').value;
    const motif = document.getElementById('motif_retrait').value;
    const numero = document.getElementById('numero_retrait').value;
    const compte = document.getElementById('compte_bancaire').value;
    
    if (!montant || !methode) {
        showToast('Veuillez remplir tous les champs requis', 'error');
        return;
    }
    
    if (parseInt(montant) < 1000) {
        showToast('Le montant minimum est de 1000 FCFA', 'error');
        return;
    }
    
    if (parseInt(montant) > <?= $solde_disponible ?>) {
        showToast('Montant supérieur au solde disponible', 'error');
        return;
    }
    
    if (methode !== 'virement' && !numero) {
        showToast('Veuillez saisir votre numéro de téléphone', 'error');
        return;
    } else if (methode === 'virement' && !compte) {
        showToast('Veuillez saisir votre numéro de compte bancaire', 'error');
        return;
    }
    
    // Confirmation
    if (!confirm('Êtes-vous sûr de vouloir demander ce retrait de ' + new Intl.NumberFormat('fr-FR').format(montant) + ' FCFA ?')) {
        return;
    }
    
    // Envoyer la demande au serveur
    fetch('actions/process_retrait.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            montant: parseInt(montant),
            methode: methode,
            numero: numero,
            compte_bancaire: compte,
            motif: motif
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('retirerModal'));
            modal.hide();
            
            showToast(data.message, 'success');
            
            // Mettre à jour le solde en temps réel
            updateSolde();
            
            // Réinitialiser le formulaire
            document.getElementById('retirerForm').reset();
            document.getElementById('numero_retrait_group').style.display = 'none';
            document.getElementById('compte_bancaire_group').style.display = 'none';
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erreur lors de la demande de retrait', 'error');
    });
}

// Fonction pour mettre à jour le solde en temps réel
function updateSolde() {
    console.log('Mise à jour du solde...');
    fetch('actions/get_solde.php')
    .then(response => response.json())
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            // Mettre à jour l'affichage du solde principal
            const soldeElement = document.querySelector('.solde-amount');
            if (soldeElement) {
                soldeElement.textContent = new Intl.NumberFormat('fr-FR').format(data.solde_disponible) + ' FCFA';
                console.log('Solde mis à jour:', data.solde_disponible);
            }
            
            // Mettre à jour les statistiques détaillées
            if (data.detail) {
                const rechargeElement = document.querySelector('.stat-recharge');
                const retraitElement = document.querySelector('.stat-retrait');
                
                if (rechargeElement) {
                    rechargeElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_recharge) + ' FCFA';
                }
                if (retraitElement) {
                    retraitElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_retire) + ' FCFA';
                }
            }
            
            // Forcer le rafraîchissement après 1 seconde pour s'assurer que tout est à jour
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            console.error('Erreur dans la réponse:', data.message);
            
            const bonusElement = document.querySelector('.bonus-amount');
            if (bonusElement) {
                bonusElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_bonus) + ' FCFA';
            }
            
            const retireElement = document.querySelector('.retire-amount');
            if (retireElement) {
                retireElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_retire) + ' FCFA';
            }
            
            // Mettre à jour la validation du montant de retrait
            const montantRetraitInput = document.getElementById('montant_retrait');
            if (montantRetraitInput) {
                montantRetraitInput.max = data.solde_disponible;
            }
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour du solde:', error);
    });
}

// Gestion des champs conditionnels
document.addEventListener('DOMContentLoaded', function() {
    // Pour la recharge
    document.getElementById('methode_paiement').addEventListener('change', function() {
        const numeroGroup = document.getElementById('numero_telephone_group');
        if (this.value && this.value !== 'wave') {
            numeroGroup.style.display = 'block';
            document.getElementById('numero_telephone').required = true;
        } else {
            numeroGroup.style.display = 'none';
            document.getElementById('numero_telephone').required = false;
        }
    });
    
    // Pour le retrait
    document.getElementById('methode_retrait').addEventListener('change', function() {
        const numeroGroup = document.getElementById('numero_retrait_group');
        const compteGroup = document.getElementById('compte_bancaire_group');
        
        if (this.value === 'virement') {
            numeroGroup.style.display = 'none';
            compteGroup.style.display = 'block';
            document.getElementById('numero_retrait').required = false;
            document.getElementById('compte_bancaire').required = true;
        } else if (this.value) {
            numeroGroup.style.display = 'block';
            compteGroup.style.display = 'none';
            document.getElementById('numero_retrait').required = true;
            document.getElementById('compte_bancaire').required = false;
        } else {
            numeroGroup.style.display = 'none';
            compteGroup.style.display = 'none';
            document.getElementById('numero_retrait').required = false;
            document.getElementById('compte_bancaire').required = false;
        }
    });
    
    // Filtrage des paiements par statut
    document.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('.payment-row');
            
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

// Fonctions pour les actions sur les paiements
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

function relancerPaiement(transactionId) {
    if (confirm('Êtes-vous sûr de vouloir relancer ce paiement ?')) {
        fetch('actions/relancer_paiement.php', {
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
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors de la relance du paiement', 'error');
        });
    }
}

function telechargerRecu(transactionId) {
    window.open(`actions/generer_recu.php?id=${transactionId}`, '_blank');
}

// Fonction pour afficher les toasts
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    const bgClass = type === 'success' ? 'bg-success' : 
                   type === 'error' ? 'bg-danger' : 'bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php include 'includes/footer.php'; ?>
