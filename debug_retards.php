<?php
// Script de débogage pour vérifier les retards de paiement
session_start();
require_once 'config/database.php';

// Simuler une session utilisateur (remplacez par votre ID utilisateur)
$_SESSION['user_id'] = 1;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Debug - Retards de paiement</h2>";
    
    // 1. Vérifier toutes les tontines
    echo "<h3>1. Toutes les tontines :</h3>";
    $query = "SELECT * FROM tontines ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tontines = $stmt->fetchAll();
    
    foreach ($tontines as $tontine) {
        echo "ID: {$tontine['id']}, Nom: {$tontine['nom']}, Statut: {$tontine['statut']}<br>";
    }
    
    // 2. Vérifier les participations
    echo "<h3>2. Participations de l'utilisateur 1 :</h3>";
    $query = "SELECT p.*, t.nom as tontine_nom FROM participations p 
              JOIN tontines t ON p.tontine_id = t.id 
              WHERE p.user_id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $participations = $stmt->fetchAll();
    
    foreach ($participations as $participation) {
        echo "Tontine: {$participation['tontine_nom']}, Statut: {$participation['statut']}<br>";
    }
    
    // 3. Vérifier toutes les cotisations pending
    echo "<h3>3. Toutes les cotisations pending de l'utilisateur 1 :</h3>";
    $query = "SELECT c.*, t.nom as tontine_nom,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN DATEDIFF(CURDATE(), c.date_cotisation)
                ELSE 0
              END as jours_retard,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN 'en_retard'
                ELSE 'en_attente'
              END as statut_echeance
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.user_id = 1 AND c.statut = 'pending'
              ORDER BY c.date_cotisation ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $cotisations = $stmt->fetchAll();
    
    foreach ($cotisations as $cotisation) {
        echo "Tontine: {$cotisation['tontine_nom']}, ";
        echo "Type: {$cotisation['type_transaction']}, ";
        echo "Date: {$cotisation['date_cotisation']}, ";
        echo "Statut échéance: {$cotisation['statut_echeance']}, ";
        echo "Jours retard: {$cotisation['jours_retard']}<br>";
    }
    
    // 4. Vérifier spécifiquement la tontine "natt ouverture"
    echo "<h3>4. Cotisations pour 'natt ouverture' :</h3>";
    $query = "SELECT c.*, t.nom as tontine_nom,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN DATEDIFF(CURDATE(), c.date_cotisation)
                ELSE 0
              END as jours_retard,
              CASE 
                WHEN c.date_cotisation < CURDATE() THEN 'en_retard'
                ELSE 'en_attente'
              END as statut_echeance
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.user_id = 1 AND t.nom LIKE '%natt%'
              ORDER BY c.date_cotisation ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $natt_cotisations = $stmt->fetchAll();
    
    if (empty($natt_cotisations)) {
        echo "❌ Aucune cotisation trouvée pour 'natt ouverture'<br>";
        echo "<strong>Il faut exécuter le script create_natt_ouverture_data.sql</strong><br>";
    } else {
        foreach ($natt_cotisations as $cotisation) {
            echo "✅ Tontine: {$cotisation['tontine_nom']}, ";
            echo "Type: {$cotisation['type_transaction']}, ";
            echo "Date: {$cotisation['date_cotisation']}, ";
            echo "Statut: {$cotisation['statut']}, ";
            echo "Statut échéance: {$cotisation['statut_echeance']}, ";
            echo "Jours retard: {$cotisation['jours_retard']}<br>";
        }
    }
    
    // 5. Date actuelle pour référence
    echo "<h3>5. Date actuelle :</h3>";
    echo "Date serveur: " . date('Y-m-d H:i:s') . "<br>";
    echo "Date MySQL: ";
    $stmt = $db->query("SELECT NOW() as now, CURDATE() as today");
    $dates = $stmt->fetch();
    echo "NOW: {$dates['now']}, CURDATE: {$dates['today']}<br>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
