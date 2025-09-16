<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de tontine manquant']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tontine_id = (int)$_GET['id'];
    
    // Récupérer les détails de la tontine avec informations du créateur
    $query = "SELECT t.*, 
                     u.prenom as createur_prenom, u.nom as createur_nom, u.email as createur_email,
                     COUNT(p.id) as participants_actuels,
                     (t.nombre_participants - COUNT(p.id)) as places_restantes,
                     CASE WHEN up.user_id IS NOT NULL THEN 1 ELSE 0 END as deja_participant
              FROM tontines t 
              LEFT JOIN users u ON t.createur_id = u.id
              LEFT JOIN participations p ON t.id = p.tontine_id AND p.statut != 'retire'
              LEFT JOIN participations up ON t.id = up.tontine_id AND up.user_id = ?
              WHERE t.id = ?
              GROUP BY t.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $tontine_id]);
    $tontine = $stmt->fetch();
    
    if (!$tontine) {
        echo json_encode(['success' => false, 'message' => 'Tontine non trouvée']);
        exit;
    }
    
    // Récupérer la liste des participants
    $participants_query = "SELECT u.prenom, u.nom, p.date_participation, p.statut
                          FROM participations p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.tontine_id = ? AND p.statut != 'retire'
                          ORDER BY p.date_participation ASC";
    $stmt = $db->prepare($participants_query);
    $stmt->execute([$tontine_id]);
    $participants = $stmt->fetchAll();
    
    // Calculer les dates importantes
    $date_debut = new DateTime($tontine['date_debut']);
    $date_fin = new DateTime($tontine['date_fin']);
    $now = new DateTime();
    
    $pourcentage_rempli = ($tontine['participants_actuels'] / $tontine['nombre_participants']) * 100;
    
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-8">
            <h4><?= htmlspecialchars($tontine['nom']) ?></h4>
            <p class="text-muted mb-3"><?= htmlspecialchars($tontine['description']) ?></p>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-icon bg-success">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <div class="info-label">Montant de cotisation</div>
                            <div class="info-value"><?= number_format($tontine['montant_cotisation'], 0, ',', ' ') ?> FCFA</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-icon bg-info">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div>
                            <div class="info-label">Fréquence</div>
                            <div class="info-value"><?= ucfirst($tontine['frequence']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="info-label">Participants</div>
                            <div class="info-value"><?= $tontine['participants_actuels'] ?>/<?= $tontine['nombre_participants'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="info-label">Durée</div>
                            <div class="info-value"><?= $date_debut->format('d/m/Y') ?> - <?= $date_fin->format('d/m/Y') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h6>Progression</h6>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?= $pourcentage_rempli ?>%"></div>
                </div>
                <small class="text-muted"><?= round($pourcentage_rempli, 1) ?>% rempli (<?= $tontine['places_restantes'] ?> place<?= $tontine['places_restantes'] > 1 ? 's' : '' ?> restante<?= $tontine['places_restantes'] > 1 ? 's' : '' ?>)</small>
            </div>
            
            <?php if (!empty($participants)): ?>
            <div class="mb-4">
                <h6>Participants (<?= count($participants) ?>)</h6>
                <div class="participants-list">
                    <?php foreach ($participants as $participant): ?>
                    <div class="participant-item">
                        <div class="participant-avatar">
                            <?= strtoupper(substr($participant['prenom'], 0, 1) . substr($participant['nom'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="participant-name"><?= htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']) ?></div>
                            <small class="text-muted">Rejoint le <?= date('d/m/Y', strtotime($participant['date_participation'])) ?></small>
                        </div>
                        <span class="badge bg-success">Actif</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="organizer-card">
                <h6>Organisateur</h6>
                <div class="organizer-info">
                    <div class="organizer-avatar">
                        <?= strtoupper(substr($tontine['createur_prenom'], 0, 1) . substr($tontine['createur_nom'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="organizer-name"><?= htmlspecialchars($tontine['createur_prenom'] . ' ' . $tontine['createur_nom']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($tontine['createur_email']) ?></small>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons mt-3">
                <?php if ($tontine['deja_participant']): ?>
                    <button class="btn btn-outline-success w-100 mb-2" disabled>
                        <i class="fas fa-check"></i> Déjà participant
                    </button>
                <?php elseif ($tontine['places_restantes'] <= 0): ?>
                    <button class="btn btn-outline-secondary w-100 mb-2" disabled>
                        <i class="fas fa-users"></i> Tontine complète
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary w-100 mb-2" onclick="rejoindre(<?= $tontine['id'] ?>)" data-bs-dismiss="modal">
                        <i class="fas fa-plus"></i> Rejoindre cette tontine
                    </button>
                <?php endif; ?>
                
                <button class="btn btn-outline-secondary w-100" onclick="partager(<?= $tontine['id'] ?>)">
                    <i class="fas fa-share"></i> Partager
                </button>
            </div>
        </div>
    </div>
    
    <style>
    .info-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid var(--primary-color);
    }
    
    .info-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        font-weight: 600;
        color: #212529;
    }
    
    .participants-list {
        max-height: 200px;
        overflow-y: auto;
    }
    
    .participant-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .participant-item:last-child {
        border-bottom: none;
    }
    
    .participant-avatar, .organizer-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .participant-name, .organizer-name {
        font-weight: 600;
        color: #212529;
    }
    
    .organizer-card {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .organizer-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    </style>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_tontine_details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors du chargement des détails']);
}
?>
