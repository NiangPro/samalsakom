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
    
    if (!$formule_id) {
        throw new Exception('ID de formule invalide');
    }

    // Vérifier que la formule existe
    $check_query = "SELECT nom FROM formules_services WHERE id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$formule_id]);
    $formule = $stmt->fetch();

    if (!$formule) {
        throw new Exception('Formule introuvable');
    }

    // Supprimer la formule (les fonctionnalités seront supprimées automatiquement par CASCADE)
    $delete_query = "DELETE FROM formules_services WHERE id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->execute([$formule_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Formule "' . $formule['nom'] . '" supprimée avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur delete_formule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
