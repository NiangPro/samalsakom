<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Récupération des formules avec leurs fonctionnalités
try {
    $query = "SELECT f.*, 
                     COUNT(ff.id) as nb_fonctionnalites
              FROM formules_services f
              LEFT JOIN formule_fonctionnalites ff ON f.id = ff.formule_id
              GROUP BY f.id
              ORDER BY f.ordre_affichage ASC, f.id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $formules = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des formules.";
    $formules = [];
}
?>

<?php include 'includes/header.php'; ?>

<!-- Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Gestion des Formules</h1>
                <p class="content-subtitle">Gérez les formules d'abonnement affichées sur la page services</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFormuleModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle Formule
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Liste des formules -->
        <div class="row g-4">
            <?php foreach ($formules as $formule): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($formule['nom']); ?></h5>
                            <?php if ($formule['populaire']): ?>
                            <span class="badge bg-primary ms-2">Populaire</span>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo $formule['statut'] === 'actif' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($formule['statut']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="h2 fw-bold text-primary">
                                <?php if ($formule['prix'] == 0): ?>
                                    Gratuit
                                <?php else: ?>
                                    <?php echo number_format($formule['prix'], 0, ',', ' '); ?> <?php echo $formule['devise']; ?>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo htmlspecialchars($formule['description']); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-list me-1"></i><?php echo $formule['nb_fonctionnalites']; ?> fonctionnalités
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="editFormule(<?php echo $formule['id']; ?>)">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                            <button class="btn btn-outline-info btn-sm flex-fill" onclick="manageFonctionnalites(<?php echo $formule['id']; ?>, '<?php echo htmlspecialchars($formule['nom']); ?>')">
                                <i class="fas fa-cog me-1"></i>Fonctionnalités
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteFormule(<?php echo $formule['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvelle/Modifier Formule -->
<div class="modal fade" id="addFormuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Formule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formuleForm">
                    <input type="hidden" id="formule_id" name="formule_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prix</label>
                            <input type="number" class="form-control" id="prix" name="prix" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Devise</label>
                            <select class="form-select" id="devise" name="devise">
                                <option value="FCFA">FCFA</option>
                                <option value="EUR">EUR</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Période</label>
                            <input type="text" class="form-control" id="periode" name="periode" placeholder="mois, année, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="Par mois, Pour débuter, etc.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Couleur</label>
                            <select class="form-select" id="couleur" name="couleur">
                                <option value="primary">Primaire</option>
                                <option value="secondary">Secondaire</option>
                                <option value="success">Succès</option>
                                <option value="outline-primary">Contour Primaire</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="ordre_affichage" name="ordre_affichage" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="populaire" name="populaire">
                                <label class="form-check-label" for="populaire">
                                    Marquer comme populaire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveFormule()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter/Modifier Fonctionnalité -->
<div class="modal fade" id="fonctionnaliteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Ajouter une fonctionnalité</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="fonctionnaliteForm">
                    <input type="hidden" id="fonctionnalite_id" name="fonctionnalite_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="fonctionnalite_nom" name="nom" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" id="fonctionnalite_description" name="description">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Icône</label>
                            <input type="text" class="form-control" id="fonctionnalite_icone" name="icone" value="fas fa-check">
                            <small class="form-text text-muted">Ex: fas fa-check, fas fa-times</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" id="fonctionnalite_ordre" name="ordre_affichage" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="fonctionnalite_inclus" checked>
                                <label class="form-check-label" for="fonctionnalite_inclus">Inclus dans la formule</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveFonctionnalite()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gestion des Fonctionnalités -->
<div class="modal fade" id="fonctionnalitesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Fonctionnalités - <span id="formule-name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Liste des fonctionnalités</h6>
                    <button class="btn btn-sm btn-success" onclick="addFonctionnalite()">
                        <i class="fas fa-plus me-1"></i>Ajouter
                    </button>
                </div>
                <div id="fonctionnalites-list" class="animate__animated animate__fadeIn">
                    <!-- Chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (nécessaire pour les modals Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // S'assurer que Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas chargé. Chargement...');
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
        script.onload = initializeBootstrapComponents;
        document.head.appendChild(script);
    } else {
        initializeBootstrapComponents();
    }
    
    function initializeBootstrapComponents() {
        console.log('Initialisation des composants Bootstrap...');
        
        // Initialisation des tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Réinitialiser les tooltips après chargement dynamique
        document.addEventListener('DOMNodeInserted', function(e) {
            if (e.target.querySelector && e.target.querySelector('[data-bs-toggle="tooltip"]')) {
                var tooltips = e.target.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltips.forEach(function(tooltip) {
                    new bootstrap.Tooltip(tooltip);
                });
            }
        });
        
        // Initialiser correctement les modals pour éviter les problèmes
        document.querySelectorAll('.modal').forEach(function(modalEl) {
            // Ne pas initialiser les modals ici, car cela peut causer des problèmes
            // avec les événements et l'ouverture ultérieure
            console.log('Modal détecté:', modalEl.id);
        });
        
        // Configurer jQuery pour utiliser Bootstrap 5 modals correctement
        $.fn.modal = function(action) {
            if (action === 'show') {
                var modalId = this.attr('id');
                console.log('Ouverture du modal via jQuery:', modalId);
                var modalEl = document.getElementById(modalId);
                if (modalEl) {
                    var bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                }
            }
            return this;
        };
    }
    
    // Charger les formules
    loadFormules();
});

let currentFormuleId = null;

function editFormule(id) {
    fetch(`actions/get_formule.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const formule = data.formule;
                document.getElementById('formule_id').value = formule.id;
                document.getElementById('nom').value = formule.nom;
                document.getElementById('prix').value = formule.prix;
                document.getElementById('devise').value = formule.devise;
                document.getElementById('periode').value = formule.periode;
                document.getElementById('description').value = formule.description;
                document.getElementById('couleur').value = formule.couleur;
                document.getElementById('ordre_affichage').value = formule.ordre_affichage;
                document.getElementById('populaire').checked = formule.populaire == 1;
                document.getElementById('statut').value = formule.statut;
                
                document.querySelector('#addFormuleModal .modal-title').textContent = 'Modifier Formule';
                new bootstrap.Modal(document.getElementById('addFormuleModal')).show();
            }
        });
}

function saveFormule() {
    const form = document.getElementById('formuleForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    fetch('actions/save_formule.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addFormuleModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    });
}

function deleteFormule(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette formule ?')) {
        const formData = new FormData();
        formData.append('formule_id', id);
        
        fetch('actions/delete_formule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        });
    }
}

function loadFormules() {
    // Cette fonction n'est pas nécessaire car les formules sont déjà chargées par PHP
    // Elle est ajoutée pour éviter l'erreur de référence
    console.log('Formules déjà chargées par PHP');
}

function manageFonctionnalites(formuleId, formuleName) {
    console.log('manageFonctionnalites appelé avec ID:', formuleId, 'et nom:', formuleName);
    currentFormuleId = formuleId;
    
    // Vérifier si l'élément existe
    const formuleNameElement = document.getElementById('formule-name');
    if (!formuleNameElement) {
        console.error("Élément 'formule-name' introuvable dans le DOM");
        return;
    }
    formuleNameElement.textContent = formuleName;
    
    // Charger les fonctionnalités avant d'ouvrir le modal
    loadFonctionnalites();
    
    // Obtenir l'élément modal
    const modalElement = document.getElementById('fonctionnalitesModal');
    if (!modalElement) {
        console.error("Modal 'fonctionnalitesModal' introuvable dans le DOM");
        alert("Erreur: Le modal des fonctionnalités n'existe pas dans la page.");
        return;
    }
    
    // Méthode directe avec l'API DOM (fonctionne avec Bootstrap 5)
    const openButton = document.createElement('button');
    openButton.setAttribute('type', 'button');
    openButton.setAttribute('data-bs-toggle', 'modal');
    openButton.setAttribute('data-bs-target', '#fonctionnalitesModal');
    openButton.style.display = 'none';
    document.body.appendChild(openButton);
    openButton.click();
    document.body.removeChild(openButton);
    console.log('Modal ouvert via bouton temporaire');
}

function addFonctionnalite() {
    console.log('addFonctionnalite appelé');
    document.getElementById('fonctionnalite_id').value = '';
    document.getElementById('fonctionnalite_nom').value = '';
    document.getElementById('fonctionnalite_description').value = '';
    document.getElementById('fonctionnalite_inclus').checked = true;
    document.getElementById('fonctionnalite_icone').value = 'fas fa-check';
    document.getElementById('fonctionnalite_ordre').value = '0';
    
    document.querySelector('#fonctionnaliteModal .modal-title').textContent = 'Ajouter une fonctionnalité';
    
    // Méthode directe avec l'API DOM (fonctionne avec Bootstrap 5)
    const openButton = document.createElement('button');
    openButton.setAttribute('type', 'button');
    openButton.setAttribute('data-bs-toggle', 'modal');
    openButton.setAttribute('data-bs-target', '#fonctionnaliteModal');
    openButton.style.display = 'none';
    document.body.appendChild(openButton);
    openButton.click();
    document.body.removeChild(openButton);
    console.log('Modal fonctionnaliteModal ouvert via bouton temporaire');
}

function editFonctionnalite(id) {
    console.log('editFonctionnalite appelé avec ID:', id);
    fetch(`actions/get_fonctionnalites.php?formule_id=${currentFormuleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fonctionnalite = data.fonctionnalites.find(f => f.id == id);
                if (fonctionnalite) {
                    document.getElementById('fonctionnalite_id').value = fonctionnalite.id;
                    document.getElementById('fonctionnalite_nom').value = fonctionnalite.nom;
                    document.getElementById('fonctionnalite_description').value = fonctionnalite.description || '';
                    document.getElementById('fonctionnalite_inclus').checked = fonctionnalite.inclus == 1;
                    document.getElementById('fonctionnalite_icone').value = fonctionnalite.icone || 'fas fa-check';
                    document.getElementById('fonctionnalite_ordre').value = fonctionnalite.ordre_affichage || '0';
                    
                    document.querySelector('#fonctionnaliteModal .modal-title').textContent = 'Modifier la fonctionnalité';
                    
                    // Méthode directe avec l'API DOM (fonctionne avec Bootstrap 5)
                    const openButton = document.createElement('button');
                    openButton.setAttribute('type', 'button');
                    openButton.setAttribute('data-bs-toggle', 'modal');
                    openButton.setAttribute('data-bs-target', '#fonctionnaliteModal');
                    openButton.style.display = 'none';
                    document.body.appendChild(openButton);
                    openButton.click();
                    document.body.removeChild(openButton);
                    console.log('Modal fonctionnaliteModal ouvert via bouton temporaire pour édition');
                }
            }
        });
}

function deleteFonctionnalite(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette fonctionnalité ?')) {
        const formData = new FormData();
        formData.append('fonctionnalite_id', id);
        
        fetch('actions/delete_fonctionnalite.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                loadFonctionnalites();
            } else {
                showToast(data.message, 'error');
            }
        });
    }
}

function saveFonctionnalite() {
    const form = document.getElementById('fonctionnaliteForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    formData.append('formule_id', currentFormuleId);
    formData.append('inclus', document.getElementById('fonctionnalite_inclus').checked ? '1' : '0');
    
    fetch('actions/save_fonctionnalite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('fonctionnaliteModal')).hide();
            loadFonctionnalites();
        } else {
            showToast(data.message, 'error');
        }
    });
}

function loadFonctionnalites() {
    console.log('loadFonctionnalites appelé pour formuleId:', currentFormuleId);
    fetch(`actions/get_fonctionnalites.php?formule_id=${currentFormuleId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Données des fonctionnalités reçues:', data);
            if (data.success) {
                const container = document.getElementById('fonctionnalites-list');
                container.innerHTML = '';
                
                data.fonctionnalites.forEach(fonc => {
                    console.log('Création de l\'élément pour la fonctionnalité:', fonc.id, fonc.nom);
                    const item = createFonctionnaliteItem(fonc);
                    container.appendChild(item);
                });
                
                // Réinitialiser les tooltips après chargement dynamique
                setTimeout(() => {
                    const tooltips = container.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltips.forEach(tooltip => {
                        new bootstrap.Tooltip(tooltip);
                    });
                    console.log('Tooltips réinitialisés pour', tooltips.length, 'éléments');
                }, 100);
            } else {
                container.innerHTML = '<div class="alert alert-info">Aucune fonctionnalité n\'est définie pour cette formule.</div>';
            }
        });
}

function createFonctionnaliteItem(fonc) {
    const div = document.createElement('div');
    div.className = 'card mb-2 animate__animated animate__fadeIn';
    
    // Créer le contenu HTML
    const cardBody = document.createElement('div');
    cardBody.className = 'card-body py-2';
    
    const flexContainer = document.createElement('div');
    flexContainer.className = 'd-flex justify-content-between align-items-center';
    
    // Partie gauche avec icône et texte
    const leftPart = document.createElement('div');
    leftPart.className = 'd-flex align-items-center';
    
    const icon = document.createElement('i');
    icon.className = `${fonc.icone || (fonc.inclus ? 'fas fa-check text-success' : 'fas fa-times text-muted')} me-2`;
    
    const textDiv = document.createElement('div');
    const nameSpan = document.createElement('span');
    nameSpan.className = 'fw-medium';
    nameSpan.textContent = fonc.nom;
    textDiv.appendChild(nameSpan);
    
    if (fonc.description) {
        const descDiv = document.createElement('div');
        const descSmall = document.createElement('small');
        descSmall.className = 'text-muted';
        descSmall.textContent = fonc.description;
        descDiv.appendChild(descSmall);
        textDiv.appendChild(descDiv);
    }
    
    leftPart.appendChild(icon);
    leftPart.appendChild(textDiv);
    
    // Partie droite avec les boutons
    const btnGroup = document.createElement('div');
    btnGroup.className = 'btn-group';
    
    // Bouton Modifier
    const editBtn = document.createElement('button');
    editBtn.className = 'btn btn-sm btn-outline-primary';
    editBtn.setAttribute('data-bs-toggle', 'tooltip');
    editBtn.setAttribute('title', 'Modifier');
    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
    editBtn.addEventListener('click', function() {
        console.log('Bouton modifier cliqué pour ID:', fonc.id);
        editFonctionnalite(fonc.id);
    });
    
    // Bouton Supprimer
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-sm btn-outline-danger';
    deleteBtn.setAttribute('data-bs-toggle', 'tooltip');
    deleteBtn.setAttribute('title', 'Supprimer');
    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
    deleteBtn.addEventListener('click', function() {
        console.log('Bouton supprimer cliqué pour ID:', fonc.id);
        deleteFonctionnalite(fonc.id);
    });
    
    btnGroup.appendChild(editBtn);
    btnGroup.appendChild(deleteBtn);
    
    // Assembler le tout
    flexContainer.appendChild(leftPart);
    flexContainer.appendChild(btnGroup);
    cardBody.appendChild(flexContainer);
    div.appendChild(cardBody);
    
    return div;
}

// Réinitialiser le formulaire à la fermeture du modal
document.getElementById('addFormuleModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formuleForm').reset();
    document.getElementById('formule_id').value = '';
    document.querySelector('#addFormuleModal .modal-title').textContent = 'Nouvelle Formule';
});

function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast_' + Date.now();
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
