<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    // Podstawowa sanitacja koloru HEX
    $color = $_POST['color'] ?? '#3b82f6';
    if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        $color = '#3b82f6';
    }
    
    $avatar = $_POST['avatar'] ?? 'cat1';
    $valid_avatars = ['cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6'];
    if (!in_array($avatar, $valid_avatars)) {
        $avatar = 'cat1';
    }
    $limit = (float)($_POST['monthly_limit'] ?? 0);
    
    if (trim($name) !== '') {
        $stmt = $db->prepare("INSERT INTO users (name, color, avatar, monthly_limit) VALUES (?, ?, ?, ?)");
        $stmt->execute([trim($name), $color, $avatar, $limit]);
    }
}

header("Location: members.php");
exit;
