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

include 'includes/header.php';
?>

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

<!-- Modal Gestion des Fonctionnalités -->
<div class="modal fade" id="fonctionnalitesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fonctionnalités - <span id="formule-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Liste des fonctionnalités</h6>
                    <button class="btn btn-sm btn-success" onclick="addFonctionnalite()">
                        <i class="fas fa-plus me-1"></i>Ajouter
                    </button>
                </div>
                <div id="fonctionnalites-list">
                    <!-- Chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

function manageFonctionnalites(formuleId, formuleName) {
    currentFormuleId = formuleId;
    document.getElementById('formule-name').textContent = formuleName;
    loadFonctionnalites();
    new bootstrap.Modal(document.getElementById('fonctionnalitesModal')).show();
}

function loadFonctionnalites() {
    fetch(`actions/get_fonctionnalites.php?formule_id=${currentFormuleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('fonctionnalites-list');
                container.innerHTML = '';
                
                data.fonctionnalites.forEach(fonc => {
                    const item = createFonctionnaliteItem(fonc);
                    container.appendChild(item);
                });
            }
        });
}

function createFonctionnaliteItem(fonc) {
    const div = document.createElement('div');
    div.className = 'card mb-2';
    div.innerHTML = `
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="${fonc.inclus ? 'fas fa-check text-success' : 'fas fa-times text-muted'} me-2"></i>
                    <span>${fonc.nom}</span>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editFonctionnalite(${fonc.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFonctionnalite(${fonc.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
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
