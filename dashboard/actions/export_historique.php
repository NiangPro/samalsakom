<?php
// Export de l'historique des transactions de l'utilisateur connecté (CSV)

// Session et sécurité
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Non autorisé';
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $user_id = (int)$_SESSION['user_id'];

    // Récupération des filtres (mêmes clés que la page historique)
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $date_filter = $_GET['date'] ?? '';

    $where_conditions = ["c.user_id = ?"];
    $params = [$user_id];

    if (!empty($type_filter)) {
        $where_conditions[] = "c.type_transaction = ?";
        $params[] = $type_filter;
    }

    if (!empty($status_filter)) {
        $where_conditions[] = "c.statut = ?";
        $params[] = $status_filter;
    }

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case '7days':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '3months':
                $where_conditions[] = "c.date_creation >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
        }
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Requête sans pagination pour exporter toutes les lignes filtrées
    $query = "SELECT c.*, t.nom AS tontine_nom
              FROM cotisations c
              LEFT JOIN tontines t ON t.id = c.tontine_id
              WHERE $where_clause
              ORDER BY c.date_creation DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Préparer le CSV
    $filename = 'historique_transactions_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // En-têtes
    fputcsv($output, [
        'Date',
        'Heure',
        'Tontine',
        'Type',
        'Montant (FCFA)',
        'Mode de paiement',
        'Statut',
        'Référence'
    ], ';');

    // Mapping statuts et modes pour lisibilité
    $statusLabels = [
        'pending' => 'En attente',
        'completed' => 'Complété',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé'
    ];

    // Lignes
    foreach ($rows as $r) {
        $date = $r['date_creation'] ? date('d/m/Y', strtotime($r['date_creation'])) : '';
        $time = $r['date_creation'] ? date('H:i', strtotime($r['date_creation'])) : '';
        $tontine = $r['tontine_nom'] ?? '';
        $type = ucfirst($r['type_transaction']);
        $montant = number_format((float)$r['montant'], 0, ',', ' ');
        $mode = $r['mode_paiement'] ? ucfirst(str_replace('_', ' ', $r['mode_paiement'])) : '';
        $statut = $statusLabels[$r['statut']] ?? $r['statut'];
        $ref = $r['reference_paiement'] ?? '';

        fputcsv($output, [
            $date,
            $time,
            $tontine,
            $type,
            $montant,
            $mode,
            $statut,
            $ref
        ], ';');
    }

    fclose($output);
    exit;
} catch (Throwable $e) {
    // En cas d'erreur, renvoyer un CSV minimal avec le message
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="historique_error.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Erreur'], ';');
    fputcsv($out, [isset($e) ? $e->getMessage() : 'Erreur inconnue'], ';');
    fclose($out);
    exit;
}
?>


