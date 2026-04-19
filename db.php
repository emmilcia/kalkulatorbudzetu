<?php
$db_file = __DIR__ . '/budget.sqlite';
$needs_init = !file_exists($db_file);

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($needs_init) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                color TEXT DEFAULT '#ffffff',
                avatar TEXT DEFAULT 'cat1',
                monthly_limit REAL DEFAULT 0
            );
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('income', 'expense'))
            );
            CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                date TEXT NOT NULL,
                description TEXT,
                type TEXT NOT NULL CHECK(type IN ('income', 'expense')),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );
            
            INSERT INTO users (name, color) VALUES ('Mama', '#ec4899'), ('Tata', '#3b82f6'), ('Młodzież', '#8b5cf6');
            INSERT INTO categories (name, type) VALUES ('Wynagrodzenie', 'income'), ('Kieszonkowe', 'income'), ('Prezenty', 'income');
            INSERT INTO categories (name, type) VALUES ('Mieszkanie', 'expense'), ('Jedzenie', 'expense'), ('Paliwo i Transport', 'expense'), ('Rozrywka i Hobby', 'expense'), ('Ubrania', 'expense'), ('Edukacja', 'expense'), ('Zdrowie', 'expense');
        ");
    }
} catch (Exception $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
