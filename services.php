<?php
$page_title = "Services";
include 'includes/header.php';

// Récupération des formules depuis la base de données
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Récupérer les formules actives avec leurs fonctionnalités
    $formules_query = "SELECT * FROM formules_services 
                       WHERE statut = 'actif' 
                       ORDER BY ordre_affichage ASC, id ASC";
    $stmt = $db->prepare($formules_query);
    $stmt->execute();
    $formules = $stmt->fetchAll();
    
    // Récupérer toutes les fonctionnalités
    $fonctionnalites_query = "SELECT ff.*, fs.nom as formule_nom 
                              FROM formule_fonctionnalites ff
                              JOIN formules_services fs ON ff.formule_id = fs.id
                              WHERE fs.statut = 'actif'
                              ORDER BY ff.formule_id, ff.ordre_affichage ASC";
    $stmt = $db->prepare($fonctionnalites_query);
    $stmt->execute();
    $all_fonctionnalites = $stmt->fetchAll();
    
    // Organiser les fonctionnalités par formule
    $fonctionnalites_by_formule = [];
    foreach ($all_fonctionnalites as $fonc) {
        $fonctionnalites_by_formule[$fonc['formule_id']][] = $fonc;
    }
    
} catch (PDOException $e) {
    // En cas d'erreur, utiliser les données par défaut
    $formules = [
        ['id' => 1, 'nom' => 'Basique', 'prix' => 0, 'description' => 'Pour débuter', 'couleur' => 'outline-primary', 'populaire' => 0],
        ['id' => 2, 'nom' => 'Premium', 'prix' => 2500, 'description' => 'Par mois', 'couleur' => 'primary', 'populaire' => 1],
        ['id' => 3, 'nom' => 'Business', 'prix' => 5000, 'description' => 'Par mois', 'couleur' => 'secondary', 'populaire' => 0]
    ];
    $fonctionnalites_by_formule = [];
}
?>

<!-- Hero Section Services -->
<section class="hero-section" style="min-height: 60vh;">
    <div class="container">
        <div class="row align-items-center justify-content-center text-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">Nos <span class="text-gradient">Services</span></h1>
                <p class="hero-subtitle">
                    Découvrez notre gamme complète de services financiers innovants 
                    conçus pour transformer votre expérience d'épargne collective.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Main Services Section -->
<section class="section-padding">
    <div class="container">
        <div class="row g-5">
            <!-- Tontines Digitales -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="service-card card-modern p-5 h-100">
                    <div class="service-icon mb-4">
                        <div class="card-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    <h3 class="mb-3">Tontines Digitales</h3>
                    <p class="text-muted mb-4">
                        Créez et gérez vos tontines en ligne avec une sécurité bancaire. 
                        Invitez vos proches, définissez les règles et suivez les contributions en temps réel.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Création de groupes personnalisés</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Gestion automatisée des tours</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Notifications SMS automatiques</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Historique complet des transactions</li>
                    </ul>
                </div>
            </div>

            <!-- Épargne Automatique -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="service-card card-modern p-5 h-100">
                    <div class="service-icon mb-4">
                        <div class="card-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                    </div>
                    <h3 class="mb-3">Épargne Automatique</h3>
                    <p class="text-muted mb-4">
                        Programmez vos épargnes selon vos objectifs. Notre système intelligent 
                        prélève automatiquement les montants définis à la fréquence choisie.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Prélèvements programmés</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Objectifs d'épargne personnalisés</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Suivi des progrès en temps réel</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Rappels et motivations</li>
                    </ul>
                </div>
            </div>

            <!-- Microcrédits -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
                <div class="service-card card-modern p-5 h-100">
                    <div class="service-icon mb-4">
                        <div class="card-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    <h3 class="mb-3">Microcrédits</h3>
                    <p class="text-muted mb-4">
                        Accédez à des prêts basés sur votre historique d'épargne. 
                        Plus vous épargnez régulièrement, plus votre capacité d'emprunt augmente.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Évaluation basée sur l'historique</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Taux préférentiels</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Remboursement flexible</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Approbation rapide</li>
                    </ul>
                </div>
            </div>

            <!-- Éducation Financière -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
                <div class="service-card card-modern p-5 h-100">
                    <div class="service-icon mb-4">
                        <div class="card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <h3 class="mb-3">Éducation Financière</h3>
                    <p class="text-muted mb-4">
                        Développez vos compétences financières avec nos modules de formation 
                        adaptés au contexte sénégalais et aux réalités locales.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cours interactifs en français et wolof</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Conseils personnalisés</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Webinaires avec experts</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificats de formation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Comparison -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <h2 class="section-title">Comparaison des Formules</h2>
                <p class="section-subtitle">
                    Choisissez la formule qui correspond le mieux à vos besoins
                </p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <?php 
            $delay = 100;
            foreach ($formules as $formule): 
                $fonctionnalites = isset($fonctionnalites_by_formule[$formule['id']]) ? $fonctionnalites_by_formule[$formule['id']] : [];
            ?>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                <div class="pricing-card card-modern p-4 text-center h-100 <?php echo $formule['populaire'] ? 'position-relative' : ''; ?>">
                    <?php if ($formule['populaire']): ?>
                    <div class="popular-badge position-absolute top-0 start-50 translate-middle">
                        <span class="badge bg-gradient-primary px-3 py-2">Populaire</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pricing-header mb-4 <?php echo $formule['populaire'] ? 'mt-3' : ''; ?>">
                        <h4 class="fw-bold"><?php echo htmlspecialchars($formule['nom']); ?></h4>
                        <div class="price display-4 fw-bold text-gradient mb-2">
                            <?php if ($formule['prix'] == 0): ?>
                                Gratuit
                            <?php else: ?>
                                <?php echo number_format($formule['prix'], 0, ',', ' '); ?> <?php echo $formule['devise']; ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted"><?php echo htmlspecialchars($formule['description']); ?></p>
                    </div>
                    
                    <ul class="list-unstyled mb-4">
                        <?php foreach ($fonctionnalites as $fonc): ?>
                        <li class="mb-3">
                            <i class="<?php echo $fonc['inclus'] ? 'fas fa-check text-success' : 'fas fa-times text-muted'; ?> me-2"></i>
                            <?php echo htmlspecialchars($fonc['nom']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php
                    $btn_class = 'btn-' . $formule['couleur'];
                    $btn_text = $formule['prix'] == 0 ? 'Commencer' : 'Choisir ' . $formule['nom'];
                    $btn_link = $formule['prix'] == 0 ? 'register.php' : 'contact.php';
                    ?>
                    <a href="<?php echo $btn_link; ?>" class="btn <?php echo $btn_class; ?> w-100">
                        <?php echo $btn_text; ?>
                    </a>
                </div>
            </div>
            <?php 
            $delay += 100;
            endforeach; 
            ?>
        </div>
    </div>
</section>

<!-- Payment Methods -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <h2 class="section-title">Moyens de Paiement</h2>
                <p class="section-subtitle">
                    Nous acceptons tous les moyens de paiement populaires au Sénégal
                </p>
            </div>
        </div>

        <div class="row g-4 justify-content-center align-items-center">
            <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-delay="100">
                <div class="payment-method text-center p-3">
                    <div class="payment-icon bg-gradient-primary text-white rounded-3 p-3 d-inline-block mb-2">
                        <i class="fas fa-mobile-alt" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-0">Orange Money</h6>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-delay="200">
                <div class="payment-method text-center p-3">
                    <div class="payment-icon bg-gradient-secondary text-white rounded-3 p-3 d-inline-block mb-2">
                        <i class="fas fa-wave-square" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-0">Wave</h6>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-delay="300">
                <div class="payment-method text-center p-3">
                    <div class="payment-icon text-white rounded-3 p-3 d-inline-block mb-2" style="background: linear-gradient(135deg, #1f4e79 0%, #2e5984 100%);">
                        <i class="fab fa-cc-visa" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-0">Visa</h6>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-delay="400">
                <div class="payment-method text-center p-3">
                    <div class="payment-icon text-white rounded-3 p-3 d-inline-block mb-2" style="background: linear-gradient(135deg, #eb001b 0%, #f79e1b 100%);">
                        <i class="fab fa-cc-mastercard" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-0">Mastercard</h6>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6" data-aos="fade-up" data-aos-delay="500">
                <div class="payment-method text-center p-3">
                    <div class="payment-icon text-white rounded-3 p-3 d-inline-block mb-2" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">
                        <i class="fas fa-university" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-0">Virement</h6>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h2 class="section-title text-start">Sécurité de Niveau Bancaire</h2>
                <p class="lead mb-4">
                    Vos fonds et données personnelles sont protégés par les mêmes 
                    technologies utilisées par les plus grandes banques internationales.
                </p>
                <div class="security-features">
                    <div class="security-item d-flex align-items-start mb-4">
                        <div class="security-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h5 class="mb-2">Chiffrement SSL 256-bit</h5>
                            <p class="text-muted mb-0">Toutes vos données sont chiffrées lors des transmissions</p>
                        </div>
                    </div>

                    <div class="security-item d-flex align-items-start mb-4">
                        <div class="security-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-2">Authentification à Deux Facteurs</h5>
                            <p class="text-muted mb-0">Protection renforcée de votre compte avec SMS</p>
                        </div>
                    </div>

                    <div class="security-item d-flex align-items-start mb-4">
                        <div class="security-icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <h5 class="mb-2">Surveillance 24/7</h5>
                            <p class="text-muted mb-0">Monitoring continu pour détecter toute activité suspecte</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left">
                <div class="security-visual text-center">
                    <div class="bg-gradient-primary rounded-4 p-5 d-inline-block">
                        <i class="fas fa-shield-alt text-white" style="font-size: 8rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <h2 class="section-title">Questions Fréquentes</h2>
                <p class="section-subtitle">
                    Trouvez rapidement les réponses à vos questions
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Comment fonctionne une tontine digitale ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Une tontine digitale fonctionne comme une tontine traditionnelle, mais en ligne. Les participants cotisent régulièrement et récupèrent à tour de rôle la cagnotte totale. Notre plateforme automatise la gestion, sécurise les fonds et envoie des notifications.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Mes fonds sont-ils sécurisés ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolument ! Nous utilisons un chiffrement de niveau bancaire, une authentification à deux facteurs et nos partenaires financiers sont agréés par la BCEAO. Vos fonds sont séparés de nos comptes d'entreprise.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="300">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Quels sont les frais de service ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                La formule Basique est entièrement gratuite. Les formules Premium (2 500 FCFA/mois) et Business (5 000 FCFA/mois) offrent des fonctionnalités avancées. Aucun frais caché !
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 mb-3" data-aos="fade-up" data-aos-delay="400">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Puis-je utiliser SamalSakom sans smartphone ?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oui ! Notre plateforme est accessible depuis n'importe quel téléphone avec internet. Nous envoyons aussi des notifications par SMS pour vous tenir informé même avec un téléphone basique.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section-padding bg-gradient-primary text-white">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8" data-aos="zoom-in">
                <h2 class="display-5 fw-bold mb-4">
                    Prêt à Commencer ?
                </h2>
                <p class="lead mb-5">
                    Choisissez la formule qui vous convient et commencez à épargner 
                    intelligemment dès aujourd'hui !
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="register.php" class="btn btn-light btn-lg px-5">
                        <i class="fas fa-rocket me-2"></i>Commencer Gratuitement
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="fas fa-phone me-2"></i>Parler à un Expert
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
