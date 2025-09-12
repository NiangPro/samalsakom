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
    
    // Marquer tous les messages comme lus
    $query = "UPDATE contacts SET statut = 'lu' WHERE statut = 'nouveau'";
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        $affected_rows = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => "$affected_rows message(s) marqué(s) comme lu(s)"
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
