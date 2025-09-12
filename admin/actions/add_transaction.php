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
    // Récupération et validation des données
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $tontine_id = filter_input(INPUT_POST, 'tontine_id', FILTER_VALIDATE_INT);
    $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
    $type_transaction = filter_input(INPUT_POST, 'type_transaction', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    // Validation des champs obligatoires
    if (!$user_id || !$tontine_id || !$montant || !$type_transaction) {
        throw new Exception('Tous les champs obligatoires doivent être remplis');
    }

    if ($montant <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }

    $types_valides = ['cotisation', 'remboursement', 'penalite'];
    if (!in_array($type_transaction, $types_valides)) {
        throw new Exception('Type de transaction invalide');
    }

    // Vérifier que l'utilisateur existe
    $user_check = "SELECT id, nom, prenom FROM users WHERE id = ?";
    $stmt = $db->prepare($user_check);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Utilisateur introuvable');
    }

    // Vérifier que la tontine existe
    $tontine_check = "SELECT id, nom, statut FROM tontines WHERE id = ?";
    $stmt = $db->prepare($tontine_check);
    $stmt->execute([$tontine_id]);
    $tontine = $stmt->fetch();

    if (!$tontine) {
        throw new Exception('Tontine introuvable');
    }

    // Récupérer l'ID de participation (obligatoire pour la contrainte FK)
    $participation_id = null;
    if ($type_transaction !== 'penalite') {
        $participation_check = "SELECT id FROM participations WHERE user_id = ? AND tontine_id = ? AND statut != 'retire'";
        $stmt = $db->prepare($participation_check);
        $stmt->execute([$user_id, $tontine_id]);
        $participation = $stmt->fetch();
        
        if (!$participation) {
            throw new Exception('L\'utilisateur ne participe pas à cette tontine');
        }
        $participation_id = $participation['id'];
    } else {
        // Pour les pénalités, créer une participation temporaire ou utiliser une existante
        $participation_check = "SELECT id FROM participations WHERE user_id = ? AND tontine_id = ? LIMIT 1";
        $stmt = $db->prepare($participation_check);
        $stmt->execute([$user_id, $tontine_id]);
        $participation = $stmt->fetch();
        
        if ($participation) {
            $participation_id = $participation['id'];
        } else {
            // Créer une participation pour la pénalité
            $create_participation = "INSERT INTO participations (user_id, tontine_id, statut, date_participation) VALUES (?, ?, 'confirme', NOW())";
            $stmt = $db->prepare($create_participation);
            $stmt->execute([$user_id, $tontine_id]);
            $participation_id = $db->lastInsertId();
        }
    }

    // Générer une date de cotisation appropriée
    $date_cotisation = date('Y-m-d');
    if ($type_transaction === 'cotisation') {
        // Pour les cotisations, calculer la prochaine date selon la fréquence
        $freq_query = "SELECT frequence FROM tontines WHERE id = ?";
        $stmt = $db->prepare($freq_query);
        $stmt->execute([$tontine_id]);
        $frequence = $stmt->fetchColumn();
        
        // Trouver la dernière cotisation pour cet utilisateur
        $last_cotisation = "SELECT MAX(date_cotisation) FROM cotisations WHERE user_id = ? AND tontine_id = ?";
        $stmt = $db->prepare($last_cotisation);
        $stmt->execute([$user_id, $tontine_id]);
        $last_date = $stmt->fetchColumn();
        
        if ($last_date) {
            switch ($frequence) {
                case 'hebdomadaire':
                    $date_cotisation = date('Y-m-d', strtotime($last_date . ' +1 week'));
                    break;
                case 'mensuelle':
                    $date_cotisation = date('Y-m-d', strtotime($last_date . ' +1 month'));
                    break;
                case 'quotidienne':
                    $date_cotisation = date('Y-m-d', strtotime($last_date . ' +1 day'));
                    break;
            }
        }
    }

    // Insérer la transaction
    $query = "INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, type_transaction, statut, date_cotisation, description, date_creation) 
              VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $user_id,
        $tontine_id,
        $participation_id,
        $montant,
        $type_transaction,
        $date_cotisation,
        $description ?: null
    ]);

    $transaction_id = $db->lastInsertId();

    // Créer une notification pour l'utilisateur
    $notification_message = '';
    switch ($type_transaction) {
        case 'cotisation':
            $notification_message = "Nouvelle cotisation de {$montant} FCFA ajoutée pour la tontine {$tontine['nom']}";
            break;
        case 'remboursement':
            $notification_message = "Remboursement de {$montant} FCFA effectué pour la tontine {$tontine['nom']}";
            break;
        case 'penalite':
            $notification_message = "Pénalité de {$montant} FCFA appliquée pour la tontine {$tontine['nom']}";
            break;
    }

    $notif_query = "INSERT INTO notifications (user_id, type, titre, message, date_creation) 
                    VALUES (?, 'transaction', ?, ?, NOW())";
    $stmt = $db->prepare($notif_query);
    $stmt->execute([
        $user_id,
        'Nouvelle transaction',
        $notification_message
    ]);

    // Log de l'action admin (optionnel - créer la table si elle n'existe pas)
    try {
        $log_query = "INSERT INTO admin_logs (admin_id, action, details, date_creation) 
                      VALUES (?, 'add_transaction', ?, NOW())";
        $stmt = $db->prepare($log_query);
        $stmt->execute([
            $_SESSION['admin_id'],
            "Transaction ajoutée: {$type_transaction} de {$montant} FCFA pour {$user['prenom']} {$user['nom']} (Tontine: {$tontine['nom']})"
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
                "Transaction ajoutée: {$type_transaction} de {$montant} FCFA pour {$user['prenom']} {$user['nom']} (Tontine: {$tontine['nom']})"
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction ajoutée avec succès',
        'transaction_id' => $transaction_id,
        'data' => [
            'user' => $user['prenom'] . ' ' . $user['nom'],
            'tontine' => $tontine['nom'],
            'montant' => $montant,
            'type' => $type_transaction
        ]
    ]);

} catch (Exception $e) {
    error_log("Erreur add_transaction: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
