<?php
require_once 'db.php';

// Pobieranie kategorii
$stmt = $db->query("
    SELECT c.*, COUNT(t.id) as tx_count 
    FROM categories c 
    LEFT JOIN transactions t ON c.id = t.category_id 
    GROUP BY c.id 
    ORDER BY c.type, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$expenses = array_filter($categories, fn($c) => $c['type'] === 'expense');
$incomes = array_filter($categories, fn($c) => $c['type'] === 'income');

// Statystyki bazy
$usersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$txCount = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$dbSize = file_exists('budget.sqlite') ? round(filesize('budget.sqlite') / 1024, 2) : 0;
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Bazy - KalkoBudżet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
        .cat-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .cat-item { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.6); padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0; }
        .badge-info { font-size: 0.75rem; background: #e2e8f0; padding: 2px 8px; border-radius: 12px; color: #475569; }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fca5a5;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Pasek boczny -->
        <nav class="sidebar glass-effect">
            <div class="logo">
                <div class="logo-icon">💸</div>
                <h1>KalkoBudżet</h1>
            </div>
            
            <ul class="nav-links">
                <li><a href="index.php"><span class="nav-icon">📊</span> Pulpit</a></li>
                <li><a href="members.php"><span class="nav-icon">👥</span> Członkowie rodziny</a></li>
                <li><a href="reports.php"><span class="nav-icon">📈</span> Raporty</a></li>
                <li class="active"><a href="settings.php"><span class="nav-icon">⚙️</span> Ustawienia bazy</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Wersja 1.0 (Lokalna)</p>
            </div>
        </nav>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2 class="greeting">Ustawienia systemowe ⚙️</h2>
                    <p class="subtitle">Zarządzaj słownikami, narzędziami oraz wykonuj kopie zapasowe.</p>
                </div>
            </header>

            <?php if ($error === 'cat_in_use'): ?>
            <div class="alert-error">
                <span><strong>Błąd:</strong> Nie można usunąć tej kategorii, ponieważ są do niej przypisane istniejące transakcje na pulpicie.</span>
                <a href="settings.php" style="color: #b91c1c; text-decoration: none; font-weight: bold;">✕</a>
            </div>
            <?php endif; ?>

            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="card glass-effect gradient-blue">
                    <div class="card-icon">🗄️</div>
                    <h3>Rozmiar Bazy Danych</h3>
                    <p class="amount" style="font-size: 1.5rem;"><?= $dbSize ?> KB</p>
                </div>
                <div class="card glass-effect gradient-red">
                    <div class="card-icon">📝</div>
                    <h3>Zapisane Zdarzenia</h3>
                    <p class="amount" style="font-size: 1.5rem;"><?= $txCount ?> wpisów</p>
                </div>
                <div class="card glass-effect gradient-green">
                    <div class="card-icon">👩‍👦</div>
                    <h3>Zarejestrowani Domownicy</h3>
                    <p class="amount" style="font-size: 1.5rem;"><?= $usersCount ?> osób</p>
                </div>
            </div>

            <div class="settings-grid">
                <!-- Lewa kolumna: Kategorie -->
                <section class="glass-effect section-box">
                    <div class="section-header">
                        <h3>Kategorie Wydatków</h3>
                    </div>
                    <div class="cat-list" style="margin-bottom: 2rem;">
                        <?php foreach($expenses as $c): ?>
                            <div class="cat-item">
                                <div>
                                    <span style="font-weight: 500;"><?= htmlspecialchars($c['name']) ?></span>
                                    <span class="badge-info">Użyto: <?= $c['tx_count'] ?>x</span>
                                </div>
                                <form action="delete_category.php" method="POST" style="margin: 0;" onsubmit="return confirm('Trwale usunąć tę kategorię?');">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn-delete" title="Usuń kategorię" style="width: 24px; height: 24px;">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="section-header">
                        <h3>Kategorie Wpływów (Dochód)</h3>
                    </div>
                    <div class="cat-list">
                        <?php foreach($incomes as $c): ?>
                            <div class="cat-item">
                                <div>
                                    <span style="font-weight: 500;"><?= htmlspecialchars($c['name']) ?></span>
                                    <span class="badge-info">Użyto: <?= $c['tx_count'] ?>x</span>
                                </div>
                                <form action="delete_category.php" method="POST" style="margin: 0;" onsubmit="return confirm('Trwale usunąć tę kategorię?');">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn-delete" title="Usuń kategorię" style="width: 24px; height: 24px;">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Prawa kolumna: Operacje i dodawanie -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <section class="glass-effect section-box">
                        <div class="section-header">
                            <h3>Dodaj Kategorię Wydatku 🔴</h3>
                        </div>
                        <form action="add_category.php" method="POST" class="add-form" style="display: flex; gap: 0.5rem; flex-direction: column;">
                            <input type="hidden" name="type" value="expense">
                            <input type="text" name="name" required placeholder="np. Zakupy, Czynsz, Raty" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <button type="submit" class="btn-primary full-width" style="margin-top: 0;">Dodaj wydatek</button>
                        </form>
                    </section>

                    <section class="glass-effect section-box">
                        <div class="section-header">
                            <h3>Dodaj Kategorię Wpływu 🟢</h3>
                        </div>
                        <form action="add_category.php" method="POST" class="add-form" style="display: flex; gap: 0.5rem; flex-direction: column;">
                            <input type="hidden" name="type" value="income">
                            <input type="text" name="name" required placeholder="np. Wypłata, Premia, 800+" style="padding: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <button type="submit" class="btn-primary full-width" style="margin-top: 0; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">Dodaj wpływ</button>
                        </form>
                    </section>

                    <section class="glass-effect section-box">
                        <div class="section-header">
                            <h3>Operacje na plikach</h3>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                            Możesz pobrać całą swoją bazę danych w formie pliku SQLite, aby zapisać ją na pendrive jako bezpieczną lokalną kopię zapasową.
                        </p>
                        <a href="export_db.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; padding: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">📥 Pobierz Kopię Zapasową (SQLite)</a>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
