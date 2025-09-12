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
    
    // Calculer le solde total (robuste face aux enums manquants)
    // total_cotise: cotisations terminées liées aux tontines
    // total_recharge: recharges complétées (type='bonus' OU référence commençant par RC)
    // total_retire: retraits complétés (type='retrait' OU référence commençant par RT)
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND (type_transaction = 'bonus' OR (reference_paiement IS NOT NULL AND reference_paiement LIKE 'RC%')) THEN montant ELSE 0 END), 0) as total_recharge,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND (type_transaction = 'retrait' OR (reference_paiement IS NOT NULL AND reference_paiement LIKE 'RT%')) THEN montant ELSE 0 END), 0) as total_retire
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($solde_query);
    $stmt->execute([$user_id]);
    $solde_data = $stmt->fetch();
    
    $solde_disponible = $solde_data['total_cotise'] + $solde_data['total_recharge'] - $solde_data['total_retire'];
    
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
    
    // Transactions récentes
    $transactions_query = "SELECT c.*, t.nom as tontine_nom 
        FROM cotisations c 
        LEFT JOIN tontines t ON c.tontine_id = t.id 
        WHERE c.user_id = ? 
        ORDER BY c.date_creation DESC 
        LIMIT 5";
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
                                <h1 class="text-white fw-bold mb-3"><?= number_format($solde_disponible, 0, ',', ' ') ?> FCFA</h1>
                                <div class="row text-white-50">
                                    <div class="col-4">
                                        <small>Cotisé</small>
                                        <div class="fw-semibold"><?= number_format($solde_data['total_cotise'], 0, ',', ' ') ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small>Rechargé</small>
                                        <div class="fw-semibold"><?= number_format($solde_data['total_recharge'], 0, ',', ' ') ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small>Retiré</small>
                                        <div class="fw-semibold"><?= number_format($solde_data['total_retire'], 0, ',', ' ') ?></div>
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

            <!-- Transactions récentes -->
            <div class="col-lg-4">
                <div class="card modern-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Transactions Récentes</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($transactions_recentes)): ?>
                            <?php foreach ($transactions_recentes as $transaction): ?>
                                <div class="transaction-item d-flex align-items-center mb-3">
                                    <div class="transaction-icon me-3">
                                        <i class="fas <?= $transaction['type_transaction'] === 'cotisation' ? 'fa-arrow-down text-danger' : 'fa-arrow-up text-success' ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?= ucfirst($transaction['type_transaction']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($transaction['tontine_nom'] ?? 'N/A') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold"><?= number_format($transaction['montant'], 0, ',', ' ') ?></div>
                                        <small class="text-muted"><?= date('d/m', strtotime($transaction['date_creation'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="historique.php" class="btn btn-outline-primary btn-sm">Voir tout</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Aucune transaction</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
</style>

<!-- Modales Recharge / Retrait -->
<div class="modal fade" id="walletModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="walletModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="walletModalBody"></div>
        </div>
    </div>
</div>

<script>
function demanderRetrait() {
    fetch('actions/get_retrait_form.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('walletModalTitle').textContent = 'Demande de Retrait';
                document.getElementById('walletModalBody').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('walletModal')).show();
            } else {
                showToast(data.message || 'Erreur de chargement', 'danger');
            }
        })
        .catch(() => showToast('Erreur de connexion', 'danger'));
}

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
            bootstrap.Modal.getInstance(document.getElementById('walletModal')).hide();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Erreur lors de la recharge', 'danger');
        }
    })
    .catch(() => showToast('Erreur de connexion', 'danger'));
}

function confirmerRetrait() {
    const form = document.getElementById('retraitForm');
    if (!form || !form.reportValidity()) return;
    const formData = new FormData(form);
    fetch('actions/process_retrait.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            montant: parseInt(formData.get('montant'), 10),
            mode_paiement: formData.get('mode_paiement'),
            coordonnees: formData.get('coordonnees')
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast(d.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('walletModal')).hide();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Erreur lors du retrait', 'danger');
        }
    })
    .catch(() => showToast('Erreur de connexion', 'danger'));
}
</script>

<?php include 'includes/footer.php'; ?>
