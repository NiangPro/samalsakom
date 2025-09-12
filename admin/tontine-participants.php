<?php
$page_title = "Gestion des Participants";
$breadcrumb = "Tontines > Participants";
include 'includes/header.php';

// Récupération de l'ID tontine
$tontine_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tontine_id) {
    header('Location: tontines.php');
    exit;
}

try {
    // Récupérer les informations de la tontine
    $query = "SELECT t.*, u.prenom as createur_prenom, u.nom as createur_nom 
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
    
    // Récupérer les participants actuels
    $participants_query = "SELECT p.*, u.prenom, u.nom, u.email, u.telephone, u.photo_profil 
                          FROM participations p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.tontine_id = ? AND p.statut != 'retire' 
                          ORDER BY p.date_participation ASC";
    $participants_stmt = $db->prepare($participants_query);
    $participants_stmt->execute([$tontine_id]);
    $participants = $participants_stmt->fetchAll();
    
    // Récupérer les utilisateurs qui ne sont pas encore participants
    $non_participants_query = "SELECT u.* FROM users u 
                              WHERE u.statut = 'actif' AND u.id NOT IN 
                              (SELECT p.user_id FROM participations p WHERE p.tontine_id = ? AND p.statut != 'retire') 
                              ORDER BY u.prenom, u.nom";
    $non_participants_stmt = $db->prepare($non_participants_query);
    $non_participants_stmt->execute([$tontine_id]);
    $non_participants = $non_participants_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
}
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Gestion des Participants</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="tontines.php">Tontines</a></li>
                    <li class="breadcrumb-item"><a href="tontine-details.php?id=<?php echo $tontine_id; ?>"><?php echo htmlspecialchars($tontine['nom']); ?></a></li>
                    <li class="breadcrumb-item active">Participants</li>
                </ol>
            </nav>
        </div>
        <div class="page-actions">
            <button class="btn-admin btn-outline btn-sm" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <button class="btn-admin btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
                <i class="fas fa-user-plus"></i> Ajouter un participant
            </button>
        </div>
    </div>
</div>

<!-- Informations de la tontine -->
<div class="card-modern mb-4">
    <div class="card-header">
        <h3 class="card-title">Informations de la tontine</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-details">
                    <tr>
                        <th>Nom:</th>
                        <td><?php echo htmlspecialchars($tontine['nom']); ?></td>
                    </tr>
                    <tr>
                        <th>Créateur:</th>
                        <td><?php echo htmlspecialchars($tontine['createur_prenom'] . ' ' . $tontine['createur_nom']); ?></td>
                    </tr>
                    <tr>
                        <th>Montant:</th>
                        <td><?php echo number_format($tontine['montant_cotisation'], 0, ',', ' '); ?> FCFA</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-details">
                    <tr>
                        <th>Fréquence:</th>
                        <td><?php echo ucfirst($tontine['frequence']); ?></td>
                    </tr>
                    <tr>
                        <th>Statut:</th>
                        <td>
                            <span class="status-badge status-<?php echo $tontine['statut'] == 'active' ? 'active' : ($tontine['statut'] == 'en_attente' ? 'pending' : 'inactive'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $tontine['statut'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Participants:</th>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-2"><?php echo count($participants); ?></span>
                                <small class="text-muted">/ <?php echo $tontine['nombre_participants']; ?> max</small>
                            </div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar" style="width: <?php echo (count($participants) / $tontine['nombre_participants']) * 100; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Liste des participants -->
<div class="card-modern mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Participants actuels (<?php echo count($participants); ?>)</h3>
        <div class="card-actions">
            <div class="input-group" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Rechercher un participant..." id="searchParticipants">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="admin-table" id="participantsTable">
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Contact</th>
                        <th>Position</th>
                        <th>Statut</th>
                        <th>Date d'adhésion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($participants)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">Aucun participant pour le moment</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($participants as $participant): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <?php if ($participant['photo_profil']): ?>
                                        <img src="../uploads/profiles/<?php echo htmlspecialchars($participant['photo_profil']); ?>" alt="Photo de profil">
                                    <?php else: ?>
                                        <div class="avatar-initials"><?php echo substr($participant['prenom'], 0, 1) . substr($participant['nom'], 0, 1); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div><i class="fas fa-envelope me-1 text-muted"></i> <?php echo htmlspecialchars($participant['email']); ?></div>
                                <div><i class="fas fa-phone me-1 text-muted"></i> <?php echo htmlspecialchars($participant['telephone']); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $participant['position_tirage'] ? $participant['position_tirage'] : 'Non défini'; ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $participant['statut'] == 'confirme' ? 'active' : 'pending'; ?>">
                                <?php echo ucfirst($participant['statut']); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <div><?php echo date('d/m/Y', strtotime($participant['date_participation'])); ?></div>
                                <small class="text-muted"><?php echo date('H:i', strtotime($participant['date_participation'])); ?></small>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="window.location.href='user-details.php?id=<?php echo $participant['user_id']; ?>'" 
                                        data-bs-toggle="tooltip" title="Voir profil">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="editPosition(<?php echo $participant['id']; ?>, <?php echo $participant['position_tirage'] ?: 'null'; ?>)" 
                                        data-bs-toggle="tooltip" title="Modifier position">
                                    <i class="fas fa-sort-numeric-down"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="removeParticipant(<?php echo $participant['id']; ?>, '<?php echo htmlspecialchars(addslashes($participant['prenom'] . ' ' . $participant['nom'])); ?>')" 
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
</div>

<!-- Modal Ajouter Participant -->
<div class="modal fade" id="addParticipantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addParticipantForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sélectionner un utilisateur</label>
                        <select class="form-select" id="userId" name="user_id" required>
                            <option value="">Choisir un utilisateur...</option>
                            <?php foreach ($non_participants as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position (optionnel)</label>
                        <input type="number" class="form-control" id="position" name="position" min="1" max="<?php echo $tontine['nombre_participants']; ?>">
                        <small class="form-text text-muted">Laissez vide pour une attribution aléatoire ultérieure</small>
                    </div>
                    <input type="hidden" name="tontine_id" value="<?php echo $tontine_id; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Position -->
<div class="modal fade" id="editPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPositionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nouvelle position</label>
                        <input type="number" class="form-control" id="newPosition" name="position" min="1" max="<?php echo $tontine['nombre_participants']; ?>" required>
                    </div>
                    <input type="hidden" id="participationId" name="participation_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Recherche de participants
document.getElementById('searchParticipants').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#participantsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Ajouter un participant
document.getElementById('addParticipantForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('userId').value;
    const position = document.getElementById('position').value;
    const tontineId = <?php echo $tontine_id; ?>;
    
    fetch('actions/add_participant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            tontine_id: tontineId,
            position: position || null
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
            showToast(data.message || 'Erreur lors de l\'ajout du participant', 'danger');
        }
    })
    .catch(error => {
        showToast('Erreur de connexion', 'danger');
    });
});

// Modifier la position d'un participant
function editPosition(participationId, currentPosition) {
    document.getElementById('participationId').value = participationId;
    document.getElementById('newPosition').value = currentPosition || '';
    
    const editPositionModal = new bootstrap.Modal(document.getElementById('editPositionModal'));
    editPositionModal.show();
}

document.getElementById('editPositionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const participationId = document.getElementById('participationId').value;
    const newPosition = document.getElementById('newPosition').value;
    
    // Implémenter l'action pour modifier la position
    showToast('Fonctionnalité de modification de position en cours de développement', 'info');
    
    // Fermer le modal
    const editPositionModal = bootstrap.Modal.getInstance(document.getElementById('editPositionModal'));
    editPositionModal.hide();
});

// Retirer un participant
function removeParticipant(participationId, participantName) {
    if (confirm(`Êtes-vous sûr de vouloir retirer ${participantName} de cette tontine ?`)) {
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
                showToast(data.message || 'Erreur lors du retrait du participant', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>