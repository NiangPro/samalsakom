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
    
    // Créer la transaction de recharge directement comme complétée
    $reference = 'RC' . date('YmdHis') . rand(1000, 9999);
    $query = "INSERT INTO cotisations (user_id, montant, type_transaction, methode_paiement, numero_telephone, statut, reference_paiement, date_creation, date_paiement) 
              VALUES (?, ?, 'recharge', ?, ?, 'completed', ?, NOW(), NOW())";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$user_id, $montant, $methode, $numero, $reference]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'enregistrement de la transaction");
    }
    
    $transaction_id = $db->lastInsertId();
    
    // Créer une notification pour l'utilisateur
    $notif_query = "INSERT INTO notifications (user_id, titre, message, type, date_creation) 
                    VALUES (?, 'Recharge effectuée', ?, 'success', NOW())";
    $notif_stmt = $db->prepare($notif_query);
    $notif_message = "Votre portefeuille a été rechargé de " . number_format($montant, 0, ',', ' ') . " FCFA via " . ucfirst(str_replace('_', ' ', $methode));
    $notif_stmt->execute([$user_id, $notif_message]);
    
    // Log pour débogage
    error_log("Recharge effectuée - User: $user_id, Montant: $montant, Transaction ID: $transaction_id, Référence: $reference");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Recharge effectuée avec succès! Votre solde a été mis à jour.',
        'transaction_id' => $transaction_id,
        'reference' => $reference,
        'montant' => $montant
    ]);
    
} catch (Exception $e) {
    error_log("Erreur recharge: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Erreur PDO recharge: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>
