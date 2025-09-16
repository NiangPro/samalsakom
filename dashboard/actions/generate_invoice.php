<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Non autorisé');
}

// Vérifier si l'ID de la transaction est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID de transaction manquant');
}

$transaction_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupérer les détails de la transaction
    $query = "SELECT c.*, t.nom as tontine_nom, u.nom, u.prenom, u.email, u.telephone
              FROM cotisations c 
              LEFT JOIN tontines t ON c.tontine_id = t.id 
              JOIN users u ON c.user_id = u.id
              WHERE c.id = ? AND c.user_id = ? AND c.statut = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        header('HTTP/1.1 404 Not Found');
        exit('Transaction non trouvée');
    }
    
    // Générer le contenu HTML de la facture
    $invoice_html = generateInvoiceHTML($transaction);
    
    // Définir les en-têtes pour le téléchargement PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="facture_' . $transaction['reference_paiement'] . '.html"');
    
    echo $invoice_html;
    
} catch (Exception $e) {
    error_log("Erreur génération facture: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur lors de la génération de la facture');
}

function generateInvoiceHTML($transaction) {
    $invoice_number = 'INV-' . date('Y') . '-' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT);
    $date_facture = date('d/m/Y');
    $date_paiement = date('d/m/Y H:i', strtotime($transaction['date_paiement']));
    
    // Déterminer le type de transaction
    $transaction_type = '';
    $transaction_description = '';
    
    switch($transaction['type_transaction']) {
        case 'cotisation':
            $transaction_type = 'Cotisation Tontine';
            $transaction_description = 'Cotisation pour la tontine "' . htmlspecialchars($transaction['tontine_nom']) . '"';
            break;
        case 'recharge':
            $transaction_type = 'Recharge Portefeuille';
            $transaction_description = 'Recharge du portefeuille SamalSakom';
            break;
        case 'retrait':
            $transaction_type = 'Retrait';
            $transaction_description = 'Retrait de fonds du portefeuille';
            break;
        default:
            $transaction_type = ucfirst($transaction['type_transaction']);
            $transaction_description = $transaction['motif'] ?? 'Transaction SamalSakom';
    }
    
    $mode_paiement = $transaction['methode_paiement'] ?? 'N/A';
    $mode_display = ucfirst(str_replace('_', ' ', $mode_paiement));
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - ' . $invoice_number . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #2E8B57 0%, #3CB371 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .invoice-details {
            padding: 30px;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .invoice-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2E8B57;
        }
        
        .invoice-date {
            color: #666;
            font-size: 1.1rem;
        }
        
        .customer-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .customer-info h3 {
            color: #2E8B57;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .customer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .transaction-details {
            margin-bottom: 30px;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .transaction-table th,
        .transaction-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .transaction-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2E8B57;
        }
        
        .amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2E8B57;
        }
        
        .payment-info {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2E8B57;
        }
        
        .payment-info h3 {
            color: #2E8B57;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
        }
        
        .print-button {
            background: #2E8B57;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            margin: 20px 0;
        }
        
        .print-button:hover {
            background: #236B47;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-button {
                display: none;
            }
        }
        
        @media (max-width: 600px) {
            .invoice-meta {
                flex-direction: column;
                gap: 15px;
            }
            
            .customer-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="logo">SamalSakom</div>
            <div class="company-info">
                Plateforme de Gestion de Tontines et d\'Épargne<br>
                Email: contact@samalsakom.sn | Tél: +221 33 123 45 67
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="invoice-meta">
                <div>
                    <div class="invoice-number">Facture N° ' . $invoice_number . '</div>
                    <div class="invoice-date">Date: ' . $date_facture . '</div>
                </div>
                <div>
                    <span class="status-badge">✓ PAYÉ</span>
                </div>
            </div>
            
            <div class="customer-info">
                <h3>Informations Client</h3>
                <div class="customer-details">
                    <div class="detail-item">
                        <span class="detail-label">Nom complet:</span>
                        <span class="detail-value">' . htmlspecialchars($transaction['prenom'] . ' ' . $transaction['nom']) . '</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">' . htmlspecialchars($transaction['email']) . '</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Téléphone:</span>
                        <span class="detail-value">' . htmlspecialchars($transaction['telephone']) . '</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date de paiement:</span>
                        <span class="detail-value">' . $date_paiement . '</span>
                    </div>
                </div>
            </div>
            
            <div class="transaction-details">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $transaction_description . '</td>
                            <td>' . $transaction_type . '</td>
                            <td class="amount">' . number_format($transaction['montant'], 0, ',', ' ') . ' FCFA</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="payment-info">
                <h3>Informations de Paiement</h3>
                <div class="detail-item">
                    <span class="detail-label">Référence de paiement:</span>
                    <span class="detail-value">' . htmlspecialchars($transaction['reference_paiement']) . '</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Mode de paiement:</span>
                    <span class="detail-value">' . $mode_display . '</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: bold;">Paiement confirmé</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <button class="print-button" onclick="window.print()">🖨️ Imprimer cette facture</button>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>SamalSakom</strong> - Votre partenaire de confiance pour l\'épargne collective</p>
            <p>Cette facture a été générée automatiquement le ' . date('d/m/Y à H:i') . '</p>
            <p style="font-size: 0.9rem; margin-top: 10px;">
                En cas de questions, contactez notre service client: support@samalsakom.sn
            </p>
        </div>
    </div>
</body>
</html>';
}
?>
