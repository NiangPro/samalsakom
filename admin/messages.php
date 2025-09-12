<?php
$page_title = "Gestion des Messages";
$breadcrumb = "Messages";
include 'includes/header.php';

// Récupération des messages avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Construction de la requête avec filtre
    $where_clause = '';
    $params = [];
    
    if ($status_filter) {
        $where_clause = 'WHERE statut = ?';
        $params[] = $status_filter;
    }
    
    // Compter le total de messages
    $count_query = "SELECT COUNT(*) as total FROM contacts $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_messages = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_messages / $limit);
    
    // Récupérer les messages
    $query = "SELECT * FROM contacts $where_clause ORDER BY date_creation DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des messages.";
}
?>

<div class="page-header">
    <h1 class="page-title">Gestion des Messages</h1>
    <p class="page-subtitle">Gérez tous les messages de contact reçus sur la plateforme</p>
</div>

<!-- Statistiques rapides -->
<div class="stats-grid mb-4">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $total_query = "SELECT COUNT(*) as count FROM contacts";
            $total_stmt = $db->prepare($total_query);
            $total_stmt->execute();
            echo number_format($total_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Total Messages</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-envelope-open"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $new_query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'";
            $new_stmt = $db->prepare($new_query);
            $new_stmt->execute();
            echo number_format($new_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Nouveaux Messages</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $read_query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'lu'";
            $read_stmt = $db->prepare($read_query);
            $read_stmt->execute();
            echo number_format($read_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Messages Lus</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-reply"></i>
            </div>
        </div>
        <div class="stat-value">
            <?php
            $replied_query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'repondu'";
            $replied_stmt = $db->prepare($replied_query);
            $replied_stmt->execute();
            echo number_format($replied_stmt->fetch()['count']);
            ?>
        </div>
        <div class="stat-label">Messages Répondus</div>
    </div>
</div>

<!-- Filtres et actions -->
<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">Liste des Messages</h3>
        <div class="table-actions">
            <div class="d-flex gap-2">
                <select class="form-select" id="statusFilter" onchange="filterMessages()" style="width: auto;">
                    <option value="">Tous les statuts</option>
                    <option value="nouveau" <?php echo $status_filter === 'nouveau' ? 'selected' : ''; ?>>Nouveaux</option>
                    <option value="lu" <?php echo $status_filter === 'lu' ? 'selected' : ''; ?>>Lus</option>
                    <option value="repondu" <?php echo $status_filter === 'repondu' ? 'selected' : ''; ?>>Répondus</option>
                </select>
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Rechercher un message..." id="searchMessages">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <button class="btn-admin btn-success" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Expéditeur
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Sujet
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Date
                    </th>
                    <th class="sortable">
                        <i class="fas fa-sort me-1"></i>Statut
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                <tr class="message-row <?php echo $message['statut'] === 'nouveau' ? 'table-warning' : ''; ?>" 
                    data-message-id="<?php echo $message['id']; ?>">
                    <td>
                        <input type="checkbox" class="message-checkbox" value="<?php echo $message['id']; ?>">
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="profile-avatar me-3" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                <?php echo strtoupper(substr($message['nom'], 0, 2)); ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($message['nom']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="message-subject" onclick="viewMessage(<?php echo $message['id']; ?>)" style="cursor: pointer;">
                            <div class="fw-medium text-primary"><?php echo htmlspecialchars($message['sujet']); ?></div>
                            <small class="text-muted message-preview">
                                <?php echo htmlspecialchars(substr($message['message'], 0, 80)) . '...'; ?>
                            </small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div><?php echo date('d/m/Y', strtotime($message['date_creation'])); ?></div>
                            <small class="text-muted"><?php echo date('H:i', strtotime($message['date_creation'])); ?></small>
                        </div>
                    </td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch($message['statut']) {
                            case 'nouveau': 
                                $status_class = 'warning'; 
                                $status_text = 'Nouveau';
                                break;
                            case 'lu': 
                                $status_class = 'info'; 
                                $status_text = 'Lu';
                                break;
                            case 'repondu': 
                                $status_class = 'success'; 
                                $status_text = 'Répondu';
                                break;
                            default: 
                                $status_class = 'secondary';
                                $status_text = 'Inconnu';
                        }
                        ?>
                        <span class="status-badge status-<?php echo $status_class === 'warning' ? 'pending' : ($status_class === 'success' ? 'active' : 'inactive'); ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewMessage(<?php echo $message['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Lire le message">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" 
                                    onclick="replyMessage(<?php echo $message['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Répondre">
                                <i class="fas fa-reply"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="markAsRead(<?php echo $message['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Marquer comme lu">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteMessage(<?php echo $message['id']; ?>)"
                                    data-bs-toggle="tooltip" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Actions groupées -->
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="bulk-actions" style="display: none;">
            <span class="text-muted me-3">Actions sur les éléments sélectionnés :</span>
            <button class="btn btn-sm btn-outline-info me-2" onclick="bulkMarkAsRead()">
                <i class="fas fa-check"></i> Marquer comme lu
            </button>
            <button class="btn btn-sm btn-outline-success me-2" onclick="bulkMarkAsReplied()">
                <i class="fas fa-reply"></i> Marquer comme répondu
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">Précédent</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">Suivant</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Voir Message -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageDetails">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" onclick="replyFromModal()">
                    <i class="fas fa-reply"></i> Répondre
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Répondre -->
<div class="modal fade" id="replyMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Répondre au Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="ajax-form" action="actions/reply_message.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="message_id" id="replyMessageId">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <input type="email" class="form-control" name="to_email" id="replyToEmail" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" id="replySubject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="8" required 
                                  placeholder="Votre réponse..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Envoyer la réponse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.message-subject:hover {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 2px 4px;
}

.message-preview {
    display: block;
    margin-top: 2px;
}

.bulk-actions {
    background-color: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 4px;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.message-row.selected {
    background-color: rgba(13, 110, 253, 0.1) !important;
}
</style>

<script>
// Recherche en temps réel
document.getElementById('searchMessages').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.message-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filtrage par statut
function filterMessages() {
    const status = document.getElementById('statusFilter').value;
    const currentUrl = new URL(window.location);
    
    if (status) {
        currentUrl.searchParams.set('status', status);
    } else {
        currentUrl.searchParams.delete('status');
    }
    
    window.location.href = currentUrl.toString();
}

// Sélection multiple
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.message-checkbox');
    const bulkActions = document.querySelector('.bulk-actions');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        const row = checkbox.closest('.message-row');
        if (selectAll.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    });
    
    bulkActions.style.display = selectAll.checked ? 'block' : 'none';
}

// Gestion sélection individuelle
document.querySelectorAll('.message-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const row = this.closest('.message-row');
        const bulkActions = document.querySelector('.bulk-actions');
        const selectedCount = document.querySelectorAll('.message-checkbox:checked').length;
        
        if (this.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
        
        bulkActions.style.display = selectedCount > 0 ? 'block' : 'none';
        
        // Mettre à jour le checkbox "Tout sélectionner"
        const totalCheckboxes = document.querySelectorAll('.message-checkbox').length;
        const selectAll = document.getElementById('selectAll');
        selectAll.checked = selectedCount === totalCheckboxes;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < totalCheckboxes;
    });
});

// Fonctions pour les actions
function viewMessage(messageId) {
    fetch(`actions/get_message.php?id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('messageDetails').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
                modal.show();
                
                // Marquer comme lu automatiquement
                markAsRead(messageId, false);
            } else {
                showToast('Erreur lors du chargement du message', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
}

function replyMessage(messageId) {
    fetch(`actions/get_message.php?id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('replyMessageId').value = messageId;
                document.getElementById('replyToEmail').value = data.message.email;
                document.getElementById('replySubject').value = 'Re: ' + data.message.sujet;
                
                const modal = new bootstrap.Modal(document.getElementById('replyMessageModal'));
                modal.show();
            } else {
                showToast('Erreur lors du chargement du message', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
}

function markAsRead(messageId, showNotification = true) {
    fetch('actions/mark_message_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message_id: messageId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (showNotification) {
                showToast(data.message, 'success');
            }
            // Mettre à jour l'affichage
            const row = document.querySelector(`[data-message-id="${messageId}"]`);
            if (row) {
                row.classList.remove('table-warning');
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-inactive';
                statusBadge.textContent = 'Lu';
            }
        } else {
            if (showNotification) {
                showToast(data.message || 'Erreur lors de la mise à jour', 'danger');
            }
        }
    })
    .catch(error => {
        if (showNotification) {
            showToast('Erreur de connexion', 'danger');
        }
    });
}

function deleteMessage(messageId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        fetch('actions/delete_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message_id: messageId })
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

function markAllAsRead() {
    if (confirm('Marquer tous les messages comme lus ?')) {
        fetch('actions/mark_all_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la mise à jour', 'danger');
            }
        })
        .catch(error => {
            showToast('Erreur de connexion', 'danger');
        });
    }
}

// Actions groupées
function bulkMarkAsRead() {
    const selectedIds = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                           .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showToast('Aucun message sélectionné', 'warning');
        return;
    }
    
    fetch('actions/bulk_mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message_ids: selectedIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Erreur lors de la mise à jour', 'danger');
        }
    })
    .catch(error => {
        showToast('Erreur de connexion', 'danger');
    });
}

function bulkMarkAsReplied() {
    const selectedIds = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                           .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showToast('Aucun message sélectionné', 'warning');
        return;
    }
    
    fetch('actions/bulk_mark_replied.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message_ids: selectedIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Erreur lors de la mise à jour', 'danger');
        }
    })
    .catch(error => {
        showToast('Erreur de connexion', 'danger');
    });
}

function bulkDelete() {
    const selectedIds = Array.from(document.querySelectorAll('.message-checkbox:checked'))
                           .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showToast('Aucun message sélectionné', 'warning');
        return;
    }
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${selectedIds.length} message(s) ?`)) {
        fetch('actions/bulk_delete_messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message_ids: selectedIds })
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

function replyFromModal() {
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewMessageModal'));
    viewModal.hide();
    
    setTimeout(() => {
        const messageId = document.getElementById('messageDetails').dataset.messageId;
        if (messageId) {
            replyMessage(messageId);
        }
    }, 300);
}
</script>

<?php include 'includes/footer.php'; ?>
