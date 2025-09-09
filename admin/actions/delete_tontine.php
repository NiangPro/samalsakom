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
    
    if (!isset($input['tontine_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de tontine manquant']);
        exit;
    }
    
    $tontine_id = (int)$input['tontine_id'];
    
    // Commencer une transaction
    $db->beginTransaction();
    
    try {
        // Supprimer les cotisations liées
        $delete_cotisations = "DELETE FROM cotisations WHERE tontine_id = ?";
        $stmt1 = $db->prepare($delete_cotisations);
        $stmt1->execute([$tontine_id]);
        
        // Supprimer les participations liées
        $delete_participations = "DELETE FROM participations WHERE tontine_id = ?";
        $stmt2 = $db->prepare($delete_participations);
        $stmt2->execute([$tontine_id]);
        
        // Supprimer la tontine
        $delete_tontine = "DELETE FROM tontines WHERE id = ?";
        $stmt3 = $db->prepare($delete_tontine);
        $result = $stmt3->execute([$tontine_id]);
        
        if ($result && $stmt3->rowCount() > 0) {
            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Tontine supprimée avec succès'
            ]);
        } else {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Tontine non trouvée']);
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
