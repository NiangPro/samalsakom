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
    $formule_id = filter_input(INPUT_GET, 'formule_id', FILTER_VALIDATE_INT);
    
    if (!$formule_id) {
        throw new Exception('ID de formule invalide');
    }

    $query = "SELECT * FROM formule_fonctionnalites 
              WHERE formule_id = ? 
              ORDER BY ordre_affichage ASC, id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$formule_id]);
    $fonctionnalites = $stmt->fetchAll();

    echo json_encode(['success' => true, 'fonctionnalites' => $fonctionnalites]);

} catch (Exception $e) {
    error_log("Erreur get_fonctionnalites: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
