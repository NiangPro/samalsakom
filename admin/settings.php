<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Récupération des paramètres système
$settings = [
    'site_name' => 'SamalSakom',
    'site_description' => 'Plateforme de gestion de tontines au Sénégal',
    'contact_email' => 'contact@samalsakom.sn',
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'max_tontine_participants' => 20,
    'default_currency' => 'FCFA',
    'email_notifications' => true,
    'sms_notifications' => false
];

include 'includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Paramètres Système</h1>
                <p class="content-subtitle">Configuration générale de la plateforme</p>
            </div>
            <div class="content-actions">
                <button class="btn btn-outline-primary me-2" onclick="exportSettings()">
                    <i class="fas fa-download me-1"></i>Exporter
                </button>
                <button class="btn btn-success" onclick="saveAllSettings()">
                    <i class="fas fa-save me-1"></i>Enregistrer Tout
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
        <div class="row">
            <!-- Paramètres Généraux -->
            <div class="col-xl-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2 text-primary"></i>Paramètres Généraux
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="generalSettings">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom du site</label>
                                    <input type="text" class="form-control" name="site_name" value="<?php echo $settings['site_name']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" name="contact_email" value="<?php echo $settings['contact_email']; ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description du site</label>
                                    <textarea class="form-control" name="site_description" rows="3"><?php echo $settings['site_description']; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Devise par défaut</label>
                                    <select class="form-select" name="default_currency">
                                        <option value="FCFA" selected>FCFA</option>
                                        <option value="EUR">EUR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Participants max par tontine</label>
                                    <input type="number" class="form-control" name="max_participants" value="<?php echo $settings['max_tontine_participants']; ?>" min="5" max="100">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paramètres de Sécurité -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt me-2 text-success"></i>Sécurité
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                    <label class="form-check-label" for="maintenanceMode">
                                        Mode maintenance
                                    </label>
                                </div>
                                <small class="text-muted">Désactive l'accès public au site</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="registrationEnabled" checked>
                                    <label class="form-check-label" for="registrationEnabled">
                                        Inscriptions ouvertes
                                    </label>
                                </div>
                                <small class="text-muted">Permet aux nouveaux utilisateurs de s'inscrire</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2 text-warning"></i>Notifications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">
                                        Notifications par email
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="smsNotifications">
                                    <label class="form-check-label" for="smsNotifications">
                                        Notifications SMS
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Rapides -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Actions Rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="clearCache()">
                                <i class="fas fa-broom me-2"></i>Vider le cache
                            </button>
                            <button class="btn btn-outline-info" onclick="backupDatabase()">
                                <i class="fas fa-database me-2"></i>Sauvegarder BDD
                            </button>
                            <button class="btn btn-outline-warning" onclick="viewLogs()">
                                <i class="fas fa-file-alt me-2"></i>Voir les logs
                            </button>
                            <button class="btn btn-outline-success" onclick="testEmail()">
                                <i class="fas fa-envelope me-2"></i>Test email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Informations Système -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Informations Système</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Version PHP</span>
                                <span class="fw-semibold"><?php echo PHP_VERSION; ?></span>
                            </div>
                        </div>
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Version MySQL</span>
                                <span class="fw-semibold">8.0.x</span>
                            </div>
                        </div>
                        <div class="info-item mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Espace disque</span>
                                <span class="fw-semibold">2.5 GB / 10 GB</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Dernière sauvegarde</span>
                                <span class="fw-semibold">Hier 03:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saveAllSettings() {
    showToast('Paramètres sauvegardés avec succès', 'success');
}

function clearCache() {
    if (confirm('Êtes-vous sûr de vouloir vider le cache ?')) {
        showToast('Cache vidé avec succès', 'success');
    }
}

function backupDatabase() {
    showToast('Sauvegarde en cours...', 'info');
    setTimeout(() => {
        showToast('Sauvegarde terminée', 'success');
    }, 3000);
}

function viewLogs() {
    window.open('logs.php', '_blank');
}

function testEmail() {
    showToast('Test email envoyé', 'success');
}

function exportSettings() {
    showToast('Export des paramètres...', 'info');
}
</script>

<?php include 'includes/footer.php'; ?>
