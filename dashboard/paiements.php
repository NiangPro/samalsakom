<?php
$page_title = "Paiements";
$breadcrumb = "Paiements";
include 'includes/header.php';

// Récupération des cotisations en attente
try {
    $query = "SELECT c.*, t.nom as tontine_nom, t.frequence, t.montant_cotisation
              FROM cotisations c 
              JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.user_id = ? AND c.statut = 'pending'
              ORDER BY c.date_cotisation ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $cotisations_pending = $stmt->fetchAll();
    
    // Historique des paiements
    $query_historique = "SELECT c.*, t.nom as tontine_nom 
                        FROM cotisations c 
                        JOIN tontines t ON c.tontine_id = t.id 
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

<!-- Cotisations en attente -->
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h3 class="card-title">Cotisations à Payer</h3>
                <span class="badge bg-warning"><?php echo count($cotisations_pending); ?> en attente</span>
            </div>
            <div class="card-body-modern">
                <?php if (!empty($cotisations_pending)): ?>
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
                                <?php foreach ($cotisations_pending as $cotisation): ?>
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
                                        $is_overdue = $date_echeance < $now;
                                        ?>
                                        <div class="<?php echo $is_overdue ? 'text-danger' : 'text-warning'; ?>">
                                            <?php echo $date_echeance->format('d/m/Y'); ?>
                                            <?php if ($is_overdue): ?>
                                                <br><small>En retard de <?php echo $diff->days; ?> jour(s)</small>
                                            <?php else: ?>
                                                <br><small>Dans <?php echo $diff->days; ?> jour(s)</small>
                                            <?php endif; ?>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique_paiements as $paiement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($paiement['date_paiement'])); ?></td>
                                    <td><?php echo htmlspecialchars($paiement['tontine_nom']); ?></td>
                                    <td><?php echo number_format($paiement['montant'], 0, ',', ' '); ?> FCFA</td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst(str_replace('_', ' ', $paiement['mode_paiement'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $paiement['statut'] == 'completed' ? 'active' : 'inactive'; ?>">
                                            <?php echo $paiement['statut'] == 'completed' ? 'Réussi' : 'Échoué'; ?>
                                        </span>
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
</script>

<?php include 'includes/footer.php'; ?>
