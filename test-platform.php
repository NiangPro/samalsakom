<?php
/**
 * Script de test pour vérifier le bon fonctionnement de la plateforme SamalSakom
 */

// Configuration
$base_url = 'http://localhost/samalsakom';
$test_results = [];

// Fonction pour tester une URL
function testUrl($url, $description) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    $http_code = 200;
    
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $http_code = isset($matches[1]) ? (int)$matches[1] : 500;
    }
    
    return [
        'url' => $url,
        'description' => $description,
        'status' => $http_code,
        'success' => $http_code === 200,
        'content_length' => $result ? strlen($result) : 0
    ];
}

// Tests des pages principales
$tests = [
    // Site visiteur
    ['/', 'Page d\'accueil'],
    ['/about.php', 'Page À propos'],
    ['/services.php', 'Page Services'],
    ['/contact.php', 'Page Contact'],
    ['/login.php', 'Page Connexion'],
    ['/register.php', 'Page Inscription'],
    
    // Admin
    ['/admin-login.php', 'Connexion Admin'],
    ['/admin/', 'Dashboard Admin (nécessite auth)'],
    ['/admin/users.php', 'Gestion Utilisateurs (nécessite auth)'],
    ['/admin/tontines.php', 'Gestion Tontines (nécessite auth)'],
    ['/admin/messages.php', 'Gestion Messages (nécessite auth)'],
    
    // Assets
    ['/assets/css/style.css', 'CSS Principal'],
    ['/assets/js/main.js', 'JavaScript Principal'],
    ['/admin/assets/css/admin.css', 'CSS Admin'],
    ['/admin/assets/js/admin.js', 'JavaScript Admin'],
];

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test SamalSakom Platform</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        .test-success { color: #28a745; }
        .test-error { color: #dc3545; }
        .test-warning { color: #ffc107; }
        .status-badge { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; }
        .status-200 { background: #d4edda; color: #155724; }
        .status-404 { background: #f8d7da; color: #721c24; }
        .status-500 { background: #f5c6cb; color: #721c24; }
        .status-other { background: #fff3cd; color: #856404; }
    </style>
</head>
<body class='bg-light'>
    <div class='container py-5'>
        <div class='row'>
            <div class='col-12'>
                <div class='card'>
                    <div class='card-header bg-primary text-white'>
                        <h1 class='h3 mb-0'>
                            <i class='fas fa-vial me-2'></i>
                            Test de la Plateforme SamalSakom
                        </h1>
                        <p class='mb-0'>Vérification du bon fonctionnement de tous les composants</p>
                    </div>
                    <div class='card-body'>";

// Exécution des tests
$total_tests = count($tests);
$passed_tests = 0;
$failed_tests = 0;

echo "<div class='table-responsive'>
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Taille</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>";

foreach ($tests as $test) {
    $url = $base_url . $test[0];
    $result = testUrl($url, $test[1]);
    
    $status_class = 'status-other';
    $icon = 'fas fa-question';
    $result_text = 'Inconnu';
    
    if ($result['status'] === 200) {
        $status_class = 'status-200';
        $icon = 'fas fa-check-circle test-success';
        $result_text = 'OK';
        $passed_tests++;
    } elseif ($result['status'] === 404) {
        $status_class = 'status-404';
        $icon = 'fas fa-times-circle test-error';
        $result_text = 'Non trouvé';
        $failed_tests++;
    } elseif ($result['status'] >= 500) {
        $status_class = 'status-500';
        $icon = 'fas fa-exclamation-triangle test-error';
        $result_text = 'Erreur serveur';
        $failed_tests++;
    } else {
        $icon = 'fas fa-exclamation-triangle test-warning';
        $result_text = 'Attention';
        $failed_tests++;
    }
    
    echo "<tr>
            <td><code>" . htmlspecialchars($test[0]) . "</code></td>
            <td>" . htmlspecialchars($result['description']) . "</td>
            <td><span class='status-badge $status_class'>" . $result['status'] . "</span></td>
            <td>" . number_format($result['content_length']) . " bytes</td>
            <td><i class='$icon me-1'></i>$result_text</td>
          </tr>";
}

echo "</tbody></table></div>";

// Résumé des tests
$success_rate = round(($passed_tests / $total_tests) * 100, 1);
$summary_class = $success_rate >= 80 ? 'success' : ($success_rate >= 60 ? 'warning' : 'danger');

echo "<div class='alert alert-$summary_class mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-chart-pie me-2'></i>Résumé des Tests
        </h4>
        <div class='row'>
            <div class='col-md-3'>
                <strong>Total:</strong> $total_tests tests
            </div>
            <div class='col-md-3'>
                <strong class='test-success'>Réussis:</strong> $passed_tests
            </div>
            <div class='col-md-3'>
                <strong class='test-error'>Échoués:</strong> $failed_tests
            </div>
            <div class='col-md-3'>
                <strong>Taux de réussite:</strong> $success_rate%
            </div>
        </div>
      </div>";

// Test de la base de données
echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5 class='mb-0'><i class='fas fa-database me-2'></i>Test de la Base de Données</h5>
        </div>
        <div class='card-body'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Test des tables principales
    $tables = ['users', 'tontines', 'participations', 'cotisations', 'contacts', 'admins'];
    $db_tests = [];
    
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            $db_tests[] = [
                'table' => $table,
                'status' => 'success',
                'count' => $count,
                'message' => "OK - $count enregistrement(s)"
            ];
        } catch (Exception $e) {
            $db_tests[] = [
                'table' => $table,
                'status' => 'error',
                'count' => 0,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    echo "<div class='table-responsive'>
            <table class='table table-sm'>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Enregistrements</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($db_tests as $test) {
        $icon = $test['status'] === 'success' ? 'fas fa-check-circle test-success' : 'fas fa-times-circle test-error';
        echo "<tr>
                <td><code>{$test['table']}</code></td>
                <td>{$test['count']}</td>
                <td><i class='$icon me-1'></i>{$test['message']}</td>
              </tr>";
    }
    
    echo "</tbody></table></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Erreur de connexion à la base de données: " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "</div></div>";

// Instructions de test
echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5 class='mb-0'><i class='fas fa-list-check me-2'></i>Instructions de Test Manuel</h5>
        </div>
        <div class='card-body'>
            <div class='row'>
                <div class='col-md-6'>
                    <h6 class='text-primary'>Site Visiteur</h6>
                    <ul class='list-unstyled'>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Naviguer sur toutes les pages</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Tester le formulaire de contact</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Créer un compte utilisateur</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Se connecter/déconnecter</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Vérifier la responsivité mobile</li>
                    </ul>
                </div>
                <div class='col-md-6'>
                    <h6 class='text-success'>Dashboard Admin</h6>
                    <ul class='list-unstyled'>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Se connecter: <code>admin@samalsakom.sn</code> / <code>admin123</code></li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Consulter les statistiques</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Gérer les utilisateurs</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Gérer les tontines</li>
                        <li><i class='fas fa-arrow-right me-2 text-muted'></i>Traiter les messages</li>
                    </ul>
                </div>
            </div>
        </div>
      </div>";

echo "<div class='text-center mt-4'>
        <a href='$base_url' class='btn btn-primary me-2'>
            <i class='fas fa-home me-1'></i>Accueil Site
        </a>
        <a href='$base_url/admin-login.php' class='btn btn-success'>
            <i class='fas fa-shield-alt me-1'></i>Admin Dashboard
        </a>
      </div>";

echo "    </div>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>
