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
    
    if (!isset($input['message_ids']) || !is_array($input['message_ids'])) {
        echo json_encode(['success' => false, 'message' => 'IDs de messages manquants']);
        exit;
    }
    
    $message_ids = array_map('intval', $input['message_ids']);
    
    if (empty($message_ids)) {
        echo json_encode(['success' => false, 'message' => 'Aucun message sélectionné']);
        exit;
    }
    
    // Créer la requête avec placeholders
    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
    $query = "UPDATE contacts SET statut = 'repondu' WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute($message_ids);
    
    if ($result) {
        $affected_rows = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => "$affected_rows message(s) marqué(s) comme répondu(s)"
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
