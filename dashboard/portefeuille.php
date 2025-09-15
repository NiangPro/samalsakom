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
    
<<<<<<< HEAD
    // Calculer le solde total
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction IN ('cotisation', 'recharge', 'bonus') THEN montant ELSE 0 END), 0) as total_entrees,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire
=======
    // Calculer le solde total (robuste face aux enums manquants)
    // total_cotise: cotisations terminées liées aux tontines
    // total_recharge: recharges complétées (type='bonus' OU référence commençant par RC)
    // total_retire: retraits complétés (type='retrait' OU référence commençant par RT)
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND (type_transaction = 'bonus' OR (reference_paiement IS NOT NULL AND reference_paiement LIKE 'RC%')) THEN montant ELSE 0 END), 0) as total_recharge,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND (type_transaction = 'retrait' OR (reference_paiement IS NOT NULL AND reference_paiement LIKE 'RT%')) THEN montant ELSE 0 END), 0) as total_retire
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($solde_query);
    $stmt->execute([$user_id]);
    $solde_data = $stmt->fetch();
    
<<<<<<< HEAD
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
=======
    $solde_disponible = $solde_data['total_cotise'] + $solde_data['total_recharge'] - $solde_data['total_retire'];
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
    
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
<<<<<<< HEAD
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
=======
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
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
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

<<<<<<< HEAD
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

=======
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
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

<<<<<<< HEAD
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
            
            // Simuler la confirmation après 5 secondes
            setTimeout(() => {
                fetch('actions/confirm_recharge.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        transaction_id: data.transaction_id
                    })
                })
                .then(response => response.json())
                .then(confirmData => {
                    if (confirmData.success) {
                        showToast(confirmData.message, 'success');
                        // Mettre à jour le solde en temps réel
                        updateSolde();
                    }
                });
            }, 5000);
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
    fetch('actions/get_solde.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le solde principal
            const soldeElement = document.querySelector('.solde-amount');
            if (soldeElement) {
                soldeElement.textContent = new Intl.NumberFormat('fr-FR').format(data.solde_disponible) + ' FCFA';
            }
            
            // Mettre à jour les détails du solde
            const cotiseElement = document.querySelector('.cotise-amount');
            if (cotiseElement) {
                cotiseElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_cotise) + ' FCFA';
            }
            
            const rechargeElement = document.querySelector('.recharge-amount');
            if (rechargeElement) {
                rechargeElement.textContent = new Intl.NumberFormat('fr-FR').format(data.detail.total_recharge) + ' FCFA';
            }
            
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
});
=======
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
>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
</script>

<?php include 'includes/footer.php'; ?>
