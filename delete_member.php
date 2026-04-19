<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    if ($id > 0) {
        $db->beginTransaction();
        try {
            // Najpierw usuwamy wszystkie powiązane transakcje
            $stmt = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
            $stmt->execute([$id]);

            // Następnie usuwamy użytkownika
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            // Można by tu obsłużyć błąd np. zapisać do logów
        }
    }
}

header("Location: members.php");
exit;
