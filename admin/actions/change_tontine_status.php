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
    
    if (!isset($input['tontine_id']) || !isset($input['status'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    $tontine_id = (int)$input['tontine_id'];
    $new_status = $input['status'];
    
    // Validation du statut
    if (!in_array($new_status, ['active', 'en_attente', 'suspendue', 'terminee'])) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }
    
    // Mise à jour du statut de la tontine
    $query = "UPDATE tontines SET statut = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$new_status, $tontine_id]);
    
    if ($result) {
        $actions = [
            'active' => 'activée',
            'en_attente' => 'mise en attente',
            'suspendue' => 'suspendue',
            'terminee' => 'terminée'
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => "Tontine {$actions[$new_status]} avec succès"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
