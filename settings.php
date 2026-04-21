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

// Pobierz nieprzeczytane powiadomienia
$unreadNotifs = $db->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($unreadNotifs);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia - Skarbonka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
        .cat-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .cat-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: white; 
            padding: 1rem 1.25rem; 
            border-radius: var(--br-md); 
            border: 1px solid var(--border); 
            transition: var(--transition);
        }
        .cat-item:hover { transform: translateX(5px); border-color: var(--accent); }
        .badge-info { font-size: 0.8rem; font-weight: 700; background: #f1f5f9; padding: 4px 10px; border-radius: 10px; color: var(--text-muted); }
        
        .alert-error {
            background-color: var(--error);
            color: white;
            padding: 1.25rem;
            border-radius: var(--br-md);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Pasek górny -->
        <header class="app-header">
            <div class="logo">
                <div class="logo-icon">🐷</div>
                <h1>Skarbonka</h1>
            </div>

            <nav class="top-nav">
                <a href="index.php">Pulpit</a>
                <a href="members.php">Rodzina</a>
                <a href="history.php">Historia</a>
                <a href="reports.php">Raporty</a>
                <a href="settings.php" class="active">Ustawienia</a>
                <!-- Centrum Powiadomień -->
                <div class="notif-center">
                    <div class="notif-bell" onclick="toggleNotifs()">
                        🔔
                        <?php if ($unreadCount > 0): ?>
                            <div class="notif-badge"><?= $unreadCount ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="notif-panel" id="notifPanel">
                        <div class="notif-header">
                            <h4>Powiadomienia</h4>
                            <?php if ($unreadCount > 0): ?>
                                <a href="clear_notifications.php" style="font-size: 0.7rem; color: var(--accent); text-decoration: none;">Oznacz jako przeczytane</a>
                            <?php endif; ?>
                        </div>
                        <div class="notif-list">
                            <?php if ($unreadCount === 0): ?>
                                <p style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 1.5rem;">Brak nowych powiadomień</p>
                            <?php else: ?>
                                <?php foreach($unreadNotifs as $n): ?>
                                    <div class="notif-item <?= htmlspecialchars($n['type']) ?>">
                                        <?= htmlspecialchars($n['message']) ?>
                                        <small><?= date('H:i', strtotime($n['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2 class="greeting">Ustawienia systemowe ⚙️</h2>
                    <p class="subtitle">Zarządzaj słownikami, narzędziami oraz wykonuj kopie zapasowe.</p>
                </div>
            </header>

            <?php if ($error === 'cat_in_use'): ?>
            <div class="alert-error">
                <span><strong>Błąd:</strong> Nie można usunąć tej kategorii, ponieważ są do niej przypisane istniejące transakcje.</span>
                <a href="settings.php" style="color: white; text-decoration: none; font-size: 1.5rem; line-height: 1;">✕</a>
            </div>
            <?php endif; ?>

            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="card glass-effect gradient-blue">
                    <div class="card-icon">🗄️</div>
                    <h3>Rozmiar Bazy</h3>
                    <p class="amount"><?= $dbSize ?> KB</p>
                </div>
                <div class="card glass-effect gradient-red">
                    <div class="card-icon">📝</div>
                    <h3>Liczba Wpisów</h3>
                    <p class="amount"><?= $txCount ?></p>
                </div>
                <div class="card glass-effect gradient-green">
                    <div class="card-icon">👩‍👦</div>
                    <h3>Domownicy</h3>
                    <p class="amount"><?= $usersCount ?></p>
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
    <script>
        function toggleNotifs() {
            const panel = document.getElementById('notifPanel');
            panel.style.display = (panel.style.display === 'flex') ? 'none' : 'flex';
        }
        window.onclick = function(event) {
            if (!event.target.closest('.notif-center')) {
                const panel = document.getElementById('notifPanel');
                if (panel) panel.style.display = 'none';
            }
        }
    </script>
</body>
</html>
