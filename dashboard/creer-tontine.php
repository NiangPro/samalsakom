<?php
$page_title = "Créer une Tontine";
$breadcrumb = "Créer";
include 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        $montant_cotisation = (int)$_POST['montant_cotisation'];
        $nombre_participants = (int)$_POST['nombre_participants'];
        $frequence = $_POST['frequence'];
        $date_debut = $_POST['date_debut'];
        $duree_mois = (int)$_POST['duree_mois'];
        
        // Validation
        if (empty($nom) || empty($montant_cotisation) || empty($nombre_participants) || empty($frequence) || empty($date_debut)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis.");
        }
        
        if ($montant_cotisation < 1000) {
            throw new Exception("Le montant de cotisation doit être d'au moins 1000 FCFA.");
        }
        
        if ($nombre_participants < 2 || $nombre_participants > 50) {
            throw new Exception("Le nombre de participants doit être entre 2 et 50.");
        }
        
        if ($duree_mois < 1 || $duree_mois > 60) {
            throw new Exception("La durée doit être entre 1 et 60 mois.");
        }
        
        // Calculer la date de fin
        $date_fin = date('Y-m-d', strtotime($date_debut . " + {$duree_mois} months"));
        
        // Insérer la tontine
        $query = "INSERT INTO tontines (nom, description, montant_cotisation, nombre_participants, frequence, date_debut, date_fin, createur_id, statut, date_creation) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $nom, 
            $description, 
            $montant_cotisation, 
            $nombre_participants, 
            $frequence, 
            $date_debut, 
            $date_fin, 
            $_SESSION['user_id']
        ]);
        
        $tontine_id = $db->lastInsertId();
        
        // Ajouter le créateur comme premier participant
        $participation_query = "INSERT INTO participations (tontine_id, user_id, date_participation, statut) VALUES (?, ?, NOW(), 'active')";
        $stmt = $db->prepare($participation_query);
        $stmt->execute([$tontine_id, $_SESSION['user_id']]);
        
        $success_message = "Tontine créée avec succès ! Vous êtes automatiquement inscrit comme premier participant.";
        
        // Redirection JavaScript au lieu de header PHP
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'mes-tontines.php';
                }, 3000);
              </script>";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<div class="page-header" data-aos="fade-down">
    <h1 class="page-title">Créer une Nouvelle Tontine</h1>
    <p class="page-subtitle">Organisez votre propre tontine et invitez d'autres participants</p>
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

<div class="row justify-content-center" data-aos="fade-up">
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="card-body-modern">
                <form method="POST" class="form-modern" id="creerTontineForm">
                    <div class="row g-4">
                        <!-- Informations de base -->
                        <div class="col-12">
                            <h5 class="section-title">
                                <i class="fas fa-info-circle me-2"></i>
                                Informations de base
                            </h5>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label required">Nom de la tontine</label>
                            <input type="text" class="form-control" name="nom" required 
                                   placeholder="Ex: Tontine Épargne Famille" 
                                   value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label required">Montant de cotisation (FCFA)</label>
                            <input type="number" class="form-control" name="montant_cotisation" required 
                                   min="1000" step="500" placeholder="Ex: 10000"
                                   value="<?php echo htmlspecialchars($_POST['montant_cotisation'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" 
                                      placeholder="Décrivez l'objectif et les règles de votre tontine..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Configuration -->
                        <div class="col-12 mt-4">
                            <h5 class="section-title">
                                <i class="fas fa-cog me-2"></i>
                                Configuration
                            </h5>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label required">Nombre de participants</label>
                            <input type="number" class="form-control" name="nombre_participants" required 
                                   min="2" max="50" placeholder="Ex: 10"
                                   value="<?php echo htmlspecialchars($_POST['nombre_participants'] ?? ''); ?>">
                            <small class="form-text text-muted">Entre 2 et 50 participants</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label required">Fréquence des cotisations</label>
                            <select class="form-control" name="frequence" required>
                                <option value="">Choisir...</option>
                                <option value="hebdomadaire" <?php echo ($_POST['frequence'] ?? '') == 'hebdomadaire' ? 'selected' : ''; ?>>Hebdomadaire</option>
                                <option value="mensuelle" <?php echo ($_POST['frequence'] ?? '') == 'mensuelle' ? 'selected' : ''; ?>>Mensuelle</option>
                                <option value="trimestrielle" <?php echo ($_POST['frequence'] ?? '') == 'trimestrielle' ? 'selected' : ''; ?>>Trimestrielle</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label required">Durée (mois)</label>
                            <input type="number" class="form-control" name="duree_mois" required 
                                   min="1" max="60" placeholder="Ex: 12"
                                   value="<?php echo htmlspecialchars($_POST['duree_mois'] ?? ''); ?>">
                            <small class="form-text text-muted">Entre 1 et 60 mois</small>
                        </div>
                        
                        <!-- Dates -->
                        <div class="col-12 mt-4">
                            <h5 class="section-title">
                                <i class="fas fa-calendar me-2"></i>
                                Planning
                            </h5>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label required">Date de début</label>
                            <input type="date" class="form-control" name="date_debut" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['date_debut'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date de fin estimée</label>
                            <input type="text" class="form-control" id="date_fin_estimee" readonly 
                                   placeholder="Sera calculée automatiquement">
                        </div>
                        
                        <!-- Résumé -->
                        <div class="col-12 mt-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-calculator me-2"></i>Résumé financier</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Total par participant :</strong>
                                        <div id="total_participant">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Fonds total de la tontine :</strong>
                                        <div id="fonds_total">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Gain potentiel :</strong>
                                        <div id="gain_potentiel">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="col-12 mt-4">
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary-modern">
                                    <i class="fas fa-plus me-2"></i>Créer la Tontine
                                </button>
                                <a href="decouvrir-tontines.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.section-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--gray-200);
}

.form-label.required::after {
    content: " *";
    color: var(--danger-color);
}

.alert-info {
    background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
    border: none;
    border-radius: var(--border-radius);
}

#total_participant, #fonds_total, #gain_potentiel {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-color);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('creerTontineForm');
    const montantInput = form.querySelector('[name="montant_cotisation"]');
    const participantsInput = form.querySelector('[name="nombre_participants"]');
    const frequenceInput = form.querySelector('[name="frequence"]');
    const dureeInput = form.querySelector('[name="duree_mois"]');
    const dateDebutInput = form.querySelector('[name="date_debut"]');
    const dateFinInput = document.getElementById('date_fin_estimee');
    
    function calculerResume() {
        const montant = parseInt(montantInput.value) || 0;
        const participants = parseInt(participantsInput.value) || 0;
        const duree = parseInt(dureeInput.value) || 0;
        const frequence = frequenceInput.value;
        
        if (montant && participants && duree && frequence) {
            let nbCotisations = 0;
            switch(frequence) {
                case 'hebdomadaire':
                    nbCotisations = duree * 4; // Approximation
                    break;
                case 'mensuelle':
                    nbCotisations = duree;
                    break;
                case 'trimestrielle':
                    nbCotisations = Math.ceil(duree / 3);
                    break;
            }
            
            const totalParParticipant = montant * nbCotisations;
            const fondsTotal = totalParParticipant * participants;
            const gainPotentiel = fondsTotal - totalParParticipant;
            
            document.getElementById('total_participant').textContent = 
                new Intl.NumberFormat('fr-FR').format(totalParParticipant) + ' FCFA';
            document.getElementById('fonds_total').textContent = 
                new Intl.NumberFormat('fr-FR').format(fondsTotal) + ' FCFA';
            document.getElementById('gain_potentiel').textContent = 
                new Intl.NumberFormat('fr-FR').format(gainPotentiel) + ' FCFA';
        }
    }
    
    function calculerDateFin() {
        const dateDebut = dateDebutInput.value;
        const duree = parseInt(dureeInput.value) || 0;
        
        if (dateDebut && duree) {
            const debut = new Date(dateDebut);
            debut.setMonth(debut.getMonth() + duree);
            dateFinInput.value = debut.toLocaleDateString('fr-FR');
        }
    }
    
    // Écouteurs d'événements
    [montantInput, participantsInput, dureeInput, frequenceInput].forEach(input => {
        input.addEventListener('input', calculerResume);
    });
    
    [dateDebutInput, dureeInput].forEach(input => {
        input.addEventListener('input', calculerDateFin);
    });
    
    // Validation du formulaire
    form.addEventListener('submit', function(e) {
        const montant = parseInt(montantInput.value);
        const participants = parseInt(participantsInput.value);
        const duree = parseInt(dureeInput.value);
        
        if (montant < 1000) {
            e.preventDefault();
            showToast('Le montant minimum est de 1000 FCFA', 'error');
            return;
        }
        
        if (participants < 2 || participants > 50) {
            e.preventDefault();
            showToast('Le nombre de participants doit être entre 2 et 50', 'error');
            return;
        }
        
        if (duree < 1 || duree > 60) {
            e.preventDefault();
            showToast('La durée doit être entre 1 et 60 mois', 'error');
            return;
        }
        
        // Confirmation
        if (!confirm('Êtes-vous sûr de vouloir créer cette tontine ? Vous serez automatiquement inscrit comme premier participant.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
