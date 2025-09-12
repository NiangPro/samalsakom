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
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $response = ['users' => [], 'tontines' => []];

    // Récupérer tous les utilisateurs actifs
    $users_query = "SELECT id, nom, prenom, email FROM users WHERE statut = 'actif' ORDER BY nom, prenom";
    $stmt = $db->prepare($users_query);
    $stmt->execute();
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $response['users'][] = [
            'id' => $user['id'],
            'nom' => $user['prenom'] . ' ' . $user['nom'],
            'email' => $user['email']
        ];
    }

    // Si un utilisateur est sélectionné, récupérer ses tontines
    if ($user_id) {
        // Requête simplifiée sans filtres restrictifs
        $tontines_query = "SELECT DISTINCT t.id, t.nom, t.montant_cotisation, t.statut 
                          FROM tontines t
                          INNER JOIN participations p ON t.id = p.tontine_id
                          WHERE p.user_id = ?
                          ORDER BY t.nom";
        $stmt = $db->prepare($tontines_query);
        $stmt->execute([$user_id]);
        $tontines = $stmt->fetchAll();

        foreach ($tontines as $tontine) {
            $response['tontines'][] = [
                'id' => $tontine['id'],
                'nom' => $tontine['nom'],
                'montant_cotisation' => $tontine['montant_cotisation'],
                'statut' => $tontine['statut']
            ];
        }
    } else {
        // Si aucun utilisateur sélectionné, récupérer toutes les tontines actives
        $tontines_query = "SELECT id, nom, montant_cotisation, statut 
                          FROM tontines 
                          WHERE statut IN ('en_attente', 'active', 'en_cours', 'terminee') 
                          ORDER BY nom";
        $stmt = $db->prepare($tontines_query);
        $stmt->execute();
        $tontines = $stmt->fetchAll();

        foreach ($tontines as $tontine) {
            $response['tontines'][] = [
                'id' => $tontine['id'],
                'nom' => $tontine['nom'],
                'montant_cotisation' => $tontine['montant_cotisation'],
                'statut' => $tontine['statut']
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    error_log("Erreur get_users_tontines: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des données'
    ]);
}
?>
