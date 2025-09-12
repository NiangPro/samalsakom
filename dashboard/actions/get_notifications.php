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
    
    $user_id = $_SESSION['user_id'];
    
    // Compter les notifications non lues
    $count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND lu = 0";
    $stmt = $db->prepare($count_query);
    $stmt->execute([$user_id]);
    $count = $stmt->fetch()['count'];
    
    // Récupérer les dernières notifications (limitées à 10)
    $notifications_query = "SELECT * FROM notifications 
                           WHERE user_id = ? 
                           ORDER BY date_creation DESC 
                           LIMIT 10";
    $stmt = $db->prepare($notifications_query);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_notifications: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de chargement des notifications'
    ]);
}
?>
