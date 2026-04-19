<?php
require_once 'db.php';
try {
    $db->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT 'avataaars'");
    echo "Migration successful!\n";
} catch(PDOException $e) {
    echo "Migration ignored or failed: " . $e->getMessage() . "\n";
}
