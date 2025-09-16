<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    ob_start();
    ?>
    <form id="rechargeForm">
        <div class="mb-3">
            <label class="form-label">Montant à recharger (FCFA)</label>
            <input type="number" class="form-control" name="montant" min="1000" step="500" required placeholder="Ex: 5000">
        </div>
        <div class="mb-3">
            <label class="form-label">Mode de paiement</label>
            <select class="form-select" name="methode_paiement" required>
                <option value="orange_money">Orange Money</option>
                <option value="wave">Wave</option>
                <option value="virement">Virement</option>
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="confirmerRecharge()">
                <i class="fas fa-check me-1"></i>Confirmer
            </button>
        </div>
    </form>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de chargement du formulaire']);
}
?>


