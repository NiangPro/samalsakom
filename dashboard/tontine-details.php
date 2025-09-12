<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tontine_id = (int)($_GET['id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!$tontine_id) {
        header('Location: mes-tontines.php');
        exit;
    }
    
    // Récupérer les détails de la tontine
    $query = "SELECT t.*, 
              COUNT(p.id) as participants_actuels,
              p.date_participation,
              p.statut as participation_statut
              FROM tontines t
              LEFT JOIN participations p ON t.id = p.tontine_id AND p.statut != 'retire'
              LEFT JOIN participations p2 ON t.id = p2.tontine_id AND p2.user_id = ? AND p2.statut != 'retire'
              WHERE t.id = ?
              GROUP BY t.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $tontine_id]);
    $tontine = $stmt->fetch();
    
    if (!$tontine) {
        header('Location: mes-tontines.php?error=tontine_not_found');
        exit;
    }
    
    // Vérifier si l'utilisateur participe à cette tontine
    $participation_query = "SELECT * FROM participations WHERE tontine_id = ? AND user_id = ? AND statut != 'retire'";
    $stmt = $db->prepare($participation_query);
    $stmt->execute([$tontine_id, $user_id]);
    $ma_participation = $stmt->fetch();
    
    // Récupérer les participants
    $participants_query = "SELECT u.nom, u.prenom, u.email, p.date_participation, p.statut
                          FROM participations p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.tontine_id = ? AND p.statut != 'retire'
                          ORDER BY p.date_participation ASC";
    $stmt = $db->prepare($participants_query);
    $stmt->execute([$tontine_id]);
    $participants = $stmt->fetchAll();
    
    // Récupérer mes cotisations pour cette tontine
    $cotisations_query = "SELECT * FROM cotisations 
                         WHERE tontine_id = ? AND user_id = ? 
                         ORDER BY date_cotisation ASC";
    $stmt = $db->prepare($cotisations_query);
    $stmt->execute([$tontine_id, $user_id]);
    $mes_cotisations = $stmt->fetchAll();
    
    // Calculer les statistiques
    $total_cotise = array_sum(array_column(array_filter($mes_cotisations, function($c) { 
        return $c['statut'] === 'completed'; 
    }), 'montant'));
    
    $cotisations_pending = array_filter($mes_cotisations, function($c) { 
        return $c['statut'] === 'pending'; 
    });
    
} catch (Exception $e) {
    error_log("Erreur tontine-details: " . $e->getMessage());
    $error_message = "Erreur de chargement des données";
    $tontine = null;
    $ma_participation = null;
    $participants = [];
    $mes_cotisations = [];
    $total_cotise = 0;
    $cotisations_pending = [];
}

include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($tontine): ?>
            <!-- En-tête de page -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="mes-tontines.php">Mes Tontines</a></li>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($tontine['nom']) ?></li>
                            </ol>
                        </nav>
                        <h1 class="page-title">
                            <i class="fas fa-piggy-bank me-3"></i>
                            <?= htmlspecialchars($tontine['nom']) ?>
                        </h1>
                        <p class="page-description"><?= htmlspecialchars($tontine['description']) ?></p>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group">
                            <?php if ($ma_participation && $tontine['statut'] === 'active'): ?>
                                <a href="paiements.php?tontine=<?= $tontine['id'] ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card me-2"></i>Payer Cotisation
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-primary" onclick="partager_tontine(<?= $tontine['id'] ?>, '<?= htmlspecialchars($tontine['nom']) ?>')">
                                <i class="fas fa-share me-2"></i>Partager
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerte pour cotisations en retard -->
            <?php 
            $cotisations_retard = array_filter($cotisations_pending, function($c) {
                return strtotime($c['date_cotisation']) < time();
            });
            if (!empty($cotisations_retard)): 
            ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Cotisations en retard !</h5>
                            <p class="mb-2">Vous avez <?= count($cotisations_retard) ?> cotisation(s) en retard pour cette tontine.</p>
                            <a href="paiements.php?tontine=<?= $tontine['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-credit-card me-2"></i>Payer maintenant
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Informations principales -->
                <div class="col-lg-8">
                    <div class="card modern-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Informations de la Tontine
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Montant par cotisation</label>
                                        <div class="info-value text-success">
                                            <?= number_format($tontine['montant_cotisation'], 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>Fréquence</label>
                                        <div class="info-value">
                                            <i class="fas fa-calendar me-2"></i>
                                            <?= ucfirst($tontine['frequence']) ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>Participants</label>
                                        <div class="info-value">
                                            <i class="fas fa-users me-2"></i>
                                            <?= $tontine['participants_actuels'] ?> / <?= $tontine['nombre_participants'] ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Statut</label>
                                        <div class="info-value">
                                            <span class="badge bg-<?= $tontine['statut'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($tontine['statut']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>Date de début</label>
                                        <div class="info-value">
                                            <i class="fas fa-calendar-start me-2"></i>
                                            <?= $tontine['date_debut'] ? date('d/m/Y', strtotime($tontine['date_debut'])) : 'Non définie' ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>Durée</label>
                                        <div class="info-value">
                                            <i class="fas fa-clock me-2"></i>
                                            <?= $tontine['duree_mois'] ?? 12 ?> mois
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mes cotisations -->
                    <div class="card modern-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>
                                Mes Cotisations
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($mes_cotisations)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date d'échéance</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Date de paiement</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mes_cotisations as $cotisation): ?>
                                                <tr class="<?= strtotime($cotisation['date_cotisation']) < time() && $cotisation['statut'] === 'pending' ? 'table-warning' : '' ?>">
                                                    <td>
                                                        <div class="fw-semibold"><?= date('d/m/Y', strtotime($cotisation['date_cotisation'])) ?></div>
                                                        <?php if (strtotime($cotisation['date_cotisation']) < time() && $cotisation['statut'] === 'pending'): ?>
                                                            <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>En retard</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">
                                                            <?= number_format($cotisation['montant'], 0, ',', ' ') ?> FCFA
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'pending' => 'warning',
                                                            'completed' => 'success',
                                                            'failed' => 'danger',
                                                            'cancelled' => 'secondary'
                                                        ][$cotisation['statut']] ?? 'secondary';
                                                        
                                                        $status_text = [
                                                            'pending' => 'En attente',
                                                            'completed' => 'Payé',
                                                            'failed' => 'Échoué',
                                                            'cancelled' => 'Annulé'
                                                        ][$cotisation['statut']] ?? $cotisation['statut'];
                                                        ?>
                                                        <span class="badge bg-<?= $status_class ?>">
                                                            <?= $status_text ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $cotisation['date_paiement'] ? date('d/m/Y H:i', strtotime($cotisation['date_paiement'])) : '-' ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($cotisation['statut'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="payer_cotisation(<?= $cotisation['id'] ?>)">
                                                                <i class="fas fa-credit-card"></i> Payer
                                                            </button>
                                                        <?php elseif ($cotisation['statut'] === 'completed'): ?>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="voirRecu(<?= $cotisation['id'] ?>)">
                                                                <i class="fas fa-receipt"></i> Reçu
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune cotisation programmée</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Ma participation -->
                    <?php if ($ma_participation): ?>
                        <div class="card modern-card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Ma Participation</h6>
                            </div>
                            <div class="card-body">
                                <div class="participation-stats">
                                    <div class="stat-item">
                                        <div class="stat-value text-success"><?= number_format($total_cotise, 0, ',', ' ') ?> FCFA</div>
                                        <div class="stat-label">Total cotisé</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value text-warning"><?= count($cotisations_pending) ?></div>
                                        <div class="stat-label">Cotisations en attente</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value text-info"><?= date('d/m/Y', strtotime($ma_participation['date_participation'])) ?></div>
                                        <div class="stat-label">Date d'adhésion</div>
                                    </div>
                                </div>
                                
                                <?php if ($tontine['statut'] === 'active' && $ma_participation['statut'] === 'confirme'): ?>
                                    <hr>
                                    <button class="btn btn-outline-danger btn-sm w-100" onclick="quitter_tontine(<?= $tontine['id'] ?>)">
                                        <i class="fas fa-sign-out-alt me-2"></i>Quitter la tontine
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Liste des participants -->
                    <div class="card modern-card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                Participants (<?= count($participants) ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($participants)): ?>
                                <div class="participants-list">
                                    <?php foreach ($participants as $participant): ?>
                                        <div class="participant-item">
                                            <div class="participant-avatar">
                                                <?= strtoupper(substr($participant['prenom'], 0, 1) . substr($participant['nom'], 0, 1)) ?>
                                            </div>
                                            <div class="participant-info">
                                                <div class="participant-name">
                                                    <?= htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']) ?>
                                                    <?php if (isset($participant['email']) && isset($_SESSION['email']) && $participant['email'] === $_SESSION['email']): ?>
                                                        <span class="badge bg-primary ms-1">Moi</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="participant-date">
                                                    Rejoint le <?= date('d/m/Y', strtotime($participant['date_participation'])) ?>
                                                </div>
                                            </div>
                                            <div class="participant-status">
                                                <span class="badge bg-<?= $participant['statut'] === 'confirme' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($participant['statut']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Aucun participant</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h3>Tontine non trouvée</h3>
                <p class="text-muted">La tontine que vous recherchez n'existe pas ou n'est plus disponible.</p>
                <a href="mes-tontines.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à mes tontines
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.info-item {
    margin-bottom: 1.5rem;
}

.info-item label {
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
    display: block;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-color);
}

.participation-stats .stat-item {
    text-align: center;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.participants-list {
    max-height: 400px;
    overflow-y: auto;
}

.participant-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.participant-item:last-child {
    border-bottom: none;
}

.participant-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.85rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.participant-info {
    flex: 1;
}

.participant-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.participant-date {
    font-size: 0.8rem;
    color: var(--gray-600);
}

.participant-status {
    flex-shrink: 0;
}
</style>

<script>
function voirRecu(cotisationId) {
    window.open(`actions/generate_receipt.php?id=${cotisationId}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>
