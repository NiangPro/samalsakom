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
    $user_id = $_SESSION['user_id'];
    
    // Calculer le solde total
    $solde_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction IN ('cotisation', 'recharge', 'bonus') THEN montant ELSE 0 END), 0) as total_entrees,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($solde_query);
    $stmt->execute([$user_id]);
    $solde_data = $stmt->fetch();
    
    $solde_disponible = $solde_data['total_entrees'] - $solde_data['total_retire'];
    
    // Détail pour l'affichage
    $detail_query = "SELECT 
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'recharge' THEN montant ELSE 0 END), 0) as total_recharge,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'bonus' THEN montant ELSE 0 END), 0) as total_bonus,
        COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire
        FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($detail_query);
    $stmt->execute([$user_id]);
    $detail_data = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'solde_disponible' => $solde_disponible,
        'detail' => [
            'total_cotise' => $detail_data['total_cotise'],
            'total_recharge' => $detail_data['total_recharge'],
            'total_bonus' => $detail_data['total_bonus'],
            'total_retire' => $detail_data['total_retire']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
