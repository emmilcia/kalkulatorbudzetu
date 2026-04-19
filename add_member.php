<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    // Podstawowa sanitacja koloru HEX
    $color = $_POST['color'] ?? '#3b82f6';
    if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        $color = '#3b82f6';
    }
    $avatar = $_POST['avatar'] ?? 'avataaars';
    $valid_avatars = ['avataaars', 'bottts', 'fun-emoji', 'adventurer', 'micah'];
    if (!in_array($avatar, $valid_avatars)) {
        $avatar = 'avataaars';
    }
    
    if (trim($name) !== '') {
        $stmt = $db->prepare("INSERT INTO users (name, color, avatar) VALUES (?, ?, ?)");
        $stmt->execute([trim($name), $color, $avatar]);
    }
}

header("Location: members.php");
exit;
