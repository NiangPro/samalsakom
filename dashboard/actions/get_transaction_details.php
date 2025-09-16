<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Transaction invalide');
    }

    $transaction_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Récupérer la transaction appartenant à l'utilisateur
    $sql = "SELECT c.*, t.nom AS tontine_nom, t.description AS tontine_description
            FROM cotisations c
            LEFT JOIN tontines t ON t.id = c.tontine_id
            WHERE c.id = ? AND c.user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$transaction_id, $user_id]);
    $tx = $stmt->fetch();

    if (!$tx) {
        throw new Exception('Transaction introuvable');
    }

    // Mapping statut lisible
    $statusText = [
        'pending' => 'En attente',
        'completed' => 'Complété',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé'
    ];

    // Mapping couleurs statut
    $statusColors = [
        'pending' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
        'cancelled' => 'secondary'
    ];

    // Mapping icônes statut
    $statusIcons = [
        'pending' => 'fa-clock',
        'completed' => 'fa-check-circle',
        'failed' => 'fa-times-circle',
        'cancelled' => 'fa-ban'
    ];

    $modeText = $tx['methode_paiement'] ? ucfirst(str_replace('_', ' ', $tx['methode_paiement'])) : '-';
    $statutLabel = $statusText[$tx['statut']] ?? $tx['statut'];
    $statusColor = $statusColors[$tx['statut']] ?? 'secondary';
    $statusIcon = $statusIcons[$tx['statut']] ?? 'fa-question';

    // Générer une référence si elle n'existe pas
    $reference = !empty($tx['reference_paiement']) 
        ? $tx['reference_paiement'] 
        : 'TXN' . str_pad($tx['id'], 6, '0', STR_PAD_LEFT) . date('ymd', strtotime($tx['date_creation']));

    ob_start();
    ?>
    <div class="container-fluid">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <h6 class="mb-0">Informations Générales</h6>
                    </div>
                    <div class="detail-content">
                        <div class="info-row">
                            <span class="info-label">Référence</span>
                            <code class="info-value"><?= htmlspecialchars($reference) ?></code>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date</span>
                            <span class="info-value"><?= $tx['date_creation'] ? date('d/m/Y à H:i', strtotime($tx['date_creation'])) : '-' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Type</span>
                            <span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $tx['type_transaction'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-credit-card text-success me-2"></i>
                        <h6 class="mb-0">Détails Paiement</h6>
                    </div>
                    <div class="detail-content">
                        <div class="info-row">
                            <span class="info-label">Montant</span>
                            <span class="info-value fw-bold text-success fs-5"><?= number_format((float)$tx['montant'], 0, ',', ' ') ?> FCFA</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Mode</span>
                            <span class="info-value"><?= htmlspecialchars($modeText) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Statut</span>
                            <span class="badge bg-<?= $statusColor ?>">
                                <i class="fas <?= $statusIcon ?> me-1"></i>
                                <?= htmlspecialchars($statutLabel) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($tx['tontine_nom']): ?>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-piggy-bank text-primary me-2"></i>
                        <h6 class="mb-0">Tontine Associée</h6>
                    </div>
                    <div class="detail-content">
                        <div class="fw-semibold text-primary fs-6"><?= htmlspecialchars($tx['tontine_nom']) ?></div>
                        <?php if ($tx['tontine_description']): ?>
                            <div class="text-muted small mt-1"><?= htmlspecialchars($tx['tontine_description']) ?></div>
                        <?php endif; ?>
                        <?php if ($tx['date_cotisation']): ?>
                            <div class="mt-2">
                                <span class="badge bg-outline-secondary">
                                    <i class="fas fa-calendar me-1"></i>
                                    Échéance: <?= date('d/m/Y', strtotime($tx['date_cotisation'])) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($tx['date_paiement'] && $tx['statut'] === 'completed'): ?>
        <div class="row g-3">
            <div class="col-12">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <h6 class="mb-0">Confirmation</h6>
                    </div>
                    <div class="detail-content">
                        <div class="info-row">
                            <span class="info-label">Date de paiement</span>
                            <span class="info-value"><?= date('d/m/Y à H:i', strtotime($tx['date_paiement'])) ?></span>
                        </div>
                        <?php if ($tx['reference_paiement']): ?>
                        <div class="info-row">
                            <span class="info-label">Référence de paiement</span>
                            <code class="info-value"><?= htmlspecialchars($tx['reference_paiement']) ?></code>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .detail-card {
        background: var(--gray-50, #f8f9fa);
        border: 1px solid var(--gray-200, #dee2e6);
        border-radius: 8px;
        padding: 1.25rem;
        height: 100%;
    }
    
    .detail-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-200, #dee2e6);
    }
    
    .detail-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.25rem 0;
    }
    
    .info-label {
        font-weight: 500;
        color: var(--gray-600, #6c757d);
        font-size: 0.875rem;
    }
    
    .info-value {
        font-weight: 600;
        color: var(--dark-color, #212529);
    }
    
    .bg-outline-secondary {
        background-color: transparent;
        border: 1px solid var(--gray-300, #ced4da);
        color: var(--gray-700, #495057);
    }
    </style>
    <?php
    $html = ob_get_clean();

    echo json_encode(['success' => true, 'html' => $html]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Erreur PDO get_transaction_details: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>
