<?php
/*
  Konfiguracja bazy danych (SQLite).
  Plik automatycznie tworzy tabelki, jeśli ich nie ma.
  SQLite jest fajny, bo wszystko siedzi w jednym pliku 'budget.sqlite'.
*/

$db_file = __DIR__ . '/budget.sqlite';
$needs_init = !file_exists($db_file);

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Jeśli to pierwsze uruchomienie, stawiamy strukturę od zera
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
            CREATE TABLE IF NOT EXISTS goals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                target_amount REAL NOT NULL,
                current_amount REAL DEFAULT 0,
                color TEXT DEFAULT '#d7b5a1',
                icon TEXT DEFAULT '🎯'
            );
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            /* Przykładowe dane na start */
            INSERT INTO users (name, color) VALUES ('Mama', '#ec4899'), ('Tata', '#3b82f6'), ('Młodzież', '#8b5cf6');
            INSERT INTO categories (name, type) VALUES ('Wynagrodzenie', 'income'), ('Kieszonkowe', 'income'), ('Prezenty', 'income');
            INSERT INTO categories (name, type) VALUES ('Mieszkanie', 'expense'), ('Jedzenie', 'expense'), ('Paliwo i Transport', 'expense'), ('Rozrywka i Hobby', 'expense'), ('Ubrania', 'expense'), ('Edukacja', 'expense'), ('Zdrowie', 'expense');
        ");
    }

    /* 
       MIGRACJE - Tutaj dorzucam kolumny i tabelki, których zapomniałam na samym początku projektu.
       Daję to w try-catch, żeby nie wywalało błędu, jeśli kolumna już istnieje.
    */
    try { $db->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT 'cat1'"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE users ADD COLUMN monthly_limit REAL DEFAULT 0"); } catch (Exception $e) {}
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            target_amount REAL NOT NULL,
            current_amount REAL DEFAULT 0,
            color TEXT DEFAULT '#d7b5a1',
            icon TEXT DEFAULT '🎯'
        )");
    } catch (Exception $e) {}
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {}

} catch (Exception $e) {
    // Jak się nie uda połączyć, to nie ma co dalej robić
    die("Ups! Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
