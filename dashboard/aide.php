<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">
                        <i class="fas fa-question-circle me-3"></i>
                        Centre d'Aide
                    </h1>
                    <p class="page-description">Trouvez des réponses à vos questions et obtenez de l'assistance</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" onclick="ouvrirTicket()">
                        <i class="fas fa-headset me-2"></i>
                        Contacter le Support
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recherche rapide -->
            <div class="col-12 mb-4">
                <div class="card modern-card">
                    <div class="card-body text-center">
                        <h4 class="mb-3">Comment pouvons-nous vous aider ?</h4>
                        <div class="search-help-container">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchHelp" 
                                       placeholder="Rechercher dans l'aide..." onkeyup="rechercherAide()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="col-lg-4 mb-4">
                <div class="card modern-card h-100">
                    <div class="card-body text-center">
                        <div class="help-icon mb-3">
                            <i class="fas fa-piggy-bank fa-3x text-primary"></i>
                        </div>
                        <h5>Comprendre les Tontines</h5>
                        <p class="text-muted">Découvrez comment fonctionnent les tontines et leurs avantages</p>
                        <button class="btn btn-outline-primary" onclick="afficherSection('tontines')">
                            En savoir plus
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card modern-card h-100">
                    <div class="card-body text-center">
                        <div class="help-icon mb-3">
                            <i class="fas fa-credit-card fa-3x text-success"></i>
                        </div>
                        <h5>Paiements & Cotisations</h5>
                        <p class="text-muted">Guide pour effectuer vos paiements en toute sécurité</p>
                        <button class="btn btn-outline-success" onclick="afficherSection('paiements')">
                            Voir le guide
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card modern-card h-100">
                    <div class="card-body text-center">
                        <div class="help-icon mb-3">
                            <i class="fas fa-shield-alt fa-3x text-warning"></i>
                        </div>
                        <h5>Sécurité & Confidentialité</h5>
                        <p class="text-muted">Protégez votre compte et vos données personnelles</p>
                        <button class="btn btn-outline-warning" onclick="afficherSection('securite')">
                            Conseils sécurité
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>
                            Questions Fréquemment Posées
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <!-- FAQ Tontines -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Qu'est-ce qu'une tontine et comment ça fonctionne ?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Une tontine est un système d'épargne collective où plusieurs personnes cotisent régulièrement une somme fixe. À tour de rôle, chaque participant reçoit la totalité des cotisations collectées.</p>
                                        <p><strong>Exemple :</strong> 10 personnes cotisent 50 000 FCFA par mois. Chaque mois, une personne différente reçoit 500 000 FCFA.</p>
                                        <p><strong>Avantages :</strong></p>
                                        <ul>
                                            <li>Épargne forcée et disciplinée</li>
                                            <li>Accès à une somme importante sans intérêts</li>
                                            <li>Solidarité et entraide communautaire</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Paiements -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Quels sont les moyens de paiement acceptés ?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>SamalSakom accepte plusieurs moyens de paiement sécurisés :</p>
                                        <ul>
                                            <li><strong>Orange Money :</strong> Paiement mobile rapide et sécurisé</li>
                                            <li><strong>Wave :</strong> Transfert d'argent instantané</li>
                                            <li><strong>Virement bancaire :</strong> Pour les gros montants</li>
                                        </ul>
                                        <p>Tous les paiements sont cryptés et sécurisés. Vous recevrez une confirmation par SMS après chaque transaction.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Sécurité -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Comment protéger mon compte ?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Conseils de sécurité :</strong></p>
                                        <ul>
                                            <li>Utilisez un mot de passe fort et unique</li>
                                            <li>Ne partagez jamais vos identifiants</li>
                                            <li>Déconnectez-vous après chaque session</li>
                                            <li>Vérifiez régulièrement vos transactions</li>
                                            <li>Contactez-nous immédiatement en cas d'activité suspecte</li>
                                        </ul>
                                        <p>SamalSakom utilise le cryptage SSL et ne stocke jamais vos données bancaires.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Participation -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        Comment rejoindre une tontine ?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p><strong>Étapes pour rejoindre une tontine :</strong></p>
                                        <ol>
                                            <li>Parcourez les tontines disponibles dans "Découvrir"</li>
                                            <li>Lisez attentivement les conditions (montant, fréquence, durée)</li>
                                            <li>Cliquez sur "Rejoindre" si vous êtes intéressé</li>
                                            <li>Confirmez votre participation</li>
                                            <li>Effectuez votre première cotisation</li>
                                        </ol>
                                        <p><strong>Important :</strong> Une fois que vous rejoignez une tontine, vous vous engagez à cotiser régulièrement jusqu'à la fin du cycle.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Problèmes -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                        Que faire si mon paiement échoue ?
                                    </button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p><strong>En cas d'échec de paiement :</strong></p>
                                        <ol>
                                            <li>Vérifiez votre solde sur votre compte mobile money</li>
                                            <li>Assurez-vous que votre numéro est correct</li>
                                            <li>Réessayez le paiement après quelques minutes</li>
                                            <li>Si le problème persiste, contactez notre support</li>
                                        </ol>
                                        <p>Vous pouvez également consulter l'historique de vos transactions dans votre portefeuille pour suivre le statut de vos paiements.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact et support -->
            <div class="col-lg-4">
                <div class="card modern-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Besoin d'aide personnalisée ?</h6>
                    </div>
                    <div class="card-body">
                        <div class="contact-method mb-3">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-phone text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Téléphone</div>
                                    <div class="text-muted">+221 77 123 45 67</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-method mb-3">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon me-3">
                                    <i class="fas fa-envelope text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">Email</div>
                                    <div class="text-muted">support@samalsakom.sn</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-method mb-3">
                            <div class="d-flex align-items-center">
                                <div class="contact-icon me-3">
                                    <i class="fab fa-whatsapp text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">WhatsApp</div>
                                    <div class="text-muted">+221 77 123 45 67</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="text-muted mb-3">Horaires d'ouverture</p>
                            <p class="mb-1"><strong>Lun - Ven :</strong> 8h - 18h</p>
                            <p class="mb-0"><strong>Samedi :</strong> 9h - 15h</p>
                        </div>
                    </div>
                </div>

                <!-- Ressources utiles -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Ressources Utiles</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-pdf me-2 text-danger"></i>
                                Guide utilisateur (PDF)
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-video me-2 text-primary"></i>
                                Tutoriels vidéo
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-book me-2 text-success"></i>
                                Conditions générales
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-shield-alt me-2 text-warning"></i>
                                Politique de confidentialité
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ticket support -->
<div class="modal fade" id="supportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contacter le Support</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="supportForm">
                    <div class="mb-3">
                        <label class="form-label">Sujet *</label>
                        <select class="form-select" required>
                            <option value="">Choisir un sujet</option>
                            <option value="paiement">Problème de paiement</option>
                            <option value="tontine">Question sur les tontines</option>
                            <option value="compte">Problème de compte</option>
                            <option value="technique">Problème technique</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message *</label>
                        <textarea class="form-control" rows="4" placeholder="Décrivez votre problème en détail..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priorité</label>
                        <select class="form-select">
                            <option value="normale">Normale</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="envoyerTicket()">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.search-help-container {
    max-width: 600px;
    margin: 0 auto;
}

.help-icon {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-icon {
    width: 40px;
    height: 40px;
    background: var(--gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contact-method {
    padding: 0.75rem;
    border-radius: var(--border-radius);
    transition: var(--transition-fast);
}

.contact-method:hover {
    background: var(--gray-50);
}

.accordion-button:not(.collapsed) {
    background-color: rgba(46, 139, 87, 0.1);
    color: var(--primary-color);
}
</style>

<script>
function ouvrirTicket() {
    const modal = new bootstrap.Modal(document.getElementById('supportModal'));
    modal.show();
}

function envoyerTicket() {
    // Simulation d'envoi de ticket
    showToast('Votre demande a été envoyée avec succès. Nous vous répondrons dans les plus brefs délais.', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('supportModal'));
    modal.hide();
    
    // Reset form
    document.getElementById('supportForm').reset();
}

function rechercherAide() {
    const query = document.getElementById('searchHelp').value.toLowerCase();
    const accordionItems = document.querySelectorAll('.accordion-item');
    
    accordionItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(query) || query === '') {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function afficherSection(section) {
    // Ouvrir la section correspondante dans l'accordéon
    let targetId = '';
    switch(section) {
        case 'tontines':
            targetId = 'faq1';
            break;
        case 'paiements':
            targetId = 'faq2';
            break;
        case 'securite':
            targetId = 'faq3';
            break;
    }
    
    if (targetId) {
        const targetElement = document.getElementById(targetId);
        if (targetElement && !targetElement.classList.contains('show')) {
            const button = document.querySelector(`[data-bs-target="#${targetId}"]`);
            if (button) {
                button.click();
            }
        }
        
        // Scroll vers la section
        targetElement.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
