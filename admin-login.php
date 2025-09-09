<?php
$page_title = "Connexion Admin";

// Traitement du formulaire de connexion admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    }
    
    if (empty($mot_de_passe)) {
        $errors[] = "Le mot de passe est requis";
    }
    
    if (empty($errors)) {
        try {
            // Recherche de l'admin
            $query = "SELECT * FROM admins WHERE email = ? AND statut = 'actif'";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
                // Connexion réussie
                session_start();
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nom'] = $admin['nom'];
                $_SESSION['admin_prenom'] = $admin['prenom'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Mise à jour de la dernière connexion
                $update_query = "UPDATE admins SET derniere_connexion = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$admin['id']]);
                
                // Redirection vers le dashboard
                header('Location: admin/index.php');
                exit;
            } else {
                $error_message = "Identifiants incorrects. Vérifiez votre email et mot de passe.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SamalSakom</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Admin Login Section -->
<section class="hero-section" style="min-height: 100vh;">
    <div class="container">
        <div class="row align-items-center justify-content-center min-vh-100">
            <div class="col-lg-5 col-md-7">
                <div class="card-modern p-5">
                    <div class="text-center mb-5">
                        <div class="admin-logo mb-4">
                            <i class="fas fa-shield-alt text-gradient" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="fw-bold text-gradient">Administration</h2>
                        <p class="text-muted">Accès réservé aux administrateurs SamalSakom</p>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="admin-login-form">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Administrateur</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user-shield"></i>
                                </span>
                                <input type="email" class="form-control" id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       placeholder="admin@samalsakom.sn" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="mot_de_passe" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="mot_de_passe" 
                                       name="mot_de_passe" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Se souvenir de moi
                                </label>
                            </div>
                            <a href="#" class="text-decoration-none small">
                                Mot de passe oublié ?
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100 btn-lg mb-4">
                            <i class="fas fa-sign-in-alt me-2"></i>Accéder au Dashboard
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <hr class="my-4">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Compte de test : <strong>admin@samalsakom.sn</strong> / <strong>admin123</strong>
                        </p>
                        <div class="mt-3">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Retour au site
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Informations de sécurité -->
                <div class="mt-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <i class="fas fa-shield-alt text-success mb-2 d-block"></i>
                                    <small class="text-muted">Sécurisé</small>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-lock text-primary mb-2 d-block"></i>
                                    <small class="text-muted">Chiffré</small>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-eye-slash text-warning mb-2 d-block"></i>
                                    <small class="text-muted">Privé</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.input-group-text {
    background: transparent;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
}

.admin-logo {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.card-modern {
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('mot_de_passe');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Focus sur le premier champ
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.focus();
    }
});
</script>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
