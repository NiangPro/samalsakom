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
    
    // Créer la transaction de retrait directement comme complétée
    $reference = 'RT' . date('YmdHis') . rand(1000, 9999);
    $query = "INSERT INTO cotisations (user_id, montant, type_transaction, methode_paiement, numero_telephone, compte_bancaire, motif, statut, reference_paiement, date_creation, date_paiement) 
              VALUES (?, ?, 'retrait', ?, ?, ?, ?, 'completed', ?, NOW(), NOW())";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$user_id, $montant, $methode, $numero, $compte_bancaire, $motif, $reference]);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'enregistrement de la demande de retrait");
    }
    
    $transaction_id = $db->lastInsertId();
    
    // Créer une notification pour l'utilisateur
    $notif_query = "INSERT INTO notifications (user_id, titre, message, type, date_creation) 
                    VALUES (?, 'Retrait effectué', ?, 'info', NOW())";
    $notif_stmt = $db->prepare($notif_query);
    $notif_message = "Retrait de " . number_format($montant, 0, ',', ' ') . " FCFA effectué via " . ucfirst(str_replace('_', ' ', $methode));
    $notif_stmt->execute([$user_id, $notif_message]);
    
    // Log pour débogage
    error_log("Retrait effectué - User: $user_id, Montant: $montant, Transaction ID: $transaction_id, Référence: $reference");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Retrait effectué avec succès! Votre solde a été mis à jour.',
        'transaction_id' => $transaction_id,
        'reference' => $reference,
        'montant' => $montant
    ]);
    
} catch (Exception $e) {
    error_log("Erreur retrait: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Erreur PDO retrait: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>
