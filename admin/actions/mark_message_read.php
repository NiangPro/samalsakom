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
    
    if (!isset($input['message_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
        exit;
    }
    
    $message_id = (int)$input['message_id'];
    
    // Mettre à jour le statut du message
    $query = "UPDATE contacts SET statut = 'lu' WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$message_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Message marqué comme lu'
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
