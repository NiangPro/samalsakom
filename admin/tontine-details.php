<?php
$page_title = "Détails Tontine";
$breadcrumb = "Tontines > Détails";
include 'includes/header.php';

// Récupération de l'ID tontine
$tontine_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tontine_id) {
    header('Location: tontines.php');
    exit;
}

try {
    // Récupérer les informations de la tontine avec le créateur
    $query = "SELECT t.*, u.prenom, u.nom, u.email, u.telephone 
              FROM tontines t 
              LEFT JOIN users u ON t.createur_id = u.id 
              WHERE t.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$tontine_id]);
    $tontine = $stmt->fetch();
    
    if (!$tontine) {
        header('Location: tontines.php');
        exit;
    }
    
    // Récupérer les participants
    $participants_query = "SELECT p.*, u.prenom, u.nom, u.email, u.telephone, u.statut as user_statut
                          FROM participations p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.tontine_id = ? 
                          ORDER BY p.date_participation ASC";
    $participants_stmt = $db->prepare($participants_query);
    $participants_stmt->execute([$tontine_id]);
    $participants = $participants_stmt->fetchAll();
    
    // Récupérer les cotisations
    $cotisations_query = "SELECT c.*, u.prenom, u.nom 
                         FROM cotisations c 
                         JOIN users u ON c.user_id = u.id 
                         WHERE c.tontine_id = ? 
                         ORDER BY c.date_cotisation DESC";
    $cotisations_stmt = $db->prepare($cotisations_query);
    $cotisations_stmt->execute([$tontine_id]);
    $cotisations = $cotisations_stmt->fetchAll();
    
    // Statistiques de la tontine
    $stats_query = "SELECT 
                        COUNT(DISTINCT p.user_id) as nb_participants,
                        COALESCE(SUM(c.montant), 0) as total_cotisations,
                        COUNT(c.id) as nb_cotisations
                    FROM participations p 
                    LEFT JOIN cotisations c ON p.tontine_id = c.tontine_id 
                    WHERE p.tontine_id = ?";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$tontine_id]);
    $tontine_stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données de la tontine.";
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Détails de la Tontine</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="tontines.php">Tontines</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($tontine['nom']); ?></li>
                </ol>
            </nav>
        </div>
        <div class="page-actions">
            <button class="btn-admin btn-outline btn-sm" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <button class="btn-admin btn-warning btn-sm" onclick="editTontine(<?php echo $tontine['id']; ?>)">
                <i class="fas fa-edit"></i> Modifier
            </button>
            <div class="btn-group">
                <button class="btn-admin btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i> Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'active')">
                        <i class="fas fa-play text-success me-2"></i>Activer
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'suspendue')">
                        <i class="fas fa-pause text-warning me-2"></i>Suspendre
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="changeTontineStatus(<?php echo $tontine['id']; ?>, 'terminee')">
                        <i class="fas fa-stop text-danger me-2"></i>Terminer
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteTontine(<?php echo $tontine['id']; ?>)">
                        <i class="fas fa-trash me-2"></i>Supprimer
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Informations tontine -->
<div class="row g-4">
    <!-- Informations générales -->
    <div class="col-lg-4">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Informations Générales</h3>
                <?php
                $status_class = '';
                switch($tontine['statut']) {
                    case 'active': $status_class = 'success'; break;
                    case 'en_attente': $status_class = 'warning'; break;
                    case 'terminee': $status_class = 'info'; break;
                    case 'suspendue': $status_class = 'danger'; break;
                    default: $status_class = 'secondary';
                }
                ?>
                <span class="badge bg-<?php echo $status_class; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $tontine['statut'])); ?>
                </span>
            </div>
            <div class="p-4">
                <div class="tontine-info">
                    <div class="info-item">
                        <i class="fas fa-piggy-bank text-primary"></i>
                        <div>
                            <strong>Nom</strong>
                            <div><?php echo htmlspecialchars($tontine['nom']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-align-left text-info"></i>
                        <div>
                            <strong>Description</strong>
                            <div><?php echo htmlspecialchars($tontine['description']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-coins text-success"></i>
                        <div>
                            <strong>Montant par personne</strong>
                            <div class="fw-semibold text-success"><?php echo number_format($tontine['montant_cotisation']); ?> FCFA</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-users text-warning"></i>
                        <div>
                            <strong>Participants</strong>
                            <div><?php echo $tontine_stats['nb_participants']; ?> / <?php echo $tontine['nombre_participants']; ?></div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-warning" 
                                     style="width: <?php echo ($tontine_stats['nb_participants'] / $tontine['nombre_participants']) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock text-secondary"></i>
                        <div>
                            <strong>Fréquence</strong>
                            <div><?php echo ucfirst($tontine['frequence']); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($tontine['date_debut']): ?>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <div>
                            <strong>Date de début</strong>
                            <div><?php echo date('d/m/Y', strtotime($tontine['date_debut'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar text-info"></i>
                        <div>
                            <strong>Créée le</strong>
                            <div><?php echo date('d/m/Y à H:i', strtotime($tontine['date_creation'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Créateur -->
        <?php if ($tontine['prenom']): ?>
        <div class="data-table mt-4">
            <div class="table-header">
                <h3 class="table-title">Créateur</h3>
            </div>
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar me-3" style="width: 50px; height: 50px;">
                        <?php echo strtoupper(substr($tontine['prenom'], 0, 1) . substr($tontine['nom'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($tontine['prenom'] . ' ' . $tontine['nom']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($tontine['email']); ?></small>
                        <div><small class="text-muted"><?php echo htmlspecialchars($tontine['telephone']); ?></small></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Statistiques et activité -->
    <div class="col-lg-8">
        <!-- Statistiques rapides -->
        <div class="stats-grid mb-4">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($tontine_stats['nb_participants']); ?></div>
                <div class="stat-label">Participants Actifs</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($tontine_stats['total_cotisations']); ?></div>
                <div class="stat-label">Total Cotisations (FCFA)</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($tontine_stats['nb_cotisations']); ?></div>
                <div class="stat-label">Transactions</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php echo round(($tontine_stats['nb_participants'] / $tontine['nombre_participants']) * 100); ?>%
                </div>
                <div class="stat-label">Taux de Remplissage</div>
            </div>
        </div>
        
        <!-- Participants -->
        <div class="data-table mb-4">
            <div class="table-header">
                <h3 class="table-title">Participants</h3>
                <div class="table-actions">
                    <button class="btn-admin btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
                        <i class="fas fa-plus"></i> Ajouter Participant
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Contact</th>
                            <th>Date Participation</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($participants)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                Aucun participant trouvé
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="profile-avatar me-3" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                            <?php echo strtoupper(substr($participant['prenom'], 0, 1) . substr($participant['nom'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']); ?></div>
                                            <span class="status-badge status-<?php echo $participant['user_statut'] === 'actif' ? 'active' : 'inactive'; ?> small">
                                                <?php echo ucfirst($participant['user_statut']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><?php echo htmlspecialchars($participant['email']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($participant['telephone']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($participant['date_participation'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $participant['statut'] === 'active' ? 'active' : 'pending'; ?>">
                                        <?php echo ucfirst($participant['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewUser(<?php echo $participant['user_id']; ?>)"
                                                data-bs-toggle="tooltip" title="Voir profil">
                                            <i class="fas fa-user"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="removeParticipant(<?php echo $participant['id']; ?>)"
                                                data-bs-toggle="tooltip" title="Retirer">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Historique des cotisations -->
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Historique des Cotisations</h3>
                <span class="badge bg-success"><?php echo count($cotisations); ?></span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cotisations)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-receipt fa-2x mb-2 d-block"></i>
                                Aucune cotisation trouvée
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($cotisations as $cotisation): ?>
                            <tr>
                                <td>
                                    <span class="fw-medium"><?php echo htmlspecialchars($cotisation['prenom'] . ' ' . $cotisation['nom']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-success"><?php echo number_format($cotisation['montant']); ?> FCFA</span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cotisation['date_cotisation'])); ?></td>
                                <td>
                                    <span class="status-badge status-active">Payée</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Participant -->
<div class="modal fade" id="addParticipantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="ajax-form" action="actions/add_participant.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="tontine_id" value="<?php echo $tontine['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Utilisateur *</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Sélectionner un utilisateur...</option>
                            <?php
                            // Récupérer les utilisateurs qui ne participent pas encore à cette tontine
                            $available_users_query = "SELECT u.id, u.prenom, u.nom, u.email 
                                                     FROM users u 
                                                     WHERE u.statut = 'actif' 
                                                     AND u.id NOT IN (
                                                         SELECT p.user_id FROM participations p WHERE p.tontine_id = ?
                                                     )
                                                     ORDER BY u.prenom, u.nom";
                            $available_users_stmt = $db->prepare($available_users_query);
                            $available_users_stmt->execute([$tontine['id']]);
                            $available_users = $available_users_stmt->fetchAll();
                            
                            foreach ($available_users as $user):
                            ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut initial</label>
                        <select class="form-select" name="statut">
                            <option value="active">Actif</option>
                            <option value="en_attente">En attente</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter le participant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.tontine-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    width: 20px;
    margin-top: 0.25rem;
}

.info-item strong {
    display: block;
    color: var(--admin-primary);
    margin-bottom: 0.25rem;
}

.progress {
    background-color: #e9ecef;
}

.status-badge.small {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}
</style>

<script>
function editTontine(tontineId) {
    showToast('Fonctionnalité d\'édition en cours de développement', 'info');
}

function changeTontineStatus(tontineId, newStatus) {
    const statusNames = {
        'active': 'activer',
        'suspendue': 'suspendre',
        'terminee': 'terminer'
    };
    
    if (confirm(`Êtes-vous sûr de vouloir ${statusNames[newStatus]} cette tontine ?`)) {
        fetch('actions/change_tontine_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tontine_id: tontineId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la modification', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}

function deleteTontine(tontineId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tontine ? Cette action est irréversible.')) {
        fetch('actions/delete_tontine.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tontine_id: tontineId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    window.location.href = 'tontines.php';
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la suppression', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}

function viewUser(userId) {
    window.location.href = `user-details.php?id=${userId}`;
}

function removeParticipant(participationId) {
    if (confirm('Êtes-vous sûr de vouloir retirer ce participant de la tontine ?')) {
        fetch('actions/remove_participant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                participation_id: participationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la suppression', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
