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
    $coord = trim($input['coordonnees'] ?? '');

    if ($montant < 1000) {
        throw new Exception('Le montant minimum est 1000 FCFA');
    }
    if (!in_array($mode, ['orange_money', 'wave', 'virement'])) {
        throw new Exception('Mode de réception invalide');
    }
    if ($coord === '') {
        throw new Exception('Les coordonnées sont requises');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Recalculer le solde disponible
    $q = "SELECT 
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'cotisation' THEN montant ELSE 0 END), 0) as total_cotise,
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'retrait' THEN montant ELSE 0 END), 0) as total_retire,
            COALESCE(SUM(CASE WHEN statut = 'completed' AND type_transaction = 'bonus' THEN montant ELSE 0 END), 0) as total_bonus
          FROM cotisations WHERE user_id = ?";
    $stmt = $db->prepare($q);
    $stmt->execute([$_SESSION['user_id']]);
    $s = $stmt->fetch();
    $solde = (int)$s['total_cotise'] + (int)$s['total_bonus'] - (int)$s['total_retire'];

    if ($montant > $solde) {
        throw new Exception('Montant supérieur au solde disponible');
    }

    // Associer à une participation existante pour respecter les contraintes FK
    $pstmt = $db->prepare("SELECT id AS participation_id, tontine_id FROM participations WHERE user_id = ? ORDER BY date_participation DESC LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $part = $pstmt->fetch();

    if (!$part) {
        throw new Exception("Aucune participation trouvée pour effectuer un retrait");
    }

    // Enregistrer le retrait comme complété pour impacter immédiatement le solde
    $query = "INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction, mode_paiement, reference_paiement, date_paiement) 
              VALUES (?, ?, ?, ?, CURDATE(), 'completed', 'retrait', ?, CONCAT('RT', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $part['tontine_id'], $part['participation_id'], $montant, $mode]);

    echo json_encode(['success' => true, 'message' => 'Demande de retrait enregistrée. Traitement en cours.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Retrait error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
?>


