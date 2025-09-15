<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
<<<<<<< HEAD
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
=======
    $input = json_decode(file_get_contents('php://input'), true);
    $montant = isset($input['montant']) ? (int)$input['montant'] : 0;
    $mode = $input['mode_paiement'] ?? '';

    if ($montant < 1000) {
        throw new Exception('Le montant minimum est 1000 FCFA');
    }
    if (!in_array($mode, ['orange_money', 'wave', 'virement'])) {
        throw new Exception('Mode de paiement invalide');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Associer la recharge à une participation existante (FK requis). Prendre la plus récente.
    $pstmt = $db->prepare("SELECT id AS participation_id, tontine_id FROM participations WHERE user_id = ? ORDER BY date_participation DESC LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $part = $pstmt->fetch();

    if (!$part) {
        throw new Exception("Vous devez participer à une tontine avant de recharger le portefeuille");
    }

    $query = "INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, mode_paiement, reference_paiement, date_paiement) 
              VALUES (?, ?, ?, ?, CURDATE(), 'completed', 'cotisation', ?, CONCAT('RC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $part['tontine_id'], $part['participation_id'], $montant, $mode]);

    echo json_encode(['success' => true, 'message' => 'Recharge effectuée avec succès']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Recharge error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>


>>>>>>> de209a5df705cdb1aa0c9ffa8b75087f1ac9e0cb
