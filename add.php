<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'expense';
    $amount = $_POST['amount'] ?? 0;
    $amount = str_replace(',', '.', $amount); // Zabezpieczenie przed polskim przecinkiem
    $amount = (float)$amount;
    
    $user_id = $_POST['user_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';

    // Podstawowa walidacja i bezpieczny insert za pomocą prepared statements
    if ($amount > 0 && $user_id && $category_id && in_array($type, ['income', 'expense'])) {
        $stmt = $db->prepare("INSERT INTO transactions (user_id, category_id, amount, date, description, type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $category_id, $amount, $date, $description, $type]);
    }

    // Powrót do index.php po zapisaniu
    header('Location: index.php');
    exit;
}
?>
