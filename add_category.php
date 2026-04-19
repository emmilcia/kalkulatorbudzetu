<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'expense';
    
    if ($name !== '' && in_array($type, ['income', 'expense'])) {
        $stmt = $db->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        $stmt->execute([$name, $type]);
    }
}
header("Location: settings.php");
exit;
