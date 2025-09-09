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
    
    $tontine_id = isset($_POST['tontine_id']) ? (int)$_POST['tontine_id'] : 0;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $statut = isset($_POST['statut']) ? $_POST['statut'] : 'active';
    
    $errors = [];
    
    if (!$tontine_id) {
        $errors[] = "ID de tontine manquant";
    }
    
    if (!$user_id) {
        $errors[] = "Utilisateur manquant";
    }
    
    if (!in_array($statut, ['active', 'en_attente'])) {
        $errors[] = "Statut invalide";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Vérifier que la tontine existe
    $tontine_query = "SELECT * FROM tontines WHERE id = ?";
    $tontine_stmt = $db->prepare($tontine_query);
    $tontine_stmt->execute([$tontine_id]);
    $tontine = $tontine_stmt->fetch();
    
    if (!$tontine) {
        echo json_encode(['success' => false, 'message' => 'Tontine non trouvée']);
        exit;
    }
    
    // Vérifier que l'utilisateur existe et est actif
    $user_query = "SELECT * FROM users WHERE id = ? AND statut = 'actif'";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé ou inactif']);
        exit;
    }
    
    // Vérifier que l'utilisateur ne participe pas déjà
    $existing_query = "SELECT id FROM participations WHERE tontine_id = ? AND user_id = ?";
    $existing_stmt = $db->prepare($existing_query);
    $existing_stmt->execute([$tontine_id, $user_id]);
    
    if ($existing_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet utilisateur participe déjà à cette tontine']);
        exit;
    }
    
    // Vérifier le nombre maximum de participants
    $count_query = "SELECT COUNT(*) as count FROM participations WHERE tontine_id = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([$tontine_id]);
    $current_count = $count_stmt->fetch()['count'];
    
    if ($current_count >= $tontine['nombre_participants']) {
        echo json_encode(['success' => false, 'message' => 'Nombre maximum de participants atteint']);
        exit;
    }
    
    // Ajouter la participation
    $insert_query = "INSERT INTO participations (tontine_id, user_id, statut, date_participation) VALUES (?, ?, ?, NOW())";
    $insert_stmt = $db->prepare($insert_query);
    $result = $insert_stmt->execute([$tontine_id, $user_id, $statut]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Participant ajouté avec succès',
            'redirect' => "tontine-details.php?id=$tontine_id"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du participant']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
