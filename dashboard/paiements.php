<?php
$page_title = "Paiements";
$breadcrumb = "Paiements";
include 'includes/header.php';

// Récupération des cotisations en attente avec calcul des retards
try {
    $query = "SELECT c.*, t.nom as tontine_nom, t.frequence, t.montant_cotisation,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN DATEDIFF(CURDATE(), c.date_cotisation)
                ELSE 0
              END as jours_retard,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN 'en_retard'
                ELSE 'en_attente'
              END as statut_echeance
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.user_id = ? AND c.statut = 'pending' AND c.type_transaction = 'cotisation'
              ORDER BY c.date_cotisation ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $cotisations_pending = $stmt->fetchAll();
    
    // Séparer les cotisations en retard et en attente
    $cotisations_en_retard = array_filter($cotisations_pending, function($c) {
        return $c['statut_echeance'] === 'en_retard';
    });
    
    $cotisations_en_attente = array_filter($cotisations_pending, function($c) {
        return $c['statut_echeance'] === 'en_attente';
    });
    
    // Historique des paiements
    $query_historique = "SELECT c.*, t.nom as tontine_nom 
                        FROM cotisations c 
                        LEFT JOIN tontines t ON c.tontine_id = t.id 
                        WHERE c.user_id = ? AND c.statut IN ('completed', 'failed')
                        ORDER BY c.date_paiement DESC 
                        LIMIT 10";
    $stmt = $db->prepare($query_historique);
    $stmt->execute([$_SESSION['user_id']]);
    $historique_paiements = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des paiements.";
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Paiements</h1>
    <p class="page-subtitle">Gérez vos cotisations et paiements</p>
</div>

<!-- Alerte pour les retards de paiement -->
<?php if (!empty($cotisations_en_retard)): ?>
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">⚠️ Paiements en Retard</h5>
                <p class="mb-0">Vous avez <strong><?php echo count($cotisations_en_retard); ?> paiement(s) en retard</strong>. Veuillez régulariser votre situation rapidement pour éviter des pénalités.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cotisations en retard -->
<?php if (!empty($cotisations_en_retard)): ?>
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="dashboard-card danger">
            <div class="card-header-modern">
                <h3 class="card-title text-danger">
                    <i class="fas fa-clock me-2"></i>Paiements en Retard
                </h3>
                <span class="badge bg-danger"><?php echo count($cotisations_en_retard); ?> en retard</span>
            </div>
            <div class="card-body-modern">
                <div class="table-modern">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tontine</th>
                                <th>Montant</th>
                                <th>Échéance</th>
                                <th>Retard</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cotisations_en_retard as $cotisation): ?>
                            <tr class="table-danger">
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($cotisation['tontine_nom']); ?></div>
                                    <small class="text-muted"><?php echo ucfirst($cotisation['frequence']); ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-danger">
                                        <?php echo number_format($cotisation['montant'], 0, ',', ' '); ?> FCFA
                                    </span>
                                </td>
                                <td>
                                    <div class="text-danger">
                                        <?php echo date('d/m/Y', strtotime($cotisation['date_cotisation'])); ?>
                                        <br><small><strong>Échéance dépassée</strong></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        <?php echo $cotisation['jours_retard']; ?> jour(s)
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="payerCotisation(<?php echo $cotisation['id']; ?>)">
                                        <i class="fas fa-credit-card"></i> Payer Maintenant
                                    </button>
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
<?php endif; ?>

<!-- Cotisations en attente -->
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Cotisations à Payer</h3>
                <div class="d-flex gap-2">
                    <?php if (!empty($cotisations_en_retard)): ?>
                        <span class="badge bg-danger"><?php echo count($cotisations_en_retard); ?> en retard</span>
                    <?php endif; ?>
                    <span class="badge bg-warning"><?php echo count($cotisations_en_attente); ?> en attente</span>
                </div>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($cotisations_en_attente)): ?>
                    <div class="table-modern">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tontine</th>
                                    <th>Montant</th>
                                    <th>Échéance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cotisations_en_attente as $cotisation): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($cotisation['tontine_nom']); ?></div>
                                        <small class="text-muted"><?php echo ucfirst($cotisation['frequence']); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            <?php echo number_format($cotisation['montant'], 0, ',', ' '); ?> FCFA
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $date_echeance = new DateTime($cotisation['date_cotisation']);
                                        $now = new DateTime();
                                        $diff = $now->diff($date_echeance);
                                        ?>
                                        <div class="text-warning">
                                            <?php echo $date_echeance->format('d/m/Y'); ?>
                                            <br><small>Dans <?php echo $diff->days; ?> jour(s)</small>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-success-modern btn-sm" 
                                                onclick="payerCotisation(<?php echo $cotisation['id']; ?>)">
                                            <i class="fas fa-credit-card"></i> Payer
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Aucune cotisation en attente</h5>
                        <p class="text-muted">Toutes vos cotisations sont à jour !</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Historique des paiements -->
<div class="row g-4" data-aos="fade-up" data-aos-delay="200">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Historique des Paiements</h3>
                <a href="historique.php" class="btn btn-outline-modern btn-sm">Voir tout</a>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($historique_paiements)): ?>
                    <div class="table-modern">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Tontine</th>
                                    <th>Montant</th>
                                    <th>Mode</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique_paiements as $paiement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paiement['date_paiement'])); ?></td>
                                    <td>
                                        <?php if ($paiement['tontine_nom']): ?>
                                            <?php echo htmlspecialchars($paiement['tontine_nom']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <?php echo ucfirst($paiement['type_transaction']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($paiement['montant'], 0, ',', ' '); ?> FCFA</td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php 
                                            $mode = $paiement['methode_paiement'] ?? $paiement['mode_paiement'] ?? 'N/A';
                                            echo ucfirst(str_replace('_', ' ', $mode)); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $paiement['statut'] == 'completed' ? 'active' : 'inactive'; ?>">
                                            <?php echo $paiement['statut'] == 'completed' ? 'Réussi' : 'Échoué'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($paiement['statut'] == 'completed'): ?>
                                            <a href="actions/generate_invoice.php?id=<?php echo $paiement['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               target="_blank"
                                               title="Télécharger la facture">
                                                <i class="fas fa-download"></i> Facture
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5>Aucun historique</h5>
                        <p class="text-muted">Vos paiements apparaîtront ici</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Effectuer un Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paiementContent">
                <!-- Contenu chargé via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function payerCotisation(cotisationId) {
    fetch(`actions/get_paiement_form.php?id=${cotisationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('paiementContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('paiementModal')).show();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur lors du chargement du formulaire', 'error');
        });
}

function processPaiement(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Désactiver le bouton et afficher le loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
    
    fetch('actions/process_paiement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('paiementModal')).hide();
            // Recharger la page pour mettre à jour la liste
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erreur lors du traitement du paiement', 'error');
    })
    .finally(() => {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
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
    
    // Supprimer le toast du DOM après qu'il soit caché
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
