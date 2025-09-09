/**
 * SamalSakom Admin Dashboard - JavaScript
 * Gestion des interactions et animations
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Éléments du DOM
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const adminContent = document.querySelector('.admin-content');
    
    // Toggle sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                // Mobile: show/hide sidebar
                sidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            } else {
                // Desktop: collapse/expand sidebar
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            }
        });
    }
    
    // Fermer sidebar sur mobile en cliquant sur overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Gestion responsive
    function handleResize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
        } else {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Animation des cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-slide-in');
    });
    
    // Animation des compteurs
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-value');
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent.replace(/\D/g, ''));
            const increment = target / 100;
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    if (counter.textContent.includes('M')) {
                        counter.textContent = (current / 1000000).toFixed(1) + 'M';
                    } else if (counter.textContent.includes('K')) {
                        counter.textContent = (current / 1000).toFixed(1) + 'K';
                    } else if (counter.textContent.includes('%')) {
                        counter.textContent = Math.ceil(current) + '%';
                    } else if (counter.textContent.includes('+')) {
                        counter.textContent = Math.ceil(current) + '+';
                    } else {
                        counter.textContent = Math.ceil(current).toLocaleString();
                    }
                    requestAnimationFrame(updateCounter);
                }
            };
            
            updateCounter();
        });
    }
    
    // Observer pour déclencher l'animation des compteurs
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                statsObserver.unobserve(entry.target);
            }
        });
    });
    
    const statsGrid = document.querySelector('.stats-grid');
    if (statsGrid) {
        statsObserver.observe(statsGrid);
    }
    
    // Recherche globale
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        let searchTimeout;
        
        globalSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performGlobalSearch(query);
                }, 300);
            }
        });
    }
    
    function performGlobalSearch(query) {
        // Recherche dans les tables visibles
        const tables = document.querySelectorAll('.admin-table tbody tr');
        let visibleCount = 0;
        
        tables.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        
        // Afficher le nombre de résultats
        updateSearchResults(visibleCount, tables.length);
    }
    
    function updateSearchResults(visible, total) {
        let resultsInfo = document.querySelector('.search-results');
        
        if (!resultsInfo) {
            resultsInfo = document.createElement('div');
            resultsInfo.className = 'search-results alert alert-info mt-3';
            const firstTable = document.querySelector('.data-table');
            if (firstTable) {
                firstTable.parentNode.insertBefore(resultsInfo, firstTable);
            }
        }
        
        if (visible < total) {
            resultsInfo.textContent = `${visible} résultat(s) sur ${total}`;
            resultsInfo.style.display = 'block';
        } else {
            resultsInfo.style.display = 'none';
        }
    }
    
    // Gestion des dropdowns
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown('profile');
        });
    }
    
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown('notification');
        });
    }
    
    function toggleDropdown(type) {
        // Fermer tous les autres dropdowns
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (!menu.classList.contains(`${type}-dropdown`)) {
                menu.classList.remove('show');
            }
        });
        
        // Toggle le dropdown actuel
        const dropdown = document.querySelector(`.${type}-dropdown`);
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    }
    
    // Fermer dropdowns en cliquant ailleurs
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    });
    
    // Gestion des modales
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }
    
    // Confirmation de suppression
    window.confirmDelete = function(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
        return confirm(message);
    };
    
    // Notifications toast
    function getOrCreateToastContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }
    
    function getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    window.showToast = function(message, type = 'info') {
        const toastContainer = getOrCreateToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${getToastIcon(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
        });
        
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    };
    
    // Gestion des formulaires AJAX
    const ajaxForms = document.querySelectorAll('.ajax-form');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            const formData = new FormData(this);
            
            // Désactiver le bouton pendant l'envoi
            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Fermer la modale si elle existe
                        const modal = this.closest('.modal');
                        if (modal) {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }
                        // Réinitialiser le formulaire
                        this.reset();
                    }
                } else {
                    showToast(data.message || 'Une erreur est survenue', 'danger');
                }
            })
            .catch(error => {
                showToast('Erreur de connexion', 'danger');
            })
            .finally(() => {
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    });
    
    // Actualisation automatique des données
    function autoRefresh() {
        const refreshElements = document.querySelectorAll('[data-auto-refresh]');
        
        refreshElements.forEach(element => {
            const interval = parseInt(element.dataset.autoRefresh) * 1000;
            const url = element.dataset.refreshUrl;
            
            if (url && interval) {
                setInterval(() => {
                    fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        element.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Erreur lors du rafraîchissement:', error);
                    });
                }, interval);
            }
        });
    }
    
    autoRefresh();
    
    // Gestion des tableaux avec tri
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = Array.from(this.parentNode.children).indexOf(this);
            const isAsc = this.classList.contains('asc');
            
            // Reset all headers
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            
            // Set current header
            this.classList.add(isAsc ? 'desc' : 'asc');
            
            // Sort rows
            rows.sort((a, b) => {
                const aVal = a.children[column].textContent.trim();
                const bVal = b.children[column].textContent.trim();
                
                if (isAsc) {
                    return bVal.localeCompare(aVal, 'fr', { numeric: true });
                } else {
                    return aVal.localeCompare(bVal, 'fr', { numeric: true });
                }
            });
            
            // Rebuild tbody
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // Initialisation des tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Sauvegarde automatique des préférences
    function savePreference(key, value) {
        localStorage.setItem(`samalsakom_admin_${key}`, value);
    }
    
    function loadPreference(key, defaultValue = null) {
        return localStorage.getItem(`samalsakom_admin_${key}`) || defaultValue;
    }
    
    // Charger les préférences de sidebar
    const sidebarCollapsed = loadPreference('sidebar_collapsed') === 'true';
    if (sidebarCollapsed && window.innerWidth > 768) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
    
    // Sauvegarder l'état de la sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth > 768) {
                setTimeout(() => {
                    savePreference('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                }, 300);
            }
        });
    }
    
    // Gestion des notifications
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            this.classList.add('read');
        });
    });
    
    // Initialisation des dropdowns Bootstrap
    initDropdowns();
    
    console.log('SamalSakom Admin Dashboard initialisé! 🚀');
});

// Initialisation des dropdowns
function initDropdowns() {
    // Initialiser tous les dropdowns Bootstrap
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
    
    // Fix spécifique pour le dropdown profil
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        // S'assurer que le dropdown fonctionne correctement
        profileDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
            dropdown.toggle();
        });
        
        // Debug - vérifier si Bootstrap est chargé
        console.log('Bootstrap Dropdown disponible:', typeof bootstrap !== 'undefined' && bootstrap.Dropdown);
    }
    
    // Empêcher la fermeture du dropdown lors du clic sur le header
    const dropdownHeaders = document.querySelectorAll('.dropdown-header');
    dropdownHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// Gestion des modales
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}
