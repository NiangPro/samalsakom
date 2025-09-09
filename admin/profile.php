<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Traitement du formulaire de mise à jour du profil
if ($_POST && isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    
    if (!empty($nom) && !empty($prenom) && !empty($email)) {
        try {
            // Vérifier si l'email n'est pas déjà utilisé par un autre admin
            $check_query = "SELECT id FROM admins WHERE email = ? AND id != ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$email, $_SESSION['admin_id']]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "Cet email est déjà utilisé par un autre administrateur.";
            } else {
                $query = "UPDATE admins SET nom = ?, prenom = ?, email = ?, date_modification = NOW() WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$nom, $prenom, $email, $_SESSION['admin_id']]);
                
                // Mettre à jour les variables de session
                $_SESSION['admin_nom'] = $nom;
                $_SESSION['admin_prenom'] = $prenom;
                $_SESSION['admin_email'] = $email;
                
                $success_message = "Profil mis à jour avec succès.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de la mise à jour du profil.";
        }
    } else {
        $error_message = "Tous les champs sont obligatoires.";
    }
}

// Traitement du changement de mot de passe
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            try {
                // Vérifier le mot de passe actuel
                $query = "SELECT mot_de_passe FROM admins WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['admin_id']]);
                $admin_data = $stmt->fetch();
                
                if (password_verify($current_password, $admin_data['mot_de_passe'])) {
                    // Mettre à jour le mot de passe
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE admins SET mot_de_passe = ?, date_modification = NOW() WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                    
                    $success_message = "Mot de passe modifié avec succès.";
                } else {
                    $error_message = "Le mot de passe actuel est incorrect.";
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors du changement de mot de passe.";
            }
        } else {
            $error_message = "Les nouveaux mots de passe ne correspondent pas.";
        }
    } else {
        $error_message = "Tous les champs sont obligatoires pour changer le mot de passe.";
    }
}

// Récupération des informations de l'admin
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Mon Profil</h1>
                <p class="content-subtitle">Gérer vos informations personnelles et paramètres de compte</p>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Informations du profil -->
            <div class="col-xl-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="profile-avatar-large mb-3">
                            <?php if ($admin['photo_profil']): ?>
                            <img src="<?php echo htmlspecialchars($admin['photo_profil']); ?>" class="rounded-circle" width="120" height="120" alt="Avatar">
                            <?php else: ?>
                            <div class="avatar-large bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 2.5rem;">
                                <?php echo strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($admin['email']); ?></p>
                        <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?> mb-3">
                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                        </span>
                        
                        <div class="profile-stats mt-4">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-number">
                                        <?php echo date('d/m/Y', strtotime($admin['date_creation'])); ?>
                                    </div>
                                    <div class="stat-label">Membre depuis</div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number">
                                        <?php echo $admin['derniere_connexion'] ? date('d/m/Y', strtotime($admin['derniere_connexion'])) : 'Jamais'; ?>
                                    </div>
                                    <div class="stat-label">Dernière connexion</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Activité Récente</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-item">
                            <div class="activity-icon bg-primary">
                                <i class="fas fa-sign-in-alt text-white"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Connexion</div>
                                <div class="activity-time">
                                    <?php echo $admin['derniere_connexion'] ? date('d/m/Y à H:i', strtotime($admin['derniere_connexion'])) : 'Aucune connexion'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-success">
                                <i class="fas fa-edit text-white"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Profil modifié</div>
                                <div class="activity-time">
                                    <?php echo date('d/m/Y à H:i', strtotime($admin['date_modification'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaires -->
            <div class="col-xl-8">
                <!-- Informations personnelles -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2 text-primary"></i>Informations Personnelles
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" name="prenom" value="<?php echo htmlspecialchars($admin['prenom']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($admin['nom']); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rôle</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Statut</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($admin['statut']); ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Mettre à jour le profil
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Changer le mot de passe -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-key me-2 text-warning"></i>Changer le Mot de Passe
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Mot de passe actuel *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nouveau mot de passe *</label>
                                    <input type="password" class="form-control" name="new_password" required minlength="6">
                                    <small class="text-muted">Minimum 6 caractères</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmer le nouveau mot de passe *</label>
                                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-1"></i>Changer le mot de passe
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paramètres de sécurité -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt me-2 text-success"></i>Paramètres de Sécurité
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                    <label class="form-check-label" for="twoFactorAuth">
                                        Authentification à deux facteurs
                                    </label>
                                </div>
                                <small class="text-muted">Sécurité renforcée pour votre compte</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">
                                        Notifications par email
                                    </label>
                                </div>
                                <small class="text-muted">Recevoir les alertes importantes</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-outline-primary" onclick="saveSecuritySettings()">
                                <i class="fas fa-save me-1"></i>Enregistrer les paramètres
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saveSecuritySettings() {
    showToast('Paramètres de sécurité sauvegardés', 'success');
}

// Validation du formulaire de mot de passe
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.querySelector('input[name="new_password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    if (newPassword && confirmPassword) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            showToast('Les mots de passe ne correspondent pas', 'error');
        }
    }
});
</script>

<style>
.profile-avatar-large {
    margin: 0 auto;
}

.profile-stats .stat-number {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
}

.profile-stats .stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.activity-item {
    display: flex;
    align-items-center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.activity-title {
    font-weight: 500;
    color: #495057;
}

.activity-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>

<?php include 'includes/footer.php'; ?>
