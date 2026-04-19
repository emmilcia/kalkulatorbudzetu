<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    if ($id > 0) {
        // Sprawdź czy kategoria jest w użyciu
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            // Nie pozwalamy na usunięcie jeżeli kategoria była przypisana.
            header("Location: settings.php?error=cat_in_use");
            exit;
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}
header("Location: settings.php");
exit;
