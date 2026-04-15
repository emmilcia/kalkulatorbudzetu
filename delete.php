<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$id]);
    }

    // Powrót do index.php po usunięciu
    header('Location: index.php');
    exit;
}
?>
