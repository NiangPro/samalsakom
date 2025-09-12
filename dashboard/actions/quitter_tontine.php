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
    $tontine_id = (int)$input['tontine_id'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que l'utilisateur participe à cette tontine
    $query = "SELECT p.*, t.nom as tontine_nom, t.statut as tontine_statut 
              FROM participations p 
              JOIN tontines t ON p.tontine_id = t.id 
              WHERE p.tontine_id = ? AND p.user_id = ? AND p.statut != 'retire'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id, $user_id]);
    $participation = $stmt->fetch();
    
    if (!$participation) {
        throw new Exception('Participation non trouvée');
    }
    
    // Vérifier s'il y a des cotisations impayées
    $query = "SELECT COUNT(*) as count FROM cotisations 
              WHERE user_id = ? AND tontine_id = ? AND statut = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $tontine_id]);
    $cotisations_pending = $stmt->fetch()['count'];
    
    if ($cotisations_pending > 0) {
        throw new Exception('Vous ne pouvez pas quitter cette tontine car vous avez des cotisations en attente');
    }
    
    // Marquer la participation comme retirée
    $query = "UPDATE participations SET statut = 'retire', date_retrait = NOW() 
              WHERE tontine_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$tontine_id, $user_id]);
    
    if ($result) {
        // Annuler les cotisations futures
        $query = "UPDATE cotisations SET statut = 'cancelled' 
                  WHERE user_id = ? AND tontine_id = ? AND statut = 'pending' 
                  AND date_cotisation > NOW()";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $tontine_id]);
        
        // Créer une notification
        $message = "Vous avez quitté la tontine \"" . $participation['tontine_nom'] . "\"";
        $query = "INSERT INTO notifications (user_id, titre, message, type, date_creation) 
                  VALUES (?, 'Tontine quittée', ?, 'info', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $message]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Vous avez quitté la tontine avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la sortie de la tontine');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Erreur PDO quitter_tontine: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
}
?>
