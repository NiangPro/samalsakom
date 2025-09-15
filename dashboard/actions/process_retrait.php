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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $montant = (int)$input['montant'];
    $methode = $input['methode'];
    $numero = $input['numero'] ?? '';
    $compte_bancaire = $input['compte_bancaire'] ?? '';
    $motif = $input['motif'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if ($montant < 1000) {
        throw new Exception("Le montant minimum est de 1000 FCFA");
    }
    
    if (!in_array($methode, ['orange_money', 'mtn_money', 'moov_money', 'wave', 'virement'])) {
        throw new Exception("Méthode de retrait invalide");
    }
    
    // Vérifier le solde disponible
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction IN ('cotisation', 'recharge', 'bonus') THEN montant ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as solde_disponible
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($solde_query);
    $stmt->execute([$user_id]);
    $solde_disponible = $stmt->fetch()['solde_disponible'];
    
    if ($montant > $solde_disponible) {
        throw new Exception("Solde insuffisant. Solde disponible: " . number_format($solde_disponible, 0, ',', ' ') . " FCFA");
    }
    
    if ($methode === 'virement' && empty($compte_bancaire)) {
        throw new Exception("Numéro de compte bancaire requis");
    } elseif ($methode !== 'virement' && empty($numero)) {
        throw new Exception("Numéro de téléphone requis");
    }
    
    // Créer la demande de retrait
    $query = "INSERT INTO cotisations (user_id, montant, type_transaction, methode_paiement, numero_telephone, compte_bancaire, motif, statut, date_creation) 
              VALUES (?, ?, 'retrait', ?, ?, ?, ?, 'completed', NOW())";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$user_id, $montant, $methode, $numero, $compte_bancaire, $motif]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'enregistrement de la demande de retrait");
    }
    
    $transaction_id = $db->lastInsertId();
    
    // Log pour débogage
    error_log("Retrait traité - User: $user_id, Montant: $montant, Transaction ID: $transaction_id");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Demande de retrait soumise avec succès. Vous recevrez une notification une fois traitée.',
        'transaction_id' => $transaction_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
