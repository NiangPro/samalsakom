<?php
header('Content-Type: application/json');
session_start();

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupération des données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['participation_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de participation manquant']);
        exit;
    }
    
    $participation_id = (int)$input['participation_id'];
    
    // Commencer une transaction
    $db->beginTransaction();
    
    try {
        // Récupérer les infos de la participation
        $participation_query = "SELECT * FROM participations WHERE id = ?";
        $participation_stmt = $db->prepare($participation_query);
        $participation_stmt->execute([$participation_id]);
        $participation = $participation_stmt->fetch();
        
        if (!$participation) {
            echo json_encode(['success' => false, 'message' => 'Participation non trouvée']);
            exit;
        }
        
        // Supprimer les cotisations liées à ce participant pour cette tontine
        $delete_cotisations = "DELETE FROM cotisations WHERE tontine_id = ? AND user_id = ?";
        $stmt1 = $db->prepare($delete_cotisations);
        $stmt1->execute([$participation['tontine_id'], $participation['user_id']]);
        
        // Supprimer la participation
        $delete_participation = "DELETE FROM participations WHERE id = ?";
        $stmt2 = $db->prepare($delete_participation);
        $result = $stmt2->execute([$participation_id]);
        
        if ($result && $stmt2->rowCount() > 0) {
            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Participant retiré avec succès'
            ]);
        } else {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Participation non trouvée']);
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
