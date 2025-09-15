<?php
session_start();
header('Content-Type: application/json');

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Vider la table des logs
    $query = "TRUNCATE TABLE admin_logs";
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        // Log de cette action
        $log_query = "INSERT INTO admin_logs (admin_id, action, details, date_creation) 
                      VALUES (?, 'clear_logs', 'Tous les logs ont été vidés', NOW())";
        $stmt = $db->prepare($log_query);
        $stmt->execute([$_SESSION['admin_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Logs vidés avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du vidage des logs']);
    }
    
} catch (Exception $e) {
    error_log("Erreur clear_logs: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
