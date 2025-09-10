<?php
session_start();
require_once '../../config/database.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $fonctionnalite_id = filter_input(INPUT_POST, 'fonctionnalite_id', FILTER_VALIDATE_INT);
    $formule_id = filter_input(INPUT_POST, 'formule_id', FILTER_VALIDATE_INT);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $inclus = isset($_POST['inclus']) ? 1 : 0;
    $icone = filter_input(INPUT_POST, 'icone', FILTER_SANITIZE_STRING);
    $ordre_affichage = filter_input(INPUT_POST, 'ordre_affichage', FILTER_VALIDATE_INT);

    // Validation
    if (!$formule_id || !$nom) {
        throw new Exception('Formule et nom sont obligatoires');
    }

    if ($ordre_affichage === false) {
        $ordre_affichage = 0;
    }

    if (!$icone) {
        $icone = $inclus ? 'fas fa-check' : 'fas fa-times';
    }

    if ($fonctionnalite_id) {
        // Modification
        $query = "UPDATE formule_fonctionnalites SET 
                    nom = ?, description = ?, inclus = ?, icone = ?, ordre_affichage = ?
                  WHERE id = ? AND formule_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nom, $description, $inclus, $icone, $ordre_affichage, $fonctionnalite_id, $formule_id]);
        $message = 'Fonctionnalité modifiée avec succès';
    } else {
        // Création
        $query = "INSERT INTO formule_fonctionnalites 
                    (formule_id, nom, description, inclus, icone, ordre_affichage) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$formule_id, $nom, $description, $inclus, $icone, $ordre_affichage]);
        $message = 'Fonctionnalité ajoutée avec succès';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    error_log("Erreur save_fonctionnalite: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
