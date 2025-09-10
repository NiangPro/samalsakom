<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = trim($_GET['q'] ?? '');
    $user_id = $_SESSION['user_id'];
    $results = [];
    
    if (strlen($query) >= 2) {
        // Rechercher dans les tontines
        $tontine_query = "SELECT t.id, t.nom, t.description, t.statut 
                         FROM tontines t 
                         LEFT JOIN participations p ON t.id = p.tontine_id AND p.user_id = ?
                         WHERE (t.nom LIKE ? OR t.description LIKE ?) 
                         AND t.statut = 'active'
                         ORDER BY p.id IS NOT NULL DESC, t.nom ASC 
                         LIMIT 5";
        $stmt = $db->prepare($tontine_query);
        $search_term = '%' . $query . '%';
        $stmt->execute([$user_id, $search_term, $search_term]);
        $tontines = $stmt->fetchAll();
        
        foreach ($tontines as $tontine) {
            $results[] = [
                'title' => $tontine['nom'],
                'description' => substr($tontine['description'], 0, 80) . '...',
                'icon' => 'fa-piggy-bank',
                'url' => 'decouvrir-tontines.php?search=' . urlencode($tontine['nom'])
            ];
        }
        
        // Rechercher dans les cotisations
        $cotisation_query = "SELECT c.id, t.nom as tontine_nom, c.montant, c.statut, c.date_cotisation
                            FROM cotisations c
                            JOIN tontines t ON c.tontine_id = t.id
                            WHERE c.user_id = ? AND t.nom LIKE ?
                            ORDER BY c.date_cotisation DESC
                            LIMIT 3";
        $stmt = $db->prepare($cotisation_query);
        $stmt->execute([$user_id, $search_term]);
        $cotisations = $stmt->fetchAll();
        
        foreach ($cotisations as $cotisation) {
            $status_text = $cotisation['statut'] === 'pending' ? 'En attente' : 'Payée';
            $results[] = [
                'title' => 'Cotisation - ' . $cotisation['tontine_nom'],
                'description' => number_format($cotisation['montant'], 0, ',', ' ') . ' FCFA - ' . $status_text,
                'icon' => 'fa-credit-card',
                'url' => 'paiements.php'
            ];
        }
        
        // Ajouter des liens rapides si aucun résultat spécifique
        if (empty($results)) {
            if (stripos($query, 'tontine') !== false || stripos($query, 'découvrir') !== false) {
                $results[] = [
                    'title' => 'Découvrir les tontines',
                    'description' => 'Parcourir toutes les tontines disponibles',
                    'icon' => 'fa-search',
                    'url' => 'decouvrir-tontines.php'
                ];
            }
            
            if (stripos($query, 'paiement') !== false || stripos($query, 'cotisation') !== false) {
                $results[] = [
                    'title' => 'Mes paiements',
                    'description' => 'Gérer mes cotisations et paiements',
                    'icon' => 'fa-credit-card',
                    'url' => 'paiements.php'
                ];
            }
            
            if (stripos($query, 'profil') !== false || stripos($query, 'compte') !== false) {
                $results[] = [
                    'title' => 'Mon profil',
                    'description' => 'Modifier mes informations personnelles',
                    'icon' => 'fa-user',
                    'url' => 'profil.php'
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Erreur quick_search: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de recherche'
    ]);
}
?>
