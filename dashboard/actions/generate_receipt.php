<?php
// Génère un reçu simple (HTML -> impression/PDF via le navigateur)
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Non autorisé';
    exit;
}

require_once '../../config/database.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Transaction invalide');
    }

    $transaction_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Récupérer la transaction (doit appartenir à l'utilisateur et être complétée)
    $sql = "SELECT c.*, t.nom AS tontine_nom
            FROM cotisations c
            LEFT JOIN tontines t ON t.id = c.tontine_id
            WHERE c.id = ? AND c.user_id = ? AND c.statut = 'completed'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$transaction_id, $user_id]);
    $tx = $stmt->fetch();

    if (!$tx) {
        throw new Exception('Reçu indisponible pour cette transaction');
    }

    // Générer un HTML imprimable
    $title = 'Reçu de Paiement - SamalSakom';
    $date = $tx['date_paiement'] ? date('d/m/Y H:i', strtotime($tx['date_paiement'])) : date('d/m/Y H:i');
    $montant = number_format((float)$tx['montant'], 0, ',', ' ') . ' FCFA';
    $ref = htmlspecialchars($tx['reference_paiement'] ?? '-');
    $tontine = htmlspecialchars($tx['tontine_nom'] ?? '');
    $mode = '-'; // Mode de paiement non disponible

    echo "<!DOCTYPE html><html lang=\"fr\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>{$title}</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <style>
        body { padding: 20px; }
        .receipt { max-width: 700px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .brand { font-weight: 800; font-size: 20px; color: #2E8B57; }
        .meta div { display: flex; justify-content: space-between; }
        .meta div span:first-child { color: #666; }
        .amount { font-size: 28px; font-weight: 800; color: #2E8B57; }
        @media print { .no-print { display: none; } }
    </style></head><body>
    <div class=\"receipt\">
        <div class=\"header\">
            <div class=\"brand\">SamalSakom</div>
            <div class=\"text-muted\">{$title}</div>
        </div>
        <hr/>
        <div class=\"mb-3\">
            <div class=\"amount\">{$montant}</div>
            <div class=\"text-muted\">Paiement confirmé</div>
        </div>
        <div class=\"meta\">
            <div><span>Référence</span><span>{$ref}</span></div>
            <div><span>Date paiement</span><span>{$date}</span></div>
            <div><span>Tontine</span><span>{$tontine}</span></div>
            <div><span>Mode paiement</span><span>{$mode}</span></div>
        </div>
        <hr/>
        <div class=\"text-muted\" style=\"font-size:12px\">Conservez ce reçu pour vos dossiers. Merci pour votre confiance.</div>
        <div class=\"mt-3 no-print\">
            <button class=\"btn btn-primary\" onclick=\"window.print()\"><i class=\"fas fa-print me-2\"></i>Imprimer</button>
        </div>
    </div>
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js\"></script>
    </body></html>";
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo 'Erreur: ' . htmlspecialchars($e->getMessage());
}
?>


