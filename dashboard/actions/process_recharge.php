<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $montant = isset($input['montant']) ? (int)$input['montant'] : 0;
    $mode = $input['mode_paiement'] ?? '';

    if ($montant < 1000) {
        throw new Exception('Le montant minimum est 1000 FCFA');
    }
    if (!in_array($mode, ['orange_money', 'wave', 'virement'])) {
        throw new Exception('Mode de paiement invalide');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Associer la recharge à une participation existante (FK requis). Prendre la plus récente.
    $pstmt = $db->prepare("SELECT id AS participation_id, tontine_id FROM participations WHERE user_id = ? ORDER BY date_participation DESC LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $part = $pstmt->fetch();

    if (!$part) {
        throw new Exception("Vous devez participer à une tontine avant de recharger le portefeuille");
    }

    $query = "INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, mode_paiement, reference_paiement, date_paiement) 
              VALUES (?, ?, ?, ?, CURDATE(), 'completed', 'cotisation', ?, CONCAT('RC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $part['tontine_id'], $part['participation_id'], $montant, $mode]);

    echo json_encode(['success' => true, 'message' => 'Recharge effectuée avec succès']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Recharge error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>


