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
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if ($montant < 1000) {
        throw new Exception("Le montant minimum est de 1000 FCFA");
    }
    
    if (!in_array($methode, ['orange_money', 'mtn_money', 'moov_money', 'wave'])) {
        throw new Exception("Méthode de paiement invalide");
    }
    
    if ($methode !== 'wave' && empty($numero)) {
        throw new Exception("Numéro de téléphone requis");
    }
    
    // Créer la transaction de recharge
    $query = "INSERT INTO cotisations (user_id, montant, type_transaction, methode_paiement, numero_telephone, statut, date_creation) 
              VALUES (?, ?, 'recharge', ?, ?, 'pending', NOW())";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$user_id, $montant, $methode, $numero]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'enregistrement de la transaction");
    }
    
    $transaction_id = $db->lastInsertId();
    
    // Log pour débogage
    error_log("Recharge créée - User: $user_id, Montant: $montant, Transaction ID: $transaction_id");
    
    // Simuler le processus de paiement (en réalité, on intégrerait avec l'API de l'opérateur)
    // Pour la démo, on va automatiquement confirmer après quelques secondes
    
    echo json_encode([
        'success' => true, 
        'message' => 'Demande de recharge envoyée. Vous allez recevoir un SMS pour confirmer.',
        'transaction_id' => $transaction_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
