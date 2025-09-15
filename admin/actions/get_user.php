<?php
require_once '../../config/database.php';

// Initialiser la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur non fourni']);
    exit;
}

$user_id = intval($_GET['id']);

try {
    // Préparer et exécuter la requête
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    // Vérifier si l'utilisateur existe
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Récupérer les données de l'utilisateur
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ne pas renvoyer le mot de passe
    unset($user['mot_de_passe']);
    
    // Renvoyer les données au format JSON
    echo json_encode(['success' => true, 'user' => $user]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()]);
}