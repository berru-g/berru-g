<?php
// export_transactions.php
require_once 'config.php';
require_once 'auth.php';

// Vérifier admin
if (!Auth::isLoggedIn() || $_SESSION['user_id'] != 1) {
    header('Location: index.php');
    exit;
}

// Récupérer les mêmes filtres
$searchUser = $_GET['user'] ?? '';
$searchStatus = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Construire la requête (identique au dashboard)
$db = getDB();
$whereConditions = [];
$params = [];

// [Même logique de filtres que dans le dashboard...]

$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

$sql = "SELECT 
            pt.id,
            u.username,
            u.email,
            pt.points_amount,
            pt.amount_eur,
            pt.status,
            pt.payment_intent_id,
            pt.created_at
        FROM point_transactions pt
        LEFT JOIN users u ON pt.user_id = u.id
        $whereClause
        ORDER BY pt.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Générer le CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transactions_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'ID', 'Utilisateur', 'Email', 'Points', 'Montant (€)', 
    'Statut', 'Payment ID', 'Date'
], ';');

foreach ($transactions as $transaction) {
    fputcsv($output, [
        $transaction['id'],
        $transaction['username'],
        $transaction['email'],
        $transaction['points_amount'],
        $transaction['amount_eur'],
        $transaction['status'],
        $transaction['payment_intent_id'],
        $transaction['created_at']
    ], ';');
}

fclose($output);
exit;