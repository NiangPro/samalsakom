<?php
$page_title = "Détails Utilisateur";
$breadcrumb = "Utilisateurs > Détails";
include 'includes/header.php';

// Récupération de l'ID utilisateur
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header('Location: users.php');
    exit;
}

try {
    // Récupérer les informations de l'utilisateur
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: users.php');
        exit;
    }
    
    // Récupérer les tontines de l'utilisateur
    $tontines_query = "SELECT t.*, p.date_participation, p.statut as participation_statut
                       FROM participations p 
                       JOIN tontines t ON p.tontine_id = t.id 
                       WHERE p.user_id = ? 
                       ORDER BY p.date_participation DESC";
    $tontines_stmt = $db->prepare($tontines_query);
    $tontines_stmt->execute([$user_id]);
    $user_tontines = $tontines_stmt->fetchAll();
    
    // Récupérer les cotisations de l'utilisateur
    $cotisations_query = "SELECT c.*, t.nom as tontine_nom 
                          FROM cotisations c 
                          JOIN tontines t ON c.tontine_id = t.id 
                          WHERE c.user_id = ? 
                          ORDER BY c.date_cotisation DESC 
                          LIMIT 10";
    $cotisations_stmt = $db->prepare($cotisations_query);
    $cotisations_stmt->execute([$user_id]);
    $user_cotisations = $cotisations_stmt->fetchAll();
    
    // Statistiques utilisateur
    $stats_query = "SELECT 
                        COUNT(DISTINCT p.tontine_id) as nb_tontines,
                        COALESCE(SUM(c.montant), 0) as total_cotisations,
                        COUNT(c.id) as nb_cotisations
                    FROM participations p 
                    LEFT JOIN cotisations c ON p.user_id = c.user_id 
                    WHERE p.user_id = ?";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$user_id]);
    $user_stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données utilisateur.";
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Profil Utilisateur</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="users.php">Utilisateurs</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></li>
                </ol>
            </nav>
        </div>
        <div class="page-actions">
            <button class="btn-admin btn-outline btn-sm" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <button class="btn-admin btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">
                <i class="fas fa-edit"></i> Modifier
            </button>
            <button class="btn-admin btn-<?php echo $user['statut'] === 'actif' ? 'danger' : 'success'; ?> btn-sm" 
                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['statut']; ?>')">
                <i class="fas fa-<?php echo $user['statut'] === 'actif' ? 'ban' : 'check'; ?>"></i>
                <?php echo $user['statut'] === 'actif' ? 'Désactiver' : 'Activer'; ?>
            </button>
        </div>
    </div>
</div>

<!-- Informations utilisateur -->
<div class="row g-4">
    <!-- Profil utilisateur -->
    <div class="col-lg-4">
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title">Informations Personnelles</h3>
            </div>
            <div class="p-4">
                <div class="text-center mb-4">
                    <div class="profile-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                    </div>
                    <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                    <span class="status-badge status-<?php echo $user['statut'] === 'actif' ? 'active' : 'inactive'; ?>">
                        <?php echo ucfirst($user['statut']); ?>
                    </span>
                </div>
                
                <div class="user-info">
                    <div class="info-item">
                        <i class="fas fa-envelope text-primary"></i>
                        <div>
                            <strong>Email</strong>
                            <div><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-phone text-success"></i>
                        <div>
                            <strong>Téléphone</strong>
                            <div><?php echo htmlspecialchars($user['telephone']); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($user['date_naissance']): ?>
                    <div class="info-item">
                        <i class="fas fa-birthday-cake text-warning"></i>
                        <div>
                            <strong>Date de naissance</strong>
                            <div><?php echo date('d/m/Y', strtotime($user['date_naissance'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user['adresse']): ?>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt text-danger"></i>
                        <div>
                            <strong>Adresse</strong>
                            <div><?php echo htmlspecialchars($user['adresse']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar text-info"></i>
                        <div>
                            <strong>Inscription</strong>
                            <div><?php echo date('d/m/Y à H:i', strtotime($user['date_creation'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques et activité -->
    <div class="col-lg-8">
        <!-- Statistiques rapides -->
        <div class="stats-grid mb-4">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['nb_tontines']); ?></div>
                <div class="stat-label">Tontines Rejointes</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['total_cotisations']); ?></div>
                <div class="stat-label">Total Cotisations (FCFA)</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['nb_cotisations']); ?></div>
                <div class="stat-label">Transactions</div>
            </div>
        </div>
        
        <!-- Tontines de l'utilisateur -->
        <div class="data-table mb-4">
            <div class="table-header">
                <h3 class="table-title">Tontines Participées</h3>
                <span class="badge bg-primary"><?php echo count($user_tontines); ?></span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tontine</th>
                            <th>Montant</th>
                            <th>Date Participation</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_tontines)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Aucune tontine trouvée
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($user_tontines as $tontine): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-semibold text-primary"><?php echo htmlspecialchars($tontine['nom']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($tontine['description'], 0, 50)) . '...'; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold"><?php echo number_format($tontine['montant_par_personne']); ?> FCFA</span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($tontine['date_participation'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $tontine['participation_statut'] === 'active' ? 'active' : 'pending'; ?>">
                                        <?php echo ucfirst($tontine['participation_statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewTontine(<?php echo $tontine['id']; ?>)"
                                            data-bs-toggle="tooltip" title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                <span class="badge bg-success"><?php echo count($user_cotisations); ?></span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tontine</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_cotisations)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-receipt fa-2x mb-2 d-block"></i>
                                Aucune cotisation trouvée
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($user_cotisations as $cotisation): ?>
                            <tr>
                                <td>
                                    <span class="fw-medium"><?php echo htmlspecialchars($cotisation['tontine_nom']); ?></span>
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

<style>
.user-info {
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

.page-actions {
    display: flex;
    gap: 0.5rem;
}

.breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
    font-size: 0.9rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.breadcrumb a {
    color: var(--admin-primary);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}
</style>

<script>
function editUser(userId) {
    showToast('Fonctionnalité d\'édition en cours de développement', 'info');
}

function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'actif' ? 'inactif' : 'actif';
    const action = newStatus === 'actif' ? 'activer' : 'désactiver';
    
    if (confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) {
        fetch('actions/toggle_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
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

function viewTontine(tontineId) {
    window.location.href = `tontine-details.php?id=${tontineId}`;
}
</script>

<?php include 'includes/footer.php'; ?>
