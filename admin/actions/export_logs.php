<?php
session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupérer les filtres
    $action_filter = $_GET['action'] ?? '';
    $admin_filter = $_GET['admin'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    
    // Construction de la requête avec filtres
    $where_conditions = [];
    $params = [];
    
    if (!empty($action_filter)) {
        $where_conditions[] = "l.action LIKE ?";
        $params[] = "%{$action_filter}%";
    }
    
    if (!empty($admin_filter)) {
        $where_conditions[] = "a.nom LIKE ? OR a.prenom LIKE ?";
        $params[] = "%{$admin_filter}%";
        $params[] = "%{$admin_filter}%";
    }
    
    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(l.date_creation) = ?";
        $params[] = $date_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Récupérer tous les logs
    $logs_query = "SELECT l.*, a.nom as admin_nom, a.prenom as admin_prenom 
                   FROM admin_logs l 
                   LEFT JOIN admins a ON l.admin_id = a.id 
                   {$where_clause}
                   ORDER BY l.date_creation DESC";
    $stmt = $db->prepare($logs_query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Générer le nom du fichier
    $filename = 'logs_admin_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Headers pour le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Ouvrir le flux de sortie
    $output = fopen('php://output', 'w');
    
    // BOM pour UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes CSV
    fputcsv($output, [
        'ID',
        'Date/Heure',
        'Administrateur',
        'Action',
        'Détails'
    ], ';');
    
    // Données
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            date('d/m/Y H:i:s', strtotime($log['date_creation'])),
            ($log['admin_prenom'] ?? '') . ' ' . ($log['admin_nom'] ?? ''),
            ucfirst(str_replace('_', ' ', $log['action'])),
            $log['details']
        ], ';');
    }
    
    fclose($output);
    
    // Log de cette action
    $log_query = "INSERT INTO admin_logs (admin_id, action, details, date_creation) 
                  VALUES (?, 'export_logs', ?, NOW())";
    $stmt = $db->prepare($log_query);
    $stmt->execute([
        $_SESSION['admin_id'],
        "Export de " . count($logs) . " logs vers " . $filename
    ]);
    
} catch (Exception $e) {
    error_log("Erreur export_logs: " . $e->getMessage());
    header('Content-Type: text/html');
    echo "<script>alert('Erreur lors de l\'export des logs'); window.close();</script>";
}
?>
