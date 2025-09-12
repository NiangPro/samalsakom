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

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(['error' => 'user_id requis']);
    exit;
}

echo "<h3>Debug pour utilisateur ID: $user_id</h3>";

try {
    // 1. Vérifier si l'utilisateur existe
    $user_query = "SELECT id, nom, prenom, email, statut FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    echo "<h4>1. Utilisateur:</h4>";
    if ($user) {
        echo "<pre>" . print_r($user, true) . "</pre>";
    } else {
        echo "<p>❌ Utilisateur introuvable</p>";
        exit;
    }
    
    // 2. Vérifier toutes les participations
    $participations_query = "SELECT p.*, t.nom as tontine_nom, t.statut as tontine_statut 
                            FROM participations p 
                            JOIN tontines t ON p.tontine_id = t.id 
                            WHERE p.user_id = ?";
    $stmt = $db->prepare($participations_query);
    $stmt->execute([$user_id]);
    $participations = $stmt->fetchAll();
    
    echo "<h4>2. Toutes les participations (" . count($participations) . "):</h4>";
    if (!empty($participations)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Tontine</th><th>Statut Participation</th><th>Statut Tontine</th><th>Date</th></tr>";
        foreach ($participations as $p) {
            echo "<tr>";
            echo "<td>{$p['tontine_id']}</td>";
            echo "<td>{$p['tontine_nom']}</td>";
            echo "<td>{$p['statut']}</td>";
            echo "<td>{$p['tontine_statut']}</td>";
            echo "<td>{$p['date_participation']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Aucune participation trouvée</p>";
    }
    
    // 3. Tester la requête actuelle
    echo "<h4>3. Test requête actuelle:</h4>";
    $current_query = "SELECT DISTINCT t.id, t.nom, t.montant_cotisation, t.statut 
                      FROM tontines t
                      JOIN participations p ON t.id = p.tontine_id
                      WHERE p.user_id = ? AND p.statut IN ('en_attente', 'confirme') 
                      AND t.statut IN ('en_attente', 'active', 'en_cours', 'terminee')
                      ORDER BY t.nom";
    $stmt = $db->prepare($current_query);
    $stmt->execute([$user_id]);
    $current_results = $stmt->fetchAll();
    
    echo "<p>Résultats trouvés: " . count($current_results) . "</p>";
    if (!empty($current_results)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Montant</th><th>Statut</th></tr>";
        foreach ($current_results as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['nom']}</td>";
            echo "<td>{$t['montant_cotisation']}</td>";
            echo "<td>{$t['statut']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Requête simplifiée
    echo "<h4>4. Test requête simplifiée (sans filtre statut):</h4>";
    $simple_query = "SELECT DISTINCT t.id, t.nom, t.montant_cotisation, t.statut 
                     FROM tontines t
                     JOIN participations p ON t.id = p.tontine_id
                     WHERE p.user_id = ?
                     ORDER BY t.nom";
    $stmt = $db->prepare($simple_query);
    $stmt->execute([$user_id]);
    $simple_results = $stmt->fetchAll();
    
    echo "<p>Résultats trouvés: " . count($simple_results) . "</p>";
    if (!empty($simple_results)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Montant</th><th>Statut</th></tr>";
        foreach ($simple_results as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['nom']}</td>";
            echo "<td>{$t['montant_cotisation']}</td>";
            echo "<td>{$t['statut']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>
