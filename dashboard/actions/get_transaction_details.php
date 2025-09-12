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


