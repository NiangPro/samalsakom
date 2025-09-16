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
    
    $cotisation_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les détails de la cotisation
    $query = "SELECT c.*, t.nom as tontine_nom, t.frequence 
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.id = ? AND c.user_id = ? AND c.statut = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$cotisation_id, $user_id]);
    $cotisation = $stmt->fetch();
    
    if (!$cotisation) {
        throw new Exception('Cotisation non trouvée');
    }
    
    $html = '
    <form id="paiementForm" onsubmit="processPaiement(event)">
        <input type="hidden" name="cotisation_id" value="' . $cotisation['id'] . '">
        
        <div class="mb-4">
            <h6 class="fw-bold">Détails du Paiement</h6>
            <div class="bg-light p-3 rounded">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Type</small>
                        <div class="fw-semibold">' . 
                        ($cotisation['tontine_nom'] ? htmlspecialchars($cotisation['tontine_nom']) : ucfirst($cotisation['type_transaction'])) 
                        . '</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Montant</small>
                        <div class="fw-bold text-success">' . number_format($cotisation['montant'], 0, ',', ' ') . ' FCFA</div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <small class="text-muted">Échéance</small>
                        <div>' . date('d/m/Y', strtotime($cotisation['date_cotisation'])) . '</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Fréquence</small>
                        <div>' . ($cotisation['frequence'] ? ucfirst($cotisation['frequence']) : 'Unique') . '</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-semibold">Mode de Paiement</label>
            <div class="payment-methods">
                <div class="form-check payment-option">
                    <input class="form-check-input" type="radio" name="methode_paiement" value="orange_money" id="orange_money" checked>
                    <label class="form-check-label" for="orange_money">
                        <div class="payment-method-card">
                            <div class="payment-icon bg-warning">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Orange Money</div>
                                <small class="text-muted">Paiement mobile sécurisé</small>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="form-check payment-option">
                    <input class="form-check-input" type="radio" name="methode_paiement" value="wave" id="wave">
                    <label class="form-check-label" for="wave">
                        <div class="payment-method-card">
                            <div class="payment-icon bg-primary">
                                <i class="fas fa-wave-square"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Wave</div>
                                <small class="text-muted">Transfert d\'argent rapide</small>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="form-check payment-option">
                    <input class="form-check-input" type="radio" name="methode_paiement" value="virement" id="virement">
                    <label class="form-check-label" for="virement">
                        <div class="payment-method-card">
                            <div class="payment-icon bg-success">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">Virement Bancaire</div>
                                <small class="text-muted">Transfert bancaire</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="mb-4" id="phoneSection">
            <label for="telephone" class="form-label">Numéro de téléphone</label>
            <div class="input-group">
                <span class="input-group-text">+221</span>
                <input type="tel" class="form-control" id="telephone" name="telephone" 
                       placeholder="77 123 45 67" required>
            </div>
            <small class="text-muted">Numéro associé à votre compte mobile money</small>
        </div>
        
        <div class="mb-4" id="bankSection" style="display: none;">
            <label for="reference_bancaire" class="form-label">Référence bancaire</label>
            <input type="text" class="form-control" id="reference_bancaire" name="reference_bancaire" 
                   placeholder="Numéro de compte ou RIB">
            <small class="text-muted">Informations pour le virement</small>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Information :</strong> Votre paiement sera traité dans les plus brefs délais. 
            Vous recevrez une confirmation par SMS.
        </div>
        
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success-modern flex-fill">
                <i class="fas fa-credit-card me-2"></i>
                Payer ' . number_format($cotisation['montant'], 0, ',', ' ') . ' FCFA
            </button>
        </div>
    </form>
    
    <style>
    .payment-methods {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .payment-option {
        margin: 0;
    }
    
    .payment-method-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 2px solid var(--gray-200);
        border-radius: var(--border-radius);
        transition: var(--transition-fast);
        cursor: pointer;
        width: 100%;
    }
    
    .payment-option input:checked + label .payment-method-card {
        border-color: var(--primary-color);
        background: rgba(46, 139, 87, 0.05);
    }
    
    .payment-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    </style>
    
    <script>
    // Gestion des modes de paiement
    document.querySelectorAll(\'input[name="methode_paiement"]\').forEach(radio => {
        radio.addEventListener(\'change\', function() {
            const phoneSection = document.getElementById(\'phoneSection\');
            const bankSection = document.getElementById(\'bankSection\');
            const phoneInput = document.getElementById(\'telephone\');
            const bankInput = document.getElementById(\'reference_bancaire\');
            
            if (this.value === \'virement\') {
                phoneSection.style.display = \'none\';
                bankSection.style.display = \'block\';
                phoneInput.required = false;
                bankInput.required = true;
            } else {
                phoneSection.style.display = \'block\';
                bankSection.style.display = \'none\';
                phoneInput.required = true;
                bankInput.required = false;
            }
        });
    });
    
    // Formatage du numéro de téléphone
    document.getElementById(\'telephone\').addEventListener(\'input\', function() {
        let value = this.value.replace(/\D/g, \'\');
        if (value.length >= 2) {
            value = value.substring(0, 2) + \' \' + value.substring(2, 5) + \' \' + value.substring(5, 7) + \' \' + value.substring(7, 9);
        }
        this.value = value.trim();
    });
    </script>';
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
