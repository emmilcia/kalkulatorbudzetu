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

        // LOGIKA POWIADOMIEŃ O LIMITACH
        if ($type === 'expense') {
            $stmt = $db->prepare("SELECT name, monthly_limit FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $userObj = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userObj && $userObj['monthly_limit'] > 0) {
                $limit = (float)$userObj['monthly_limit'];
                
                // Pobierz sumę wydatków z tego miesiąca
                $stmt = $db->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense' AND date LIKE ?");
                $stmt->execute([$user_id, date('Y-m') . '%']);
                $spent = (float)$stmt->fetchColumn();

                if ($spent >= $limit) {
                    $msg = "⚠️ ALARM: " . $userObj['name'] . " przekroczył limit! (" . number_format($spent, 2, ',', ' ') . " zł)";
                    $stmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('alarm', ?)");
                    $stmt->execute([$msg]);
                } elseif ($spent >= $limit * 0.9) {
                    $msg = "⚡ UWAGA: " . $userObj['name'] . " wykorzystał 90% limitu!";
                    $stmt = $db->prepare("INSERT INTO notifications (type, message) VALUES ('warning', ?)");
                    $stmt->execute([$msg]);
                }
            }
        }
    }

    // Powrót do index.php po zapisaniu
    header('Location: index.php');
    exit;
}
?>
