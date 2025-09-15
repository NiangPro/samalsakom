<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
<<<<<<< HEAD
    $database = new Database();
    $db = $database->getConnection();
    
    $transaction_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les détails de la transaction
    $query = "SELECT c.*, t.nom as tontine_nom, t.description as tontine_description,
                     u.prenom as user_prenom, u.nom as user_nom
              FROM cotisations c
              LEFT JOIN tontines t ON c.tontine_id = t.id
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.id = ? AND c.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        throw new Exception("Transaction non trouvée");
    }
    
    // Générer une référence si elle n'existe pas
    $reference = !empty($transaction['reference_paiement']) 
        ? $transaction['reference_paiement'] 
        : 'TXN' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT) . date('ymd', strtotime($transaction['date_creation']));
    
    // Fonction pour obtenir l'icône de paiement
    function getPaymentIcon($methode) {
        switch ($methode) {
            case 'orange_money': return 'fa-mobile-alt text-warning';
            case 'mtn_money': return 'fa-mobile-alt text-primary';
            case 'moov_money': return 'fa-mobile-alt text-info';
            case 'wave': return 'fa-wave-square text-success';
            case 'virement': return 'fa-university text-secondary';
            default: return 'fa-credit-card text-muted';
        }
    }
    
    // Fonction pour obtenir le nom de la méthode
    function getPaymentName($methode) {
        switch ($methode) {
            case 'orange_money': return 'Orange Money';
            case 'mtn_money': return 'MTN Mobile Money';
            case 'moov_money': return 'Moov Money';
            case 'wave': return 'Wave';
            case 'virement': return 'Virement bancaire';
            default: return ucfirst(str_replace('_', ' ', $methode));
        }
    }
    
    // Statuts et couleurs
    $status_info = [
        'pending' => ['class' => 'warning', 'text' => 'En attente', 'icon' => 'fa-clock'],
        'completed' => ['class' => 'success', 'text' => 'Complété', 'icon' => 'fa-check-circle'],
        'failed' => ['class' => 'danger', 'text' => 'Échoué', 'icon' => 'fa-times-circle'],
        'cancelled' => ['class' => 'secondary', 'text' => 'Annulé', 'icon' => 'fa-ban']
    ];
    
    $status = $status_info[$transaction['statut']] ?? ['class' => 'secondary', 'text' => $transaction['statut'], 'icon' => 'fa-question'];
    
    // Types de transaction
    $type_info = [
        'cotisation' => ['class' => 'primary', 'text' => 'Cotisation', 'icon' => 'fa-piggy-bank'],
        'recharge' => ['class' => 'success', 'text' => 'Recharge', 'icon' => 'fa-plus-circle'],
        'retrait' => ['class' => 'warning', 'text' => 'Retrait', 'icon' => 'fa-minus-circle'],
        'bonus' => ['class' => 'info', 'text' => 'Bonus', 'icon' => 'fa-gift'],
        'remboursement' => ['class' => 'secondary', 'text' => 'Remboursement', 'icon' => 'fa-undo'],
        'penalite' => ['class' => 'danger', 'text' => 'Pénalité', 'icon' => 'fa-exclamation-triangle']
    ];
    
    $type = $type_info[$transaction['type_transaction']] ?? ['class' => 'secondary', 'text' => $transaction['type_transaction'], 'icon' => 'fa-exchange-alt'];
    
    // Générer le HTML
    $html = '
    <div class="transaction-details">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas ' . $type['icon'] . ' text-' . $type['class'] . ' me-2"></i>
                        <h6 class="mb-0">Type de Transaction</h6>
                    </div>
                    <div class="detail-content">
                        <span class="badge bg-' . $type['class'] . ' fs-6">' . $type['text'] . '</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas ' . $status['icon'] . ' text-' . $status['class'] . ' me-2"></i>
                        <h6 class="mb-0">Statut</h6>
                    </div>
                    <div class="detail-content">
                        <span class="badge bg-' . $status['class'] . ' fs-6">' . $status['text'] . '</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-coins text-success me-2"></i>
                        <h6 class="mb-0">Montant</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-4 fw-bold text-success">' . number_format($transaction['montant'], 0, ',', ' ') . ' FCFA</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-hashtag text-info me-2"></i>
                        <h6 class="mb-0">Référence</h6>
                    </div>
                    <div class="detail-content">
                        <code class="fs-6">' . htmlspecialchars($reference) . '</code>
                    </div>
                </div>
            </div>
        </div>';
    
    if (!empty($transaction['methode_paiement'])) {
        $html .= '
        <div class="row mb-4">
            <div class="col-12">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas ' . getPaymentIcon($transaction['methode_paiement']) . ' me-2"></i>
                        <h6 class="mb-0">Méthode de Paiement</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-6">' . getPaymentName($transaction['methode_paiement']) . '</span>';
        
        if (!empty($transaction['numero_telephone'])) {
            $html .= '<br><small class="text-muted">Numéro: ' . htmlspecialchars($transaction['numero_telephone']) . '</small>';
        }
        
        if (!empty($transaction['compte_bancaire'])) {
            $html .= '<br><small class="text-muted">Compte: ' . htmlspecialchars($transaction['compte_bancaire']) . '</small>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>
        </div>';
    }
    
    if (!empty($transaction['tontine_nom'])) {
        $html .= '
        <div class="row mb-4">
            <div class="col-12">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-piggy-bank text-primary me-2"></i>
                        <h6 class="mb-0">Tontine</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-6 fw-semibold">' . htmlspecialchars($transaction['tontine_nom']) . '</span>';
        
        if (!empty($transaction['tontine_description'])) {
            $html .= '<br><small class="text-muted">' . htmlspecialchars($transaction['tontine_description']) . '</small>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>
        </div>';
    }
    
    if (!empty($transaction['motif'])) {
        $html .= '
        <div class="row mb-4">
            <div class="col-12">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-comment text-info me-2"></i>
                        <h6 class="mb-0">Motif</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-6">' . htmlspecialchars($transaction['motif']) . '</span>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    $html .= '
        <div class="row">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-calendar text-secondary me-2"></i>
                        <h6 class="mb-0">Date de Création</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-6">' . date('d/m/Y à H:i', strtotime($transaction['date_creation'])) . '</span>
                    </div>
                </div>
            </div>';
    
    if (!empty($transaction['date_confirmation'])) {
        $html .= '
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-check text-success me-2"></i>
                        <h6 class="mb-0">Date de Confirmation</h6>
                    </div>
                    <div class="detail-content">
                        <span class="fs-6">' . date('d/m/Y à H:i', strtotime($transaction['date_confirmation'])) . '</span>
                    </div>
                </div>
            </div>';
    }
    
    $html .= '
        </div>
    </div>
    
    <style>
    .detail-card {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        padding: 1rem;
        height: 100%;
    }
    
    .detail-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .detail-content {
        padding-left: 1.5rem;
    }
    </style>';
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
=======
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

    $modeText = $tx['mode_paiement'] ? ucfirst(str_replace('_', ' ', $tx['mode_paiement'])) : '-';
    $statutLabel = $statusText[$tx['statut']] ?? $tx['statut'];

    ob_start();
    ?>
    <div class="container-fluid">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="fw-bold mb-3">Informations</h6>
                    <div class="d-flex justify-content-between"><span>Référence</span><strong><?= htmlspecialchars($tx['reference_paiement'] ?? '-') ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Date</span><strong><?= $tx['date_creation'] ? date('d/m/Y H:i', strtotime($tx['date_creation'])) : '-' ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Statut</span><strong><?= htmlspecialchars($statutLabel) ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Type</span><strong><?= ucfirst($tx['type_transaction']) ?></strong></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="fw-bold mb-3">Paiement</h6>
                    <div class="d-flex justify-content-between"><span>Montant</span><strong><?= number_format((float)$tx['montant'], 0, ',', ' ') ?> FCFA</strong></div>
                    <div class="d-flex justify-content-between"><span>Mode</span><strong><?= htmlspecialchars($modeText) ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Date prévue</span><strong><?= $tx['date_cotisation'] ? date('d/m/Y', strtotime($tx['date_cotisation'])) : '-' ?></strong></div>
                </div>
            </div>
            <div class="col-12">
                <div class="border rounded p-3">
                    <h6 class="fw-bold mb-2">Tontine</h6>
                    <div class="fw-semibold text-primary"><?= htmlspecialchars($tx['tontine_nom'] ?? 'N/A') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($tx['tontine_description'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>
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


>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
