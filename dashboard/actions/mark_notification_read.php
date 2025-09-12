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
    $notification_id = (int)$input['id'];
    $user_id = $_SESSION['user_id'];
    
    // Marquer la notification comme lue
    $query = "UPDATE notifications SET lu = 1 WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$notification_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
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
