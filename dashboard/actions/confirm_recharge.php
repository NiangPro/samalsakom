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
    $transaction_id = (int)$input['transaction_id'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la transaction appartient à l'utilisateur et est en attente
    $query = "SELECT * FROM cotisations WHERE id = ? AND user_id = ? AND statut = 'pending' AND type_transaction = 'recharge'";
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        throw new Exception("Transaction non trouvée ou déjà traitée");
    }
    
    // Confirmer la transaction (simuler la confirmation de paiement)
    $update_query = "UPDATE cotisations SET statut = 'completed', date_confirmation = NOW() WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $result = $stmt->execute([$transaction_id]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la confirmation de la transaction");
    }
    
    // Vérifier que la mise à jour a bien eu lieu
    $check_query = "SELECT statut FROM cotisations WHERE id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$transaction_id]);
    $updated_transaction = $stmt->fetch();
    
    if ($updated_transaction['statut'] !== 'completed') {
        throw new Exception("La transaction n'a pas pu être confirmée");
    }
    
    // Log pour débogage
    error_log("Recharge confirmée - Transaction ID: $transaction_id, Montant: {$transaction['montant']}");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Recharge confirmée avec succès !',
        'montant' => $transaction['montant']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
