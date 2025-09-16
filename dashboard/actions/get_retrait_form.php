<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Calculer solde disponible (même logique que portefeuille.php)
    $q = "SELECT 
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire,
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'bonus' THEN montant ELSE 0 END), 0) as total_bonus
          FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($q);
    $stmt->execute([$_SESSION['user_id']]);
    $s = $stmt->fetch();
    $solde = (int)$s['total_cotise'] + (int)$s['total_bonus'] - (int)$s['total_retire'];

    ob_start();
    ?>
    <div class="mb-2">Solde disponible: <strong><?= number_format($solde, 0, ',', ' ') ?> FCFA</strong></div>
    <form id="retraitForm">
        <div class="mb-3">
            <label class="form-label">Montant à retirer (FCFA)</label>
            <input type="number" class="form-control" name="montant" min="1000" step="500" max="<?= (int)$solde ?>" required placeholder="Ex: 5000">
        </div>
        <div class="mb-3">
            <label class="form-label">Moyen de réception</label>
            <select class="form-select" name="methode_paiement" required>
                <option value="orange_money">Orange Money</option>
                <option value="wave">Wave</option>
                <option value="virement">Virement</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Coordonnées (numéro compte/téléphone)</label>
            <input type="text" class="form-control" name="coordonnees" required placeholder="Ex: 77xxxxxxx">
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="confirmerRetrait()">
                <i class="fas fa-check me-1"></i>Demander
            </button>
        </div>
    </form>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html, 'solde' => $solde]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de chargement du formulaire']);
}
?>


