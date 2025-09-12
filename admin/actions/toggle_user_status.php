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
    
    if (!isset($input['user_id']) || !isset($input['status'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    $user_id = (int)$input['user_id'];
    $new_status = $input['status'];
    
    // Validation du statut
    if (!in_array($new_status, ['actif', 'inactif'])) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }
    
    // Mise à jour du statut utilisateur
    $query = "UPDATE users SET statut = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$new_status, $user_id]);
    
    if ($result) {
        $action = $new_status === 'actif' ? 'activé' : 'désactivé';
        echo json_encode([
            'success' => true, 
            'message' => "Utilisateur $action avec succès"
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
