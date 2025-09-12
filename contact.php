<?php
$page_title = "Contact";
include 'includes/header.php';

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (empty($sujet)) $errors[] = "Le sujet est obligatoire";
    if (empty($message)) $errors[] = "Le message est obligatoire";
    
    if (empty($errors)) {
        try {
            $query = "INSERT INTO contacts (nom, email, telephone, sujet, message) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$nom, $email, $telephone, $sujet, $message]);
            
            $success_message = "Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.";
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'envoi du message. Veuillez réessayer.";
        }
    }
}
?>

<!-- Hero Section Contact -->
<section class="hero-section" style="min-height: 60vh;">
    <div class="container">
        <div class="row align-items-center justify-content-center text-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">Contactez <span class="text-gradient">SamalSakom</span></h1>
                <p class="hero-subtitle">
                    Notre équipe est là pour répondre à toutes vos questions 
                    et vous accompagner dans votre parcours d'épargne.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Formulaire de contact -->
            <div class="col-lg-8" data-aos="fade-right">
                <div class="card-modern p-5">
                    <h3 class="mb-4">Envoyez-nous un Message</h3>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                    
                    <form method="POST" action="" class="contact-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?php echo htmlspecialchars($nom ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?php echo htmlspecialchars($telephone ?? ''); ?>" 
                                       placeholder="77 123 45 67">
                            </div>
                            <div class="col-md-6">
                                <label for="sujet" class="form-label">Sujet *</label>
                                <select class="form-select" id="sujet" name="sujet" required>
                                    <option value="">Choisissez un sujet</option>
                                    <option value="Information générale" <?php echo (isset($sujet) && $sujet === 'Information générale') ? 'selected' : ''; ?>>Information générale</option>
                                    <option value="Support technique" <?php echo (isset($sujet) && $sujet === 'Support technique') ? 'selected' : ''; ?>>Support technique</option>
                                    <option value="Problème de paiement" <?php echo (isset($sujet) && $sujet === 'Problème de paiement') ? 'selected' : ''; ?>>Problème de paiement</option>
                                    <option value="Suggestion d'amélioration" <?php echo (isset($sujet) && $sujet === 'Suggestion d\'amélioration') ? 'selected' : ''; ?>>Suggestion d'amélioration</option>
                                    <option value="Partenariat" <?php echo (isset($sujet) && $sujet === 'Partenariat') ? 'selected' : ''; ?>>Partenariat</option>
                                    <option value="Autre" <?php echo (isset($sujet) && $sujet === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          placeholder="Décrivez votre demande en détail..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary-custom btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer le Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Informations de contact -->
            <div class="col-lg-4" data-aos="fade-left">
                <div class="contact-info">
                    <h3 class="mb-4">Nos Coordonnées</h3>
                    
                    <div class="contact-item d-flex align-items-start mb-4">
                        <div class="contact-icon bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Adresse</h5>
                            <p class="text-muted mb-0">
                                Dakar, Plateau<br>
                                Rue de la République<br>
                                Sénégal
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-item d-flex align-items-start mb-4">
                        <div class="contact-icon bg-gradient-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Téléphone</h5>
                            <p class="text-muted mb-0">
                                <a href="tel:+221771234567" class="text-decoration-none">+221 77 123 45 67</a><br>
                                <a href="tel:+221781234567" class="text-decoration-none">+221 78 123 45 67</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-item d-flex align-items-start mb-4">
                        <div class="contact-icon text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Email</h5>
                            <p class="text-muted mb-0">
                                <a href="mailto:contact@samalsakom.sn" class="text-decoration-none">contact@samalsakom.sn</a><br>
                                <a href="mailto:support@samalsakom.sn" class="text-decoration-none">support@samalsakom.sn</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-item d-flex align-items-start mb-4">
                        <div class="contact-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Horaires</h5>
                            <p class="text-muted mb-0">
                                Lundi - Vendredi: 8h - 18h<br>
                                Samedi: 9h - 14h<br>
                                Dimanche: Fermé
                            </p>
                        </div>
                    </div>
                    
                    <!-- Réseaux sociaux -->
                    <div class="social-section mt-5">
                        <h5 class="mb-3">Suivez-nous</h5>
                        <div class="social-links d-flex gap-3">
                            <a href="#" class="social-link bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link bg-gradient-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #E4405F 0%, #F56040 100%);">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #0077B5 0%, #00A0DC 100%);">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <h2 class="section-title">Questions Fréquentes</h2>
                <p class="section-subtitle">
                    Trouvez rapidement les réponses aux questions les plus courantes
                </p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="contactFaqAccordion">
                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contactFaq1">
                                Comment puis-je créer mon compte ?
                            </button>
                        </h2>
                        <div id="contactFaq1" class="accordion-collapse collapse" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body">
                                Cliquez sur "Inscription" en haut de la page, remplissez le formulaire avec vos informations personnelles et votre numéro de téléphone. Vous recevrez un SMS de confirmation pour activer votre compte.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contactFaq2">
                                Que faire si j'oublie mon mot de passe ?
                            </button>
                        </h2>
                        <div id="contactFaq2" class="accordion-collapse collapse" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body">
                                Sur la page de connexion, cliquez sur "Mot de passe oublié". Saisissez votre email ou numéro de téléphone et suivez les instructions envoyées par SMS ou email pour réinitialiser votre mot de passe.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="300">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contactFaq3">
                                Comment contacter le support en urgence ?
                            </button>
                        </h2>
                        <div id="contactFaq3" class="accordion-collapse collapse" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body">
                                Pour les urgences (problème de sécurité, transaction bloquée), appelez directement notre hotline au +221 77 123 45 67. Notre équipe est disponible 24h/24 pour les situations critiques.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="400">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contactFaq4">
                                Puis-je modifier mes informations personnelles ?
                            </button>
                        </h2>
                        <div id="contactFaq4" class="accordion-collapse collapse" data-bs-parent="#contactFaqAccordion">
                            <div class="accordion-body">
                                Oui, connectez-vous à votre compte et accédez à la section "Mon Profil". Vous pouvez modifier vos informations personnelles, votre photo de profil et vos préférences de notification.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Support Channels -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <h2 class="section-title">Autres Moyens de Nous Contacter</h2>
                <p class="section-subtitle">
                    Choisissez le canal qui vous convient le mieux
                </p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card-modern p-4 text-center h-100">
                    <div class="card-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h4 class="mb-3">WhatsApp</h4>
                    <p class="text-muted mb-4">
                        Contactez-nous directement via WhatsApp pour un support rapide et personnalisé.
                    </p>
                    <a href="https://wa.me/221771234567" class="btn btn-outline-custom" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i>Ouvrir WhatsApp
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card-modern p-4 text-center h-100">
                    <div class="card-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4 class="mb-3">Chat en Direct</h4>
                    <p class="text-muted mb-4">
                        Discutez en temps réel avec notre équipe support directement depuis votre compte.
                    </p>
                    <a href="login.php" class="btn btn-outline-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card-modern p-4 text-center h-100">
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h4 class="mb-3">Centre d'Aide</h4>
                    <p class="text-muted mb-4">
                        Consultez notre base de connaissances avec guides détaillés et tutoriels vidéo.
                    </p>
                    <a href="#" class="btn btn-outline-custom">
                        <i class="fas fa-external-link-alt me-2"></i>Visiter le Centre
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
