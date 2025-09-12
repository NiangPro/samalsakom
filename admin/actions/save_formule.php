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
    $formule_id = filter_input(INPUT_POST, 'formule_id', FILTER_VALIDATE_INT);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prix = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_FLOAT);
    $devise = filter_input(INPUT_POST, 'devise', FILTER_SANITIZE_STRING);
    $periode = filter_input(INPUT_POST, 'periode', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $couleur = filter_input(INPUT_POST, 'couleur', FILTER_SANITIZE_STRING);
    $ordre_affichage = filter_input(INPUT_POST, 'ordre_affichage', FILTER_VALIDATE_INT);
    $populaire = isset($_POST['populaire']) ? 1 : 0;
    $statut = filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_STRING);

    // Validation
    if (!$nom) {
        throw new Exception('Le nom est obligatoire');
    }

    if ($prix === false || $prix < 0) {
        $prix = 0;
    }

    if ($ordre_affichage === false) {
        $ordre_affichage = 0;
    }

    // Si populaire est coché, décocher les autres
    if ($populaire) {
        $db->exec("UPDATE formules_services SET populaire = 0");
    }

    if ($formule_id) {
        // Modification
        $query = "UPDATE formules_services SET 
                    nom = ?, prix = ?, devise = ?, periode = ?, description = ?, 
                    couleur = ?, ordre_affichage = ?, populaire = ?, statut = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $nom, $prix, $devise, $periode, $description,
            $couleur, $ordre_affichage, $populaire, $statut, $formule_id
        ]);
        $message = 'Formule modifiée avec succès';
    } else {
        // Création
        $query = "INSERT INTO formules_services 
                    (nom, prix, devise, periode, description, couleur, ordre_affichage, populaire, statut) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $nom, $prix, $devise, $periode, $description,
            $couleur, $ordre_affichage, $populaire, $statut
        ]);
        $message = 'Formule créée avec succès';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    error_log("Erreur save_formule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
