/* 
 * SamalSakom Dashboard - Scripts JavaScript
 * Fonctionnalités interactives et AJAX
 */

// Variables globales
let currentModal = null;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Charger les notifications au démarrage
    loadNotifications();
    
    // Actualiser les notifications toutes les 30 secondes
    setInterval(loadNotifications, 30000);
    
    // Initialiser la recherche rapide
    initializeQuickSearch();
    
    // Initialiser les animations
    initializeAnimations();
}

// Gestion des notifications
function loadNotifications() {
    fetch('actions/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
                updateNotificationList(data.notifications);
            }
        })
        .catch(error => console.error('Erreur chargement notifications:', error));
}

function updateNotificationBadge(count) {
    const badges = document.querySelectorAll('.notification-badge');
    badges.forEach(badge => {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    });
}

function updateNotificationList(notifications) {
    const notificationList = document.getElementById('notificationList');
    if (notificationList) {
        if (notifications.length > 0) {
            notificationList.innerHTML = notifications.map(notif => `
                <div class="notification-item ${notif.lu == 0 ? 'unread' : ''}" data-id="${notif.id}">
                    <div class="notification-icon bg-${getNotificationColor(notif.type)}">
                        <i class="fas ${getNotificationIcon(notif.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.titre}</div>
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${formatTimeAgo(notif.date_creation)}</div>
                    </div>
                    ${notif.lu == 0 ? '<div class="notification-dot"></div>' : ''}
                </div>
            `).join('');
            
            // Ajouter les événements de clic
            notificationList.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function() {
                    markNotificationAsRead(this.dataset.id);
                });
            });
        } else {
            notificationList.innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Aucune notification</p>
                </div>
            `;
        }
    }
}

function markNotificationAsRead(notificationId) {
    fetch('actions/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Recharger les notifications
        }
    })
    .catch(error => console.error('Erreur marquage notification:', error));
}

function getNotificationColor(type) {
    switch(type) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        case 'info': return 'info';
        default: return 'primary';
    }
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'fa-check-circle';
        case 'warning': return 'fa-exclamation-triangle';
        case 'error': return 'fa-times-circle';
        case 'info': return 'fa-info-circle';
        case 'payment': return 'fa-credit-card';
        case 'tontine': return 'fa-piggy-bank';
        default: return 'fa-bell';
    }
}

// Recherche rapide
function initializeQuickSearch() {
    const quickSearch = document.getElementById('quickSearch');
    if (quickSearch) {
        let searchTimeout;
        
        quickSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performQuickSearch(query);
                }, 300);
            } else {
                hideSearchResults();
            }
        });
        
        // Fermer les résultats quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!quickSearch.contains(e.target)) {
                hideSearchResults();
            }
        });
    }
}

function performQuickSearch(query) {
    fetch(`actions/quick_search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSearchResults(data.results);
            }
        })
        .catch(error => console.error('Erreur recherche:', error));
}

function showSearchResults(results) {
    let resultsContainer = document.getElementById('searchResults');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.id = 'searchResults';
        resultsContainer.className = 'search-results';
        document.querySelector('.header-search').appendChild(resultsContainer);
    }
    
    if (results.length > 0) {
        resultsContainer.innerHTML = results.map(result => `
            <div class="search-result-item" onclick="window.location.href='${result.url}'">
                <div class="search-result-icon">
                    <i class="fas ${result.icon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-description">${result.description}</div>
                </div>
            </div>
        `).join('');
    } else {
        resultsContainer.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search"></i>
                <span>Aucun résultat trouvé</span>
            </div>
        `;
    }
    
    resultsContainer.style.display = 'block';
}

function hideSearchResults() {
    const resultsContainer = document.getElementById('searchResults');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

// Gestion des formulaires de paiement
function processPaiement(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Désactiver le bouton et afficher le loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
    
    fetch('actions/process_paiement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Fermer le modal après 2 secondes
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('paiementModal'));
                if (modal) {
                    modal.hide();
                }
                // Recharger la page pour mettre à jour les données
                location.reload();
            }, 2000);
        } else {
            showToast(data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showToast('Erreur lors du traitement du paiement', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Animations et effets visuels
function initializeAnimations() {
    // Animation des cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-slide-up');
    });
    
    // Animation des éléments au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, observerOptions);
    
    // Observer les éléments avec data-aos
    document.querySelectorAll('[data-aos]').forEach(el => {
        observer.observe(el);
    });
}

// Utilitaires
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'À l\'instant';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    return date.toLocaleDateString('fr-FR', { ...defaultOptions, ...options });
}

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    showToast('Une erreur inattendue s\'est produite', 'error');
});

// Gestion des erreurs de fetch
window.addEventListener('unhandledrejection', function(e) {
    console.error('Promesse rejetée:', e.reason);
    showToast('Erreur de connexion', 'error');
});

// Styles CSS pour les composants JavaScript
const dynamicStyles = `
<style>
.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: var(--transition-fast);
}

.search-result-item:hover {
    background: var(--gray-50);
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.search-result-content {
    flex: 1;
}

.search-result-title {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.search-result-description {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.search-no-results {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--gray-500);
    gap: 0.5rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: var(--transition-fast);
    position: relative;
}

.notification-item:hover {
    background: var(--gray-50);
}

.notification-item.unread {
    background: rgba(46, 139, 87, 0.05);
    border-left: 3px solid var(--primary-color);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 1rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.9rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.notification-dot {
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    position: absolute;
    top: 1rem;
    right: 1rem;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slideUp 0.6s ease-out forwards;
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>
`;

// Injecter les styles dynamiques
document.head.insertAdjacentHTML('beforeend', dynamicStyles);
