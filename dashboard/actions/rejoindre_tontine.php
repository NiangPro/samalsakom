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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tontine_id = (int)$input['tontine_id'];
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la tontine existe et est active
    $query = "SELECT * FROM tontines WHERE id = ? AND statut = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id]);
    $tontine = $stmt->fetch();
    
    if (!$tontine) {
        throw new Exception('Tontine non trouvée ou inactive');
    }
    
    // Vérifier que l'utilisateur n'est pas déjà participant
    $query = "SELECT id FROM participations WHERE tontine_id = ? AND user_id = ? AND statut != 'retire'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id, $user_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Vous participez déjà à cette tontine');
    }
    
    // Vérifier qu'il reste des places
    $query = "SELECT COUNT(*) as count FROM participations WHERE tontine_id = ? AND statut != 'retire'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id]);
    $participants_actuels = $stmt->fetch()['count'];
    
    if ($participants_actuels >= $tontine['nombre_participants']) {
        throw new Exception('Cette tontine est complète');
    }
    
    // Ajouter la participation
    $query = "INSERT INTO participations (tontine_id, user_id, date_participation, statut) 
              VALUES (?, ?, NOW(), 'confirme')";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$tontine_id, $user_id]);
    
    if ($result) {
        // Créer les cotisations futures pour cet utilisateur
        $participation_id = $db->lastInsertId();
        
        // Calculer les dates de cotisation selon la fréquence
        $date_debut = new DateTime($tontine['date_debut'] ?? 'now');
        $montant = $tontine['montant_cotisation'];
        
        for ($i = 0; $i < $tontine['duree_mois']; $i++) {
            $date_cotisation = clone $date_debut;
            
            switch ($tontine['frequence']) {
                case 'hebdomadaire':
                    $date_cotisation->add(new DateInterval('P' . ($i * 7) . 'D'));
                    break;
                case 'mensuelle':
                    $date_cotisation->add(new DateInterval('P' . $i . 'M'));
                    break;
                case 'trimestrielle':
                    $date_cotisation->add(new DateInterval('P' . ($i * 3) . 'M'));
                    break;
            }
            
            $query = "INSERT INTO cotisations (user_id, tontine_id, participation_id, montant, date_cotisation, statut, type_transaction) 
                      VALUES (?, ?, ?, ?, ?, 'pending', 'cotisation')";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $tontine_id, $participation_id, $montant, $date_cotisation->format('Y-m-d')]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Vous avez rejoint la tontine avec succès !'
        ]);
    } else {
        throw new Exception('Erreur lors de l\'inscription');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Erreur PDO rejoindre_tontine: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données'
    ]);
}
?>
