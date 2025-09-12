<?php
/**
 * Script pour corriger le mot de passe admin dans la base de données
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Nouveau hash pour le mot de passe "admin123"
    $new_password_hash = '$2y$10$7BVawMEW6iMnutb40qK3b.fENdxcAnxqfLymsiAYzz/rK/3rGMdP2';
    
    // Mise à jour du mot de passe admin
    $query = "UPDATE admins SET mot_de_passe = ? WHERE email = 'admin@samalsakom.sn'";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$new_password_hash]);
    
    if ($result) {
        echo "✅ Mot de passe admin mis à jour avec succès !<br>";
        echo "📧 Email: admin@samalsakom.sn<br>";
        echo "🔑 Mot de passe: admin123<br>";
        echo "<br><a href='admin-login.php'>🔗 Se connecter maintenant</a>";
    } else {
        echo "❌ Erreur lors de la mise à jour du mot de passe";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base de données: " . $e->getMessage();
}
?>
