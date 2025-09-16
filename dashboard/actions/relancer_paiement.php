<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['transaction_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de transaction manquant']);
        exit;
    }
    
    $transaction_id = (int)$input['transaction_id'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la transaction appartient à l'utilisateur et est en attente
    $query = "SELECT c.*, t.nom as tontine_nom 
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.id = ? AND c.user_id = ? AND c.statut = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction non trouvée ou non éligible']);
        exit;
    }
    
    // Générer une nouvelle référence de paiement
    $nouvelle_reference = 'PAY_' . strtoupper(uniqid()) . '_' . time();
    
    // Mettre à jour la transaction avec la nouvelle référence
    $update_query = "UPDATE cotisations 
                     SET reference_paiement = ?, 
                         date_creation = CURRENT_TIMESTAMP,
                         motif = CONCAT(COALESCE(motif, ''), ' - Paiement relancé le ', NOW())
                     WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $result = $stmt->execute([$nouvelle_reference, $transaction_id]);
    
    if ($result) {
        // Créer une notification pour l'utilisateur
        $notification_query = "INSERT INTO notifications (user_id, titre, message, type, date_creation) 
                              VALUES (?, ?, ?, 'info', NOW())";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([
            $user_id,
            'Paiement relancé',
            'Votre paiement de ' . number_format($transaction['montant'], 0, ',', ' ') . ' FCFA pour la tontine "' . $transaction['tontine_nom'] . '" a été relancé avec succès.'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Paiement relancé avec succès',
            'nouvelle_reference' => $nouvelle_reference
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la relance du paiement']);
    }
    
} catch (Exception $e) {
    error_log("Erreur relancer_paiement: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la relance']);
}
?>
