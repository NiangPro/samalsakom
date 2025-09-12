/**
 * SamalSakom - JavaScript Principal
 * Interactions modernes et animations
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialisation des animations AOS
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });

    // Navigation scrolling effect
    const navbar = document.querySelector('.navbar-custom');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scrolling pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Animation des compteurs dans les statistiques
    const animateCounters = () => {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent.replace(/\D/g, ''));
            const increment = target / 100;
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    if (counter.textContent.includes('%')) {
                        counter.textContent = Math.ceil(current) + '%';
                    } else if (counter.textContent.includes('+')) {
                        counter.textContent = Math.ceil(current) + '+';
                    } else {
                        counter.textContent = Math.ceil(current);
                    }
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = counter.textContent; // Restaurer le texte original
                }
            };
            
            updateCounter();
        });
    };

    // Observer pour déclencher l'animation des compteurs
    const statsSection = document.querySelector('.hero-stats');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(statsSection);
    }

    // Validation des formulaires en temps réel
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearError);
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField.call(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });

    // Fonction de validation des champs
    function validateField() {
        const field = this;
        const value = field.value.trim();
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';

        // Suppression des erreurs précédentes
        clearError.call(field);

        // Validation selon le type de champ
        switch (fieldName) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    errorMessage = 'Veuillez saisir une adresse email valide';
                    isValid = false;
                }
                break;
                
            case 'telephone':
                const phoneRegex = /^(\+221|00221)?[7][0-8][0-9]{7}$/;
                if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                    errorMessage = 'Numéro de téléphone sénégalais invalide';
                    isValid = false;
                }
                break;
                
            case 'mot_de_passe':
                if (value.length < 6) {
                    errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
                    isValid = false;
                }
                break;
                
            case 'confirm_password':
                const password = document.querySelector('input[name="mot_de_passe"]');
                if (password && value !== password.value) {
                    errorMessage = 'Les mots de passe ne correspondent pas';
                    isValid = false;
                }
                break;
                
            default:
                if (field.hasAttribute('required') && !value) {
                    errorMessage = 'Ce champ est obligatoire';
                    isValid = false;
                }
        }

        if (!isValid) {
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    // Fonction pour effacer les erreurs
    function clearError() {
        const field = this;
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
        field.classList.remove('is-invalid');
    }

    // Fonction pour afficher les erreurs de champ
    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error text-danger small mt-1';
        errorElement.textContent = message;
        
        field.parentNode.appendChild(errorElement);
    }

    // Système de notifications toast
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        // Container pour les toasts
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
        });
        
        bsToast.show();

        // Suppression automatique après fermeture
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Formatage automatique des numéros de téléphone
    const phoneInputs = document.querySelectorAll('input[name="telephone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            // Formatage pour numéros sénégalais
            if (value.startsWith('221')) {
                value = value.substring(3);
            }
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + ' ' + value.substring(2, 5) + ' ' + value.substring(5, 7) + ' ' + value.substring(7, 9);
            }
            
            this.value = value.trim();
        });
    });

    // Animation des éléments au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec la classe animate-on-scroll
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Gestion des modales
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.style.overflow = 'auto';
        });
    });

    // Préchargement des images importantes
    const preloadImages = [
        'assets/images/hero-bg.jpg',
        'assets/images/features-bg.jpg'
    ];

    preloadImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });

    // Gestion du mode sombre (optionnel)
    const darkModeToggle = document.querySelector('#darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        // Restaurer le mode sombre au chargement
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }

    // Lazy loading pour les images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Gestion des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gestion des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Fonction utilitaire pour les requêtes AJAX
    window.ajaxRequest = function(url, method = 'GET', data = null) {
        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data ? JSON.stringify(data) : null
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .catch(error => {
            showNotification('Une erreur est survenue: ' + error.message, 'error');
            throw error;
        });
    };

    // Fonction pour formater les montants
    window.formatCurrency = function(amount) {
        return new Intl.NumberFormat('fr-SN', {
            style: 'currency',
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    };

    // Fonction pour formater les dates
    window.formatDate = function(date) {
        return new Intl.DateTimeFormat('fr-SN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    };

    console.log('SamalSakom JavaScript initialisé avec succès! 🚀');
});
