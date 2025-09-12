<?php
session_start();
require_once '../../config/database.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $formule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$formule_id) {
        throw new Exception('ID de formule invalide');
    }

    $query = "SELECT * FROM formules_services WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$formule_id]);
    $formule = $stmt->fetch();

    if (!$formule) {
        throw new Exception('Formule introuvable');
    }

    echo json_encode(['success' => true, 'formule' => $formule]);

} catch (Exception $e) {
    error_log("Erreur get_formule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
