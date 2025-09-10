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
    
    if (!$fonctionnalite_id) {
        throw new Exception('ID de fonctionnalité invalide');
    }

    // Vérifier que la fonctionnalité existe
    $check_query = "SELECT nom FROM formule_fonctionnalites WHERE id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$fonctionnalite_id]);
    $fonctionnalite = $stmt->fetch();

    if (!$fonctionnalite) {
        throw new Exception('Fonctionnalité introuvable');
    }

    // Supprimer la fonctionnalité
    $delete_query = "DELETE FROM formule_fonctionnalites WHERE id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->execute([$fonctionnalite_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Fonctionnalité supprimée avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur delete_fonctionnalite: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
