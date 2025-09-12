<?php
require_once '../../config/database.php';

// Initialiser la connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Vérifier si les données sont soumises
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur non fourni']);
    exit;
}

// Récupérer les données du formulaire
$user_id = intval($_POST['user_id']);
$prenom = trim($_POST['prenom']);
$nom = trim($_POST['nom']);
$email = trim($_POST['email']);
$telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : null;
$date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
$adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : null;
$statut = $_POST['statut'];
$mot_de_passe = isset($_POST['mot_de_passe']) && !empty($_POST['mot_de_passe']) ? 
    password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT) : null;

// Validation des données
if (empty($prenom) || empty($nom) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
    exit;
}

try {
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$email, $user_id]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre utilisateur']);
        exit;
    }
    
    // Préparer la requête de mise à jour
    if ($mot_de_passe) {
        // Mise à jour avec nouveau mot de passe
        $query = "UPDATE users SET 
                  prenom = ?, 
                  nom = ?, 
                  email = ?, 
                  telephone = ?, 
                  date_naissance = ?, 
                  adresse = ?, 
                  statut = ?, 
                  mot_de_passe = ? 
                  WHERE id = ?";
        $params = [$prenom, $nom, $email, $telephone, $date_naissance, $adresse, $statut, $mot_de_passe, $user_id];
    } else {
        // Mise à jour sans changer le mot de passe
        $query = "UPDATE users SET 
                  prenom = ?, 
                  nom = ?, 
                  email = ?, 
                  telephone = ?, 
                  date_naissance = ?, 
                  adresse = ?, 
                  statut = ? 
                  WHERE id = ?";
        $params = [$prenom, $nom, $email, $telephone, $date_naissance, $adresse, $statut, $user_id];
    }
    
    // Exécuter la requête
    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'utilisateur']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
}