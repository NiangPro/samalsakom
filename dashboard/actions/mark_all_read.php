<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    
    // Marquer toutes les notifications comme lues
    $query = "UPDATE notifications SET lu = 1 WHERE user_id = ? AND lu = 0";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$user_id]);
    
    if ($result) {
        $count = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => "$count notification(s) marquée(s) comme lue(s)"
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
