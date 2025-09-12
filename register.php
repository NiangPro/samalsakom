<?php
$page_title = "Inscription";
include 'includes/header.php';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $terms = isset($_POST['terms']);
    
    $errors = [];
    
    // Validation
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire";
    if (strlen($mot_de_passe) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    if ($mot_de_passe !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
    if (empty($date_naissance)) $errors[] = "La date de naissance est obligatoire";
    if (!$terms) $errors[] = "Vous devez accepter les conditions d'utilisation";
    
    // Vérification de l'unicité email/téléphone
    if (empty($errors)) {
        try {
            $query = "SELECT id FROM users WHERE email = ? OR telephone = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email, $telephone]);
            
            if ($stmt->fetch()) {
                $errors[] = "Cet email ou numéro de téléphone est déjà utilisé";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de vérification des données";
        }
    }
    
    // Insertion en base
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (nom, prenom, email, telephone, mot_de_passe, date_naissance, adresse) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nom, $prenom, $email, $telephone, $hashed_password, $date_naissance, $adresse]);
            
            $success_message = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
            
            // Redirection après 3 secondes
            header("refresh:3;url=login.php");
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la création du compte. Veuillez réessayer.";
        }
    }
}
?>

<!-- Hero Section Register -->
<section class="hero-section" style="min-height: 100vh;">
    <div class="container">
        <div class="row align-items-center justify-content-center min-vh-100 py-5">
            <div class="col-lg-11">
                <div class="row g-0 card-modern overflow-hidden">
                    <!-- Côté gauche - Informations -->
                    <div class="col-lg-5 bg-gradient-secondary text-white p-5 d-flex flex-column justify-content-center">
                        <div data-aos="fade-right">
                            <h2 class="display-6 fw-bold mb-4">
                                Rejoignez la Révolution de l'Épargne !
                            </h2>
                            <p class="lead mb-4">
                                Créez votre compte SamalSakom et découvrez une nouvelle façon 
                                d'épargner, sécurisée et moderne.
                            </p>
                            
                            <div class="benefits-list">
                                <div class="benefit-item d-flex align-items-center mb-3">
                                    <div class="benefit-icon bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-gift text-white"></i>
                                    </div>
                                    <span>Inscription 100% gratuite</span>
                                </div>
                                <div class="benefit-item d-flex align-items-center mb-3">
                                    <div class="benefit-icon bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-rocket text-white"></i>
                                    </div>
                                    <span>Activation immédiate</span>
                                </div>
                                <div class="benefit-item d-flex align-items-center mb-3">
                                    <div class="benefit-icon bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                    <span>Rejoignez 1000+ utilisateurs</span>
                                </div>
                                <div class="benefit-item d-flex align-items-center mb-3">
                                    <div class="benefit-icon bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-medal text-white"></i>
                                    </div>
                                    <span>Bonus de bienvenue</span>
                                </div>
                            </div>
                            
                            <div class="mt-5">
                                <p class="mb-2">Déjà membre ?</p>
                                <a href="login.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Côté droit - Formulaire -->
                    <div class="col-lg-7 p-5">
                        <div data-aos="fade-left">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold text-gradient">Créer votre Compte</h3>
                                <p class="text-muted">Quelques informations pour commencer</p>
                            </div>
                            
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                    <div class="mt-2">
                                        <small>Redirection automatique vers la page de connexion...</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
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
                            
                            <form method="POST" action="" class="register-form">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom *</label>
                                        <input type="text" class="form-control" id="nom" name="nom" 
                                               value="<?php echo htmlspecialchars($nom ?? ''); ?>" 
                                               placeholder="Votre nom de famille" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" 
                                               value="<?php echo htmlspecialchars($prenom ?? ''); ?>" 
                                               placeholder="Votre prénom" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                               placeholder="votre@email.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone *</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" 
                                               value="<?php echo htmlspecialchars($telephone ?? ''); ?>" 
                                               placeholder="77 123 45 67" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mot_de_passe" class="form-label">Mot de passe *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="mot_de_passe" 
                                                   name="mot_de_passe" placeholder="••••••••" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Au moins 6 caractères</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" placeholder="••••••••" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_naissance" class="form-label">Date de naissance *</label>
                                        <input type="date" class="form-control" id="date_naissance" 
                                               name="date_naissance" 
                                               value="<?php echo htmlspecialchars($date_naissance ?? ''); ?>" 
                                               max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="adresse" class="form-label">Ville/Région</label>
                                        <select class="form-select" id="adresse" name="adresse">
                                            <option value="">Choisir votre région</option>
                                            <option value="Dakar" <?php echo (isset($adresse) && $adresse === 'Dakar') ? 'selected' : ''; ?>>Dakar</option>
                                            <option value="Thiès" <?php echo (isset($adresse) && $adresse === 'Thiès') ? 'selected' : ''; ?>>Thiès</option>
                                            <option value="Saint-Louis" <?php echo (isset($adresse) && $adresse === 'Saint-Louis') ? 'selected' : ''; ?>>Saint-Louis</option>
                                            <option value="Diourbel" <?php echo (isset($adresse) && $adresse === 'Diourbel') ? 'selected' : ''; ?>>Diourbel</option>
                                            <option value="Kaolack" <?php echo (isset($adresse) && $adresse === 'Kaolack') ? 'selected' : ''; ?>>Kaolack</option>
                                            <option value="Tambacounda" <?php echo (isset($adresse) && $adresse === 'Tambacounda') ? 'selected' : ''; ?>>Tambacounda</option>
                                            <option value="Ziguinchor" <?php echo (isset($adresse) && $adresse === 'Ziguinchor') ? 'selected' : ''; ?>>Ziguinchor</option>
                                            <option value="Louga" <?php echo (isset($adresse) && $adresse === 'Louga') ? 'selected' : ''; ?>>Louga</option>
                                            <option value="Fatick" <?php echo (isset($adresse) && $adresse === 'Fatick') ? 'selected' : ''; ?>>Fatick</option>
                                            <option value="Kolda" <?php echo (isset($adresse) && $adresse === 'Kolda') ? 'selected' : ''; ?>>Kolda</option>
                                            <option value="Matam" <?php echo (isset($adresse) && $adresse === 'Matam') ? 'selected' : ''; ?>>Matam</option>
                                            <option value="Kaffrine" <?php echo (isset($adresse) && $adresse === 'Kaffrine') ? 'selected' : ''; ?>>Kaffrine</option>
                                            <option value="Kédougou" <?php echo (isset($adresse) && $adresse === 'Kédougou') ? 'selected' : ''; ?>>Kédougou</option>
                                            <option value="Sédhiou" <?php echo (isset($adresse) && $adresse === 'Sédhiou') ? 'selected' : ''; ?>>Sédhiou</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            J'accepte les <a href="#" class="text-decoration-none">Conditions d'Utilisation</a> 
                                            et la <a href="#" class="text-decoration-none">Politique de Confidentialité</a> *
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                        <label class="form-check-label" for="newsletter">
                                            Je souhaite recevoir les actualités et offres spéciales de SamalSakom
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-secondary-custom w-100 btn-lg mt-4 mb-4">
                                    <i class="fas fa-user-plus me-2"></i>Créer mon Compte
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <div class="divider my-4">
                                    <span class="divider-text text-muted">ou</span>
                                </div>
                                
                                <div class="social-register d-grid gap-2">
                                    <button class="btn btn-outline-secondary">
                                        <i class="fab fa-google me-2"></i>S'inscrire avec Google
                                    </button>
                                    <button class="btn btn-outline-secondary">
                                        <i class="fab fa-facebook-f me-2"></i>S'inscrire avec Facebook
                                    </button>
                                </div>
                                
                                <div class="mt-4">
                                    <p class="text-muted mb-0">
                                        Déjà membre ? 
                                        <a href="login.php" class="text-decoration-none fw-bold">
                                            Se connecter
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

.form-control:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
}

.social-register .btn {
    border-radius: 50px;
    padding: 12px 20px;
}

.benefit-item {
    transition: var(--transition);
}

.benefit-item:hover {
    transform: translateX(5px);
}

@media (max-width: 768px) {
    .hero-section .row.g-0 {
        flex-direction: column-reverse;
    }
    
    .hero-section .col-lg-5 {
        padding: 2rem !important;
    }
    
    .hero-section .col-lg-7 {
        padding: 2rem !important;
    }
    
    .display-6 {
        font-size: 1.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    
    setupPasswordToggle('togglePassword1', 'mot_de_passe');
    setupPasswordToggle('togglePassword2', 'confirm_password');
    
    // Auto-format phone number
    const phoneInput = document.getElementById('telephone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value.startsWith('221')) {
                value = value.substring(3);
            }
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + ' ' + value.substring(2, 5) + ' ' + value.substring(5, 7) + ' ' + value.substring(7, 9);
            }
            
            this.value = value.trim();
        });
    }
    
    // Password strength indicator
    const passwordInput = document.getElementById('mot_de_passe');
    const confirmInput = document.getElementById('confirm_password');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            // Remove existing strength indicator
            let indicator = this.parentNode.parentNode.querySelector('.password-strength');
            if (indicator) {
                indicator.remove();
            }
            
            if (password.length > 0) {
                indicator = document.createElement('div');
                indicator.className = 'password-strength mt-1';
                
                const strengthBar = document.createElement('div');
                strengthBar.className = 'progress';
                strengthBar.style.height = '4px';
                
                const strengthFill = document.createElement('div');
                strengthFill.className = `progress-bar bg-${strength.color}`;
                strengthFill.style.width = `${strength.percentage}%`;
                
                strengthBar.appendChild(strengthFill);
                indicator.appendChild(strengthBar);
                
                const strengthText = document.createElement('small');
                strengthText.className = `text-${strength.color} mt-1`;
                strengthText.textContent = strength.text;
                indicator.appendChild(strengthText);
                
                this.parentNode.parentNode.appendChild(indicator);
            }
        });
    }
    
    // Password confirmation validation
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
    
    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 6) score += 1;
        if (password.length >= 8) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        if (score <= 2) {
            return { percentage: 33, color: 'danger', text: 'Faible' };
        } else if (score <= 4) {
            return { percentage: 66, color: 'warning', text: 'Moyen' };
        } else {
            return { percentage: 100, color: 'success', text: 'Fort' };
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
