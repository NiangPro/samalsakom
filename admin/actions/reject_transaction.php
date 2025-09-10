<?php
session_start();
require_once '../../config/database.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
    $raison = filter_input(INPUT_POST, 'raison', FILTER_SANITIZE_STRING);
    
    if (!$transaction_id) {
        throw new Exception('ID de transaction invalide');
    }

    // Récupérer les détails de la transaction
    $query = "SELECT c.*, u.nom, u.prenom, t.nom as tontine_nom 
              FROM cotisations c
              JOIN users u ON c.user_id = u.id
              JOIN tontines t ON c.tontine_id = t.id
              WHERE c.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        throw new Exception('Transaction introuvable');
    }

    if ($transaction['statut'] !== 'pending') {
        throw new Exception('Cette transaction ne peut pas être rejetée');
    }

    // Mettre à jour le statut
    $update_query = "UPDATE cotisations SET statut = 'failed', raison_rejet = ?, date_validation = NOW() WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->execute([$raison ?: 'Transaction rejetée par l\'administrateur', $transaction_id]);

    // Créer une notification pour l'utilisateur
    $notification_message = "Votre {$transaction['type_transaction']} de {$transaction['montant']} FCFA pour la tontine {$transaction['tontine_nom']} a été rejetée";
    if ($raison) {
        $notification_message .= ". Raison: " . $raison;
    }
    
    $notif_query = "INSERT INTO notifications (user_id, type, titre, message, date_creation) 
                    VALUES (?, 'rejection', 'Transaction rejetée', ?, NOW())";
    $stmt = $db->prepare($notif_query);
    $stmt->execute([$transaction['user_id'], $notification_message]);

    // Log de l'action admin (optionnel - créer la table si elle n'existe pas)
    try {
        $log_query = "INSERT INTO admin_logs (admin_id, action, details, date_creation) 
                      VALUES (?, 'reject_transaction', ?, NOW())";
        $stmt = $db->prepare($log_query);
        $stmt->execute([
            $_SESSION['admin_id'],
            "Transaction rejetée: #{$transaction_id} - {$transaction['type_transaction']} de {$transaction['montant']} FCFA pour {$transaction['prenom']} {$transaction['nom']}" . ($raison ? " - Raison: {$raison}" : "")
        ]);
    } catch (PDOException $e) {
        // Si la table admin_logs n'existe pas, la créer automatiquement
        if ($e->getCode() == '42S02') {
            $create_table = "CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
            )";
            $db->exec($create_table);
            
            // Réessayer l'insertion
            $stmt = $db->prepare($log_query);
            $stmt->execute([
                $_SESSION['admin_id'],
                "Transaction rejetée: #{$transaction_id} - {$transaction['type_transaction']} de {$transaction['montant']} FCFA pour {$transaction['prenom']} {$transaction['nom']}" . ($raison ? " - Raison: {$raison}" : "")
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction rejetée avec succès'
    ]);

} catch (Exception $e) {
    error_log("Erreur reject_transaction: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
