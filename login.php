<?php
$page_title = "Connexion";
include 'includes/header.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $email_telephone = trim($_POST['email_telephone'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    $errors = [];
    
    if (empty($email_telephone)) {
        $errors[] = "Email ou téléphone requis";
    }
    
    if (empty($mot_de_passe)) {
        $errors[] = "Mot de passe requis";
    }
    
    if (empty($errors)) {
        try {
            // Recherche de l'utilisateur par email ou téléphone
            $query = "SELECT * FROM users WHERE email = ? OR telephone = ? AND statut = 'actif'";
            $stmt = $db->prepare($query);
            $stmt->execute([$email_telephone, $email_telephone]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                // Connexion réussie
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirection vers le tableau de bord utilisateur
                header('Location: dashboard/index.php');
                exit;
            } else {
                $error_message = "Identifiants incorrects. Vérifiez votre email/téléphone et mot de passe.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion. Veuillez réessayer.";
        }
    }
}
?>

<!-- Hero Section Login -->
<section class="hero-section" style="min-height: 100vh;">
    <div class="container">
        <div class="row align-items-center justify-content-center min-vh-100">
            <div class="col-lg-10">
                <div class="row g-0 card-modern overflow-hidden">
                    <!-- Côté gauche - Informations -->
                    <div class="col-lg-6 bg-gradient-primary text-white p-5 d-flex flex-column justify-content-center">
                        <div data-aos="fade-right">
                            <h2 class="display-5 fw-bold mb-4">
                                Bon Retour sur <br>SamalSakom !
                            </h2>
                            <p class="lead mb-4">
                                Connectez-vous pour accéder à vos tontines, suivre vos épargnes 
                                et gérer vos finances en toute sécurité.
                            </p>
                            
                            <div class="features-list">
                                <div class="feature-item d-flex align-items-center mb-3">
                                    <i class="fas fa-shield-alt me-3" style="font-size: 1.5rem;"></i>
                                    <span>Sécurité bancaire garantie</span>
                                </div>
                                <div class="feature-item d-flex align-items-center mb-3">
                                    <i class="fas fa-mobile-alt me-3" style="font-size: 1.5rem;"></i>
                                    <span>Accès depuis tous vos appareils</span>
                                </div>
                                <div class="feature-item d-flex align-items-center mb-3">
                                    <i class="fas fa-clock me-3" style="font-size: 1.5rem;"></i>
                                    <span>Disponible 24h/24, 7j/7</span>
                                </div>
                            </div>
                            
                            <div class="mt-5">
                                <p class="mb-2">Pas encore de compte ?</p>
                                <a href="register.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Créer un Compte
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Côté droit - Formulaire -->
                    <div class="col-lg-6 p-5">
                        <div data-aos="fade-left">
                            <div class="text-center mb-5">
                                <h3 class="fw-bold text-gradient">Connexion</h3>
                                <p class="text-muted">Accédez à votre espace personnel</p>
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
                            
                            <form method="POST" action="" class="login-form">
                                <div class="mb-4">
                                    <label for="email_telephone" class="form-label">Email ou Téléphone</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="email_telephone" 
                                               name="email_telephone" 
                                               value="<?php echo htmlspecialchars($email_telephone ?? ''); ?>"
                                               placeholder="votre@email.com ou 77 123 45 67" required>
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
                                    <a href="forgot-password.php" class="text-decoration-none">
                                        Mot de passe oublié ?
                                    </a>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom w-100 btn-lg mb-4">
                                    <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <div class="divider my-4">
                                    <span class="divider-text text-muted">ou</span>
                                </div>
                                
                                <div class="social-login d-grid gap-2">
                                    <button class="btn btn-outline-secondary">
                                        <i class="fab fa-google me-2"></i>Continuer avec Google
                                    </button>
                                    <button class="btn btn-outline-secondary">
                                        <i class="fab fa-facebook-f me-2"></i>Continuer avec Facebook
                                    </button>
                                </div>
                                
                                <div class="mt-4">
                                    <p class="text-muted mb-0">
                                        Nouveau sur SamalSakom ? 
                                        <a href="register.php" class="text-decoration-none fw-bold">
                                            Créer un compte
                                        </a>
                                    </p>
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
.divider {
    position: relative;
    text-align: center;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #dee2e6;
}

.divider-text {
    background: white;
    padding: 0 1rem;
    position: relative;
}

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

.social-login .btn {
    border-radius: 50px;
    padding: 12px 20px;
}

@media (max-width: 768px) {
    .hero-section .row.g-0 {
        flex-direction: column-reverse;
    }
    
    .hero-section .col-lg-6:first-child {
        padding: 2rem !important;
    }
    
    .hero-section .col-lg-6:last-child {
        padding: 2rem !important;
    }
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
    
    // Auto-format phone number
    const emailTelInput = document.getElementById('email_telephone');
    if (emailTelInput) {
        emailTelInput.addEventListener('input', function() {
            let value = this.value;
            
            // Si ce n'est pas un email (pas de @), formater comme numéro
            if (!value.includes('@') && /^\d/.test(value)) {
                value = value.replace(/\D/g, '');
                
                if (value.startsWith('221')) {
                    value = value.substring(3);
                }
                
                if (value.length >= 2) {
                    value = value.substring(0, 2) + ' ' + value.substring(2, 5) + ' ' + value.substring(5, 7) + ' ' + value.substring(7, 9);
                }
                
                this.value = value.trim();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
