<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $color = $_POST['color'] ?? '#3b82f6';
    
    if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        $color = '#3b82f6';
    }
    
    $avatar = $_POST['avatar'] ?? 'cat1';
    $valid_avatars = ['cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'cat6'];
    if (!in_array($avatar, $valid_avatars)) {
        $avatar = 'cat1';
    }
    
    if (trim($name) !== '' && $id > 0) {
        $stmt = $db->prepare("UPDATE users SET name = ?, color = ?, avatar = ? WHERE id = ?");
        $stmt->execute([trim($name), $color, $avatar, $id]);
    }
}

header("Location: members.php");
exit;
