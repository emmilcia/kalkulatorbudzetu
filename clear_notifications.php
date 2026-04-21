<?php
require_once 'db.php';

// Oznacz wszystkie powiadomienia jako przeczytane
try {
    $db->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
} catch (Exception $e) {
    // Ciche błędy
}

// Przekieruj z powrotem tam skąd przyszedł użytkownik lub na pulpit
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referer);
exit;
