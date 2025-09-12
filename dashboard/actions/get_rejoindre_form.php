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
        throw new Exception('Tontine invalide');
    }

    $tontine_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Récupérer la tontine
    $query = "SELECT t.*, u.prenom, u.nom,
                     (SELECT COUNT(*) FROM participations p WHERE p.tontine_id = t.id AND p.statut != 'retire') as participants_actuels
              FROM tontines t
              LEFT JOIN users u ON u.id = t.createur_id
              WHERE t.id = ? AND t.statut = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id]);
    $tontine = $stmt->fetch();

    if (!$tontine) {
        throw new Exception('Tontine non trouvée ou inactive');
    }

    // Déjà participant ?
    $stmt = $db->prepare("SELECT 1 FROM participations WHERE tontine_id = ? AND user_id = ? AND statut != 'retire' LIMIT 1");
    $stmt->execute([$tontine_id, $user_id]);
    $deja_participant = (bool)$stmt->fetchColumn();

    $places_restantes = max(0, (int)$tontine['nombre_participants'] - (int)$tontine['participants_actuels']);

    if ($deja_participant) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous participez déjà à cette tontine.'
        ]);
        exit;
    }

    if ($places_restantes <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cette tontine est complète.'
        ]);
        exit;
    }

    // Construire le HTML du formulaire de confirmation
    ob_start();
    ?>
    <div class="mb-3">
        <div class="d-flex align-items-center mb-2">
            <div class="creator-avatar me-2" style="width:36px;height:36px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                <?php echo strtoupper(substr($tontine['prenom'], 0, 1) . substr($tontine['nom'], 0, 1)); ?>
            </div>
            <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($tontine['prenom'] . ' ' . $tontine['nom']); ?></div>
                <small class="text-muted">Organisateur</small>
            </div>
        </div>
        <div class="border rounded p-3">
            <div class="d-flex justify-content-between">
                <span>Montant par cotisation</span>
                <strong><?php echo number_format($tontine['montant_cotisation'], 0, ',', ' '); ?> FCFA</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Fréquence</span>
                <strong><?php echo ucfirst($tontine['frequence']); ?></strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Places restantes</span>
                <strong><?php echo (int)$places_restantes; ?></strong>
            </div>
            <?php if (!empty($tontine['date_debut'])): ?>
            <div class="d-flex justify-content-between">
                <span>Début</span>
                <strong><?php echo date('d/m/Y', strtotime($tontine['date_debut'])); ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="alert alert-info d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        En rejoignant, vos échéances seront générées automatiquement selon la fréquence.
    </div>
    <div class="d-flex gap-2 justify-content-end">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-primary" onclick="confirmerParticipation(<?php echo (int)$tontine_id; ?>)">
            <i class="fas fa-check me-1"></i> Confirmer
        </button>
    </div>
    <?php
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log('Erreur PDO get_rejoindre_form: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
}
?>


