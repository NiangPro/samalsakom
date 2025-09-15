<?php
$page_title = "Paramètres";
$breadcrumb = "Paramètres";
include 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Récupérer les paramètres utilisateur
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../login.php');
        exit;
    }
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des paramètres.";
}

// Traitement des paramètres de notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    try {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
        $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
        
        // Mettre à jour les préférences (on peut ajouter une table settings plus tard)
        $success_message = "Paramètres de notification mis à jour avec succès.";
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour des paramètres.";
    }
}

// Traitement de la suppression de compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirm_password = $_POST['confirm_password_delete'];
    
    if (password_verify($confirm_password, $user['mot_de_passe'])) {
        try {
            // Vérifier s'il y a des tontines actives
            $check_query = "SELECT COUNT(*) as count FROM participations p 
                           JOIN tontines t ON p.tontine_id = t.id 
                           WHERE p.user_id = ? AND t.statut = 'active'";
            $stmt = $db->prepare($check_query);
            $stmt->execute([$_SESSION['user_id']]);
            $active_tontines = $stmt->fetch()['count'];
            
            if ($active_tontines > 0) {
                $error_message = "Impossible de supprimer le compte. Vous participez encore à $active_tontines tontine(s) active(s).";
            } else {
                // Supprimer le compte (en réalité, on devrait le désactiver)
                $delete_query = "UPDATE users SET statut = 'deleted', date_suppression = NOW() WHERE id = ?";
                $stmt = $db->prepare($delete_query);
                $stmt->execute([$_SESSION['user_id']]);
                
                // Déconnecter l'utilisateur
                session_destroy();
                header('Location: ../index.php?message=account_deleted');
                exit;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de la suppression du compte.";
        }
    } else {
        $error_message = "Mot de passe incorrect.";
    }
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Paramètres</h1>
    <p class="page-subtitle">Gérez vos préférences et paramètres de compte</p>
</div>

<?php if ($success_message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-up">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $success_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4" data-aos="fade-up">
    <!-- Paramètres de notification -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="card-header-modern">
                <h5 class="card-title">
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                </h5>
                <p class="card-subtitle">Choisissez comment vous souhaitez être notifié</p>
            </div>
            <div class="card-body-modern">
                <form method="POST">
                    <div class="settings-group">
                        <div class="setting-item">
                            <div class="setting-info">
                                <h6>Notifications par email</h6>
                                <p class="text-muted">Recevez des notifications importantes par email</p>
                            </div>
                            <div class="setting-control">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" 
                                           name="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <h6>Notifications SMS</h6>
                                <p class="text-muted">Recevez des alertes importantes par SMS</p>
                            </div>
                            <div class="setting-control">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications" 
                                           name="sms_notifications" checked>
                                    <label class="form-check-label" for="sms_notifications"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <h6>Notifications push</h6>
                                <p class="text-muted">Recevez des notifications dans votre navigateur</p>
                            </div>
                            <div class="setting-control">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="push_notifications" 
                                           name="push_notifications">
                                    <label class="form-check-label" for="push_notifications"></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <h6>Emails marketing</h6>
                                <p class="text-muted">Recevez nos newsletters et offres spéciales</p>
                            </div>
                            <div class="setting-control">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="marketing_emails" 
                                           name="marketing_emails">
                                    <label class="form-check-label" for="marketing_emails"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="update_notifications" class="btn btn-primary-modern">
                            <i class="fas fa-save me-2"></i>Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Paramètres de sécurité -->
        <div class="dashboard-card mt-4">
            <div class="card-header-modern">
                <h5 class="card-title">
                    <i class="fas fa-shield-alt me-2"></i>
                    Sécurité
                </h5>
                <p class="card-subtitle">Gérez la sécurité de votre compte</p>
            </div>
            <div class="card-body-modern">
                <div class="settings-group">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Authentification à deux facteurs</h6>
                            <p class="text-muted">Ajoutez une couche de sécurité supplémentaire</p>
                        </div>
                        <div class="setting-control">
                            <button class="btn btn-outline-primary btn-sm" onclick="showToast('Fonctionnalité bientôt disponible', 'info')">
                                <i class="fas fa-plus me-2"></i>Activer
                            </button>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Sessions actives</h6>
                            <p class="text-muted">Gérez vos sessions de connexion</p>
                        </div>
                        <div class="setting-control">
                            <button class="btn btn-outline-secondary btn-sm" onclick="showSessions()">
                                <i class="fas fa-eye me-2"></i>Voir les sessions
                            </button>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Changer le mot de passe</h6>
                            <p class="text-muted">Modifiez votre mot de passe de connexion</p>
                        </div>
                        <div class="setting-control">
                            <a href="profil.php#security" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-key me-2"></i>Modifier
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zone de danger -->
        <div class="dashboard-card mt-4 border-danger">
            <div class="card-header-modern bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Zone de danger
                </h5>
            </div>
            <div class="card-body-modern">
                <div class="alert alert-warning">
                    <i class="fas fa-warning me-2"></i>
                    <strong>Attention :</strong> Ces actions sont irréversibles.
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h6 class="text-danger">Supprimer mon compte</h6>
                        <p class="text-muted">Supprime définitivement votre compte et toutes vos données</p>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-trash me-2"></i>Supprimer le compte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar informations -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-body-modern text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-4x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-success">Compte Actif</span>
                
                <hr>
                
                <div class="text-start">
                    <small class="text-muted d-block">Membre depuis</small>
                    <strong><?php echo date('d F Y', strtotime($user['date_creation'])); ?></strong>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card mt-4">
            <div class="card-header-modern">
                <h6 class="card-title">Aide et support</h6>
            </div>
            <div class="card-body-modern">
                <div class="d-grid gap-2">
                    <a href="aide.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-question-circle me-2"></i>Centre d'aide
                    </a>
                    <a href="../contact.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-envelope me-2"></i>Nous contacter
                    </a>
                    <button class="btn btn-outline-info btn-sm" onclick="showToast('Fonctionnalité bientôt disponible', 'info')">
                        <i class="fas fa-comments me-2"></i>Chat en direct
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de compte -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Supprimer le compte
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>⚠️ Action irréversible !</strong><br>
                    Cette action supprimera définitivement votre compte et toutes vos données.
                </div>
                
                <p><strong>Conséquences de la suppression :</strong></p>
                <ul>
                    <li>Perte de l'accès à toutes vos tontines</li>
                    <li>Suppression de votre historique de transactions</li>
                    <li>Perte de tous vos paramètres et préférences</li>
                    <li>Impossible de récupérer les données</li>
                </ul>
                
                <form method="POST" id="deleteAccountForm">
                    <div class="mb-3">
                        <label class="form-label">Confirmez avec votre mot de passe :</label>
                        <input type="password" class="form-control" name="confirm_password_delete" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            Je comprends que cette action est irréversible
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="deleteAccountForm" name="delete_account" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.settings-group {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    background: var(--gray-50);
}

.setting-info h6 {
    margin-bottom: 0.25rem;
    color: var(--dark-color);
}

.setting-info p {
    margin-bottom: 0;
    font-size: 0.9rem;
}

.setting-control {
    flex-shrink: 0;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.border-danger {
    border-color: var(--danger-color) !important;
}

@media (max-width: 768px) {
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .setting-control {
        width: 100%;
    }
}
</style>

<script>
function showSessions() {
    showToast('Fonctionnalité de gestion des sessions bientôt disponible', 'info');
}

// Validation du formulaire de suppression
document.getElementById('deleteAccountForm').addEventListener('submit', function(e) {
    const checkbox = document.getElementById('confirmDelete');
    const password = document.querySelector('[name="confirm_password_delete"]').value;
    
    if (!checkbox.checked) {
        e.preventDefault();
        showToast('Vous devez confirmer que vous comprenez les conséquences', 'error');
        return;
    }
    
    if (!password) {
        e.preventDefault();
        showToast('Veuillez saisir votre mot de passe', 'error');
        return;
    }
    
    if (!confirm('DERNIÈRE CHANCE : Êtes-vous absolument sûr de vouloir supprimer votre compte ?')) {
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
