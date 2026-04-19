<?php
$file = 'budget.sqlite';

if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/x-sqlite3');
    header('Content-Disposition: attachment; filename="budzet_kopia.sqlite"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    echo "Brak bazy danych do pobrania.";
}
