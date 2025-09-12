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
    
    $cotisation_id = (int)$_POST['cotisation_id'];
    $mode_paiement = $_POST['mode_paiement'];
    $telephone = $_POST['telephone'] ?? '';
    $reference_bancaire = $_POST['reference_bancaire'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la cotisation existe et appartient à l'utilisateur
    $query = "SELECT c.*, t.nom as tontine_nom 
              FROM cotisations c 
              JOIN tontines t ON c.tontine_id = t.id 
              WHERE c.id = ? AND c.user_id = ? AND c.statut = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$cotisation_id, $user_id]);
    $cotisation = $stmt->fetch();
    
    if (!$cotisation) {
        throw new Exception('Cotisation non trouvée');
    }
    
    // Validation selon le mode de paiement
    if (in_array($mode_paiement, ['orange_money', 'wave'])) {
        if (empty($telephone)) {
            throw new Exception('Numéro de téléphone requis');
        }
        $reference = $telephone;
    } elseif ($mode_paiement === 'virement') {
        if (empty($reference_bancaire)) {
            throw new Exception('Référence bancaire requise');
        }
        $reference = $reference_bancaire;
    } else {
        throw new Exception('Mode de paiement invalide');
    }
    
    // Générer une référence de paiement unique
    $reference_paiement = 'PAY_' . time() . '_' . $cotisation_id;
    
    // Simuler le traitement du paiement (en production, intégrer les APIs réelles)
    $success_rate = 0.95; // 95% de succès pour la simulation
    $payment_success = (rand(1, 100) / 100) <= $success_rate;
    
    if ($payment_success) {
        // Mettre à jour la cotisation comme payée
        $query = "UPDATE cotisations SET 
                  statut = 'completed',
                  mode_paiement = ?,
                  reference_paiement = ?,
                  date_paiement = NOW()
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$mode_paiement, $reference_paiement, $cotisation_id]);
        
        // Créer une notification pour l'utilisateur
        $message = "Paiement de " . number_format($cotisation['montant'], 0, ',', ' ') . " FCFA effectué avec succès pour la tontine \"" . $cotisation['tontine_nom'] . "\"";
        $query = "INSERT INTO notifications (user_id, titre, message, type, date_creation) 
                  VALUES (?, 'Paiement réussi', ?, 'success', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $message]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement effectué avec succès !',
            'reference' => $reference_paiement
        ]);
    } else {
        // Marquer le paiement comme échoué
        $query = "UPDATE cotisations SET 
                  statut = 'failed',
                  mode_paiement = ?,
                  reference_paiement = ?,
                  date_paiement = NOW()
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$mode_paiement, $reference_paiement, $cotisation_id]);
        
        throw new Exception('Échec du paiement. Veuillez réessayer ou contacter le support.');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Erreur PDO process_paiement: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
}
?>
