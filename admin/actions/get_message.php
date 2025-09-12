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
    
    $message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$message_id) {
        echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
        exit;
    }
    
    // Récupérer le message
    $query = "SELECT * FROM contacts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message non trouvé']);
        exit;
    }
    
    // Générer le HTML pour l'affichage
    $html = '
    <div class="message-details" data-message-id="' . $message['id'] . '">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>De :</strong> ' . htmlspecialchars($message['nom']) . '
            </div>
            <div class="col-md-6">
                <strong>Email :</strong> ' . htmlspecialchars($message['email']) . '
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Sujet :</strong> ' . htmlspecialchars($message['sujet']) . '
            </div>
            <div class="col-md-6">
                <strong>Date :</strong> ' . date('d/m/Y à H:i', strtotime($message['date_creation'])) . '
            </div>
        </div>
        <div class="mb-3">
            <strong>Message :</strong>
            <div class="mt-2 p-3 bg-light rounded">
                ' . nl2br(htmlspecialchars($message['message'])) . '
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <strong>Statut :</strong> 
                <span class="badge bg-' . ($message['statut'] === 'nouveau' ? 'warning' : ($message['statut'] === 'lu' ? 'info' : 'success')) . '">
                    ' . ucfirst($message['statut']) . '
                </span>
            </div>
        </div>
    </div>';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'html' => $html
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
