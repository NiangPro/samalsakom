        </main>
    </div>
    
    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle text-primary me-2"></i>
                <strong class="me-auto">SamalSakom</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Scripts personnalisés -->
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Initialisation AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Gestion sidebar mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                sidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
                document.body.classList.toggle('sidebar-open');
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', toggleSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
            
            // Fermer sidebar sur redimensionnement
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // Charger les notifications
            loadNotifications();
            
            // Recherche rapide
            const quickSearch = document.getElementById('quickSearch');
            if (quickSearch) {
                quickSearch.addEventListener('input', function() {
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        searchTontines(query);
                    }
                });
            }
        });
        
        // Fonction pour charger les notifications
        function loadNotifications() {
            fetch('actions/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notificationList = document.getElementById('notificationList');
                        if (notificationList) {
                            notificationList.innerHTML = data.notifications;
                        }
                    }
                })
                .catch(error => console.error('Erreur chargement notifications:', error));
        }
        
        // Fonction de recherche rapide
        function searchTontines(query) {
            // Implémentation de la recherche rapide
            console.log('Recherche:', query);
        }
        
        // Fonction pour afficher les toasts
        function showToast(message, type = 'info') {
            const toast = document.getElementById('liveToast');
            const toastBody = toast.querySelector('.toast-body');
            const toastIcon = toast.querySelector('.toast-header i');
            
            // Changer l'icône selon le type
            toastIcon.className = `fas me-2 ${getToastIcon(type)} ${getToastColor(type)}`;
            
            toastBody.textContent = message;
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Supprimer le toast après fermeture
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        
        function getToastIcon(type) {
            switch(type) {
                case 'success': return 'fa-check-circle';
                case 'error': return 'fa-exclamation-circle';
                case 'warning': return 'fa-exclamation-triangle';
                default: return 'fa-info-circle';
            }
        }
        
        function getToastColor(type) {
            switch(type) {
                case 'success': return 'text-success';
                case 'error': return 'text-danger';
                case 'warning': return 'text-warning';
                default: return 'text-primary';
            }
        }
        
        // Fonction pour formater les montants
        function formatMoney(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'XOF',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        // Fonction pour formater les dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Fonctions pour les actions de tontines
        function rejoindre_tontine(tontineId) {
            if (confirm('Voulez-vous vraiment rejoindre cette tontine ?')) {
                fetch('actions/rejoindre_tontine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ tontine_id: tontineId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erreur lors de la connexion', 'error');
                });
            }
        }

        function quitter_tontine(tontineId) {
            if (confirm('Êtes-vous sûr de vouloir quitter cette tontine ? Cette action est irréversible.')) {
                fetch('actions/quitter_tontine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ tontine_id: tontineId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erreur lors de la connexion', 'error');
                });
            }
        }

        function payer_cotisation(cotisationId) {
            fetch(`actions/get_paiement_form.php?id=${cotisationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('paiementModalBody').innerHTML = data.html;
                        const modal = new bootstrap.Modal(document.getElementById('paiementModal'));
                        modal.show();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erreur lors du chargement du formulaire', 'error');
                });
        }

        function partager_tontine(tontineId, tontineName) {
            const url = window.location.origin + '/samalsakom/dashboard/decouvrir-tontines.php?tontine=' + tontineId;
            const text = `Découvrez la tontine "${tontineName}" sur SamalSakom !`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'SamalSakom - ' + tontineName,
                    text: text,
                    url: url
                });
            } else {
                // Fallback: copier dans le presse-papier
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Lien copié dans le presse-papier !', 'success');
                });
            }
        }
        
        // Fonction pour créer un conteneur de toasts
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        // Fonction pour obtenir l'icône de toast
        function getToastIcon(type) {
            switch(type) {
                case 'success': return 'fa-check-circle';
                case 'error': return 'fa-times-circle';
                case 'warning': return 'fa-exclamation-triangle';
                default: return 'fa-info-circle';
            }
        }
        
        // Formatage des nombres
        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        }
        
        // Formatage des dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    </script>
</body>
</html>
