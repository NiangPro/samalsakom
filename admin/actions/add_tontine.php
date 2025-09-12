<?php
// Vérification de la session admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validation des données
    if (empty($_POST['nom']) || empty($_POST['montant_par_personne']) || 
        empty($_POST['nombre_participants']) || empty($_POST['frequence']) || 
        empty($_POST['createur_id'])) {
        throw new Exception('Tous les champs obligatoires doivent être remplis');
    }
    
    // Validation des valeurs numériques
    $montant = (int)$_POST['montant_par_personne'];
    $nb_participants = (int)$_POST['nombre_participants'];
    
    if ($montant < 1000) {
        throw new Exception('Le montant minimum est de 1000 FCFA');
    }
    
    if ($nb_participants < 2 || $nb_participants > 50) {
        throw new Exception('Le nombre de participants doit être entre 2 et 50');
    }
    
    // Vérifier que l'utilisateur créateur existe
    $check_user = "SELECT id FROM users WHERE id = ? AND statut = 'actif'";
    $stmt_check = $db->prepare($check_user);
    $stmt_check->execute([$_POST['createur_id']]);
    
    if (!$stmt_check->fetch()) {
        throw new Exception('Utilisateur créateur invalide ou inactif');
    }
    
    // Préparer les données
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description'] ?? '');
    $frequence = $_POST['frequence'];
    $createur_id = (int)$_POST['createur_id'];
    $statut = $_POST['statut'] ?? 'en_attente';
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    
    // Rendre l'opération atomique
    $db->beginTransaction();

    try {
        // Vérifier un doublon évident (même nom, créateur, même date de début)
        $dup_check = "SELECT id FROM tontines WHERE nom = ? AND createur_id = ? AND (
            (date_debut IS NULL AND ? IS NULL) OR (date_debut = ?)
        ) ORDER BY date_creation DESC LIMIT 1";
        $stmt_dup = $db->prepare($dup_check);
        $stmt_dup->execute([$nom, $createur_id, $date_debut, $date_debut]);
        if ($stmt_dup->fetch()) {
            throw new Exception('Une tontine avec le même nom, créateur et date existe déjà.');
        }

        // Insertion de la tontine
        $query = "INSERT INTO tontines (
            nom, description, montant_cotisation, nombre_participants, 
            frequence, date_debut, createur_id, statut, date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $nom, $description, $montant, $nb_participants,
            $frequence, $date_debut, $createur_id, $statut
        ]);
        
        if (!$result) {
            throw new Exception('Erreur lors de la création de la tontine');
        }

        $tontine_id = $db->lastInsertId();

        // Ajouter automatiquement le créateur comme participant confirmé
        // Note: la table participations a les statuts ('en_attente','confirme','retire')
        $add_creator = "INSERT INTO participations (tontine_id, user_id, date_participation, statut) 
                       VALUES (?, ?, NOW(), 'confirme')";
        $stmt_creator = $db->prepare($add_creator);
        $stmt_creator->execute([$tontine_id, $createur_id]);

        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Tontine créée avec succès',
            'tontine_id' => $tontine_id,
            'redirect' => 'tontines.php'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO add_tontine: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
