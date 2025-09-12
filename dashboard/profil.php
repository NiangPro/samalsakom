<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les informations de l'utilisateur
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../login.php');
        exit;
    }
    
    // Traitement du formulaire de mise à jour du profil
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        $adresse = trim($_POST['adresse']);
        $profession = trim($_POST['profession']);
        
        $errors = [];
        
        // Validation
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($prenom)) $errors[] = "Le prénom est requis";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email valide requis";
        }
        if (empty($telephone)) $errors[] = "Le téléphone est requis";
        
        // Vérifier si l'email ou le téléphone existe déjà (sauf pour cet utilisateur)
        if (empty($errors)) {
            $query = "SELECT id FROM users WHERE (email = ? OR telephone = ?) AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email, $telephone, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email ou téléphone est déjà utilisé";
            }
        }
        
        if (empty($errors)) {
            $query = "UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, profession = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $profession, $user_id]);
            
            if ($result) {
                $success_message = "Profil mis à jour avec succès";
                // Recharger les données utilisateur
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $errors[] = "Erreur lors de la mise à jour";
            }
        }
    }
    
    // Traitement du changement de mot de passe
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $password_errors = [];
        
        // Validation
        if (empty($current_password)) $password_errors[] = "Mot de passe actuel requis";
        if (empty($new_password)) $password_errors[] = "Nouveau mot de passe requis";
        if (strlen($new_password) < 6) $password_errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        if ($new_password !== $confirm_password) $password_errors[] = "Les mots de passe ne correspondent pas";
        
        // Vérifier le mot de passe actuel
        if (empty($password_errors) && !password_verify($current_password, $user['mot_de_passe'])) {
            $password_errors[] = "Mot de passe actuel incorrect";
        }
        
        if (empty($password_errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET mot_de_passe = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$hashed_password, $user_id]);
            
            if ($result) {
                $password_success = "Mot de passe modifié avec succès";
            } else {
                $password_errors[] = "Erreur lors de la modification";
            }
        }
    }
    
    // Statistiques utilisateur
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM participations WHERE user_id = ? AND statut != 'retire') as tontines_actives,
        (SELECT COALESCE(SUM(montant), 0) FROM cotisations WHERE user_id = ? AND statut = 'completed') as total_cotise,
        (SELECT COUNT(*) FROM cotisations WHERE user_id = ? AND statut = 'pending') as cotisations_pending,
        (SELECT COUNT(*) FROM cotisations WHERE user_id = ? AND statut = 'completed') as paiements_effectues";
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $user_stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Erreur profil: " . $e->getMessage());
    $errors[] = "Erreur de chargement des données";
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- En-tête de page -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-user-circle me-3"></i>
                        Mon Profil
                    </h1>
                    <p class="page-description">Gérez vos informations personnelles et paramètres de compte</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Informations personnelles -->
                <div class="card modern-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            Informations Personnelles
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $success_message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?= htmlspecialchars($user['nom']) ?>" required>
                                    <div class="invalid-feedback">Le nom est requis</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?= htmlspecialchars($user['prenom']) ?>" required>
                                    <div class="invalid-feedback">Le prénom est requis</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                    <div class="invalid-feedback">Email valide requis</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+221</span>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" 
                                               value="<?= htmlspecialchars($user['telephone']) ?>" required>
                                    </div>
                                    <div class="invalid-feedback">Le téléphone est requis</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2" 
                                          placeholder="Votre adresse complète"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="profession" class="form-label">Profession</label>
                                <input type="text" class="form-control" id="profession" name="profession" 
                                       value="<?= htmlspecialchars($user['profession'] ?? '') ?>" 
                                       placeholder="Votre profession">
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_profile" class="btn btn-success-modern">
                                    <i class="fas fa-save me-2"></i>
                                    Sauvegarder les modifications
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Changement de mot de passe -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>
                            Sécurité du Compte
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $password_success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($password_errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($password_errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel *</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                                <div class="invalid-feedback">Mot de passe actuel requis</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" minlength="6" required>
                                <div class="form-text">Minimum 6 caractères</div>
                                <div class="invalid-feedback">Nouveau mot de passe requis (min. 6 caractères)</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                                <div class="invalid-feedback">Confirmation requise</div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>
                                Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec statistiques -->
            <div class="col-lg-4">
                <!-- Photo de profil -->
                <div class="card modern-card mb-4">
                    <div class="card-body text-center">
                        <div class="profile-avatar mb-3">
                            <div class="avatar-circle">
                                <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                            </div>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($user['profession'] ?? 'Membre') ?></p>
                        <span class="badge bg-success">Compte Actif</span>
                        
                        <div class="mt-3">
                            <small class="text-muted">Membre depuis</small><br>
                            <span class="fw-semibold"><?= date('F Y', strtotime($user['date_creation'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistiques personnelles -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Mes Statistiques
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $user_stats['tontines_actives'] ?></div>
                                <div class="stat-label">Tontines Actives</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= number_format($user_stats['total_cotise'], 0, ',', ' ') ?></div>
                                <div class="stat-label">FCFA Cotisés</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $user_stats['cotisations_pending'] ?></div>
                                <div class="stat-label">Paiements En Attente</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $user_stats['paiements_effectues'] ?></div>
                                <div class="stat-label">Paiements Effectués</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    position: relative;
    display: inline-block;
}

.avatar-circle {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto;
    box-shadow: var(--shadow-md);
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--dark-color);
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
}

.input-group-text {
    background: var(--gray-50);
    border-color: var(--gray-300);
}
</style>

<script>
// Validation des formulaires
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Validation des mots de passe
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
    } else {
        this.setCustomValidity('');
    }
});

// Formatage du numéro de téléphone
document.getElementById('telephone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + ' ' + value.substring(2, 5) + ' ' + value.substring(5, 7) + ' ' + value.substring(7, 9);
    }
    this.value = value.trim();
});
</script>

<?php include 'includes/footer.php'; ?>
