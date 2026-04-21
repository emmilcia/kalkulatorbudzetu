<?php
require_once 'db.php';

// Pobierz uzytkowników z podliczeniem wydatków w tym miesiącu
$currentMonth = date('Y-m');
$stmt = $db->prepare("
    SELECT u.*, 
    (SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'expense' AND date LIKE ?) as spent
    FROM users u
");
$stmt->execute([$currentMonth . '%']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz kategorie ułożone w grupy wg typu
$stmt = $db->query("SELECT * FROM categories ORDER BY type, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz podsumowanie
$stmt = $db->query("SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance FROM transactions");
$balanceRaw = $stmt->fetch(PDO::FETCH_ASSOC)['balance'];
$balance = $balanceRaw ? number_format($balanceRaw, 2, ',', ' ') : "0,00";

// Podsumowanie na obecny miesiąc
$currentMonth = date('Y-m');
$stmt = $db->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE date LIKE ? GROUP BY type");
$stmt->execute([$currentMonth . '%']);
$monthlyStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$income = isset($monthlyStats['income']) ? number_format($monthlyStats['income'], 2, ',', ' ') : "0,00";
$expense = isset($monthlyStats['expense']) ? number_format($monthlyStats['expense'], 2, ',', ' ') : "0,00";

// Pobierz ostatnie 10 transakcji
$stmt = $db->query("
    SELECT t.*, u.name as user_name, u.color, c.name as category_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    ORDER BY t.date DESC, t.id DESC LIMIT 15
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz nieprzeczytane powiadomienia
$unreadNotifs = $db->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($unreadNotifs);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skarbonka</title>
    <!-- Nowoczesna czcionka -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
                <a href="index.php" class="active">Pulpit</a>
                <a href="members.php">Rodzina</a>
                <a href="history.php">Historia</a>
                <a href="reports.php">Raporty</a>
                <a href="settings.php">Ustawienia</a>
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

        <!-- Główna zawartość -->
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2 class="greeting">Cześć! 👋</h2>
                    <p class="subtitle">Oto jak prezentują się rodzinne finanse w tym miesiącu.</p>
                </div>
            </header>

            <!-- Karty podsumowania -->
            <div class="dashboard-cards">
                <div class="card glass-effect gradient-blue">
                    <div class="card-icon">💎</div>
                    <h3>Zostaje na koncie</h3>
                    <p class="amount"><?= $balance ?> PLN</p>
                </div>
                <div class="card glass-effect gradient-green">
                    <div class="card-icon">📈</div>
                    <h3>Wpływy w miesiącu</h3>
                    <p class="amount">+<?= $income ?> PLN</p>
                </div>
                <div class="card glass-effect gradient-red">
                    <div class="card-icon">🛒</div>
                    <h3>Wydatki w miesiącu</h3>
                    <p class="amount">-<?= $expense ?> PLN</p>
                </div>
            </div>

            <!-- Siatka na listę i rodzinę -->
            <div class="content-grid">
                <section class="recent-transactions glass-effect section-box">
                    <div class="section-header">
                        <h3>Ostatnie Pieniądze</h3>
                        <button class="btn-primary" onclick="openModal()">+ Nowy wpis</button>
                    </div>
                    
                    <div class="transaction-list">
                        <?php if (empty($transactions)): ?>
                            <div class="empty-state">
                                <span>🌬️</span>
                                <p>Cisza w portfelu. Dodaj pierwszą transakcję!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($transactions as $t): ?>
                                <div class="transaction-item">
                                    <div class="t-icon" style="background-color: <?= htmlspecialchars($t['color']) ?>22; color: <?= htmlspecialchars($t['color']) ?>">
                                        <?= $t['type'] == 'income' ? '↑' : '↓' ?>
                                    </div>
                                    <div class="t-details">
                                        <h4><?= htmlspecialchars($t['category_name']) ?> <span class="t-user">(<?= htmlspecialchars($t['user_name']) ?>)</span></h4>
                                        <p><?= htmlspecialchars($t['date']) ?><?= $t['description'] ? ' &bull; '.htmlspecialchars($t['description']) : '' ?></p>
                                    </div>
                                    <div class="t-amount <?= $t['type'] ?>">
                                        <?= $t['type'] == 'income' ? '+' : '-' ?><?= number_format($t['amount'], 2, ',', ' ') ?> PLN
                                    </div>
                                    <form action="delete.php" method="POST" class="delete-form">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn-delete" title="Usuń wpis">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="family-members glass-effect section-box">
                    <div class="section-header">
                        <h3>Kto wydaje?</h3>
                    </div>
                    <div class="users-list">
                        <?php 
                        $daysInMonth = (int)date('t');
                        $currentDay = (int)date('j');
                        $remainingDays = $daysInMonth - $currentDay + 1;
                        ?>
                        <?php foreach($users as $u): ?>
                            <?php 
                                $userSpent = (float)$u['spent'];
                                $userLimit = (float)$u['monthly_limit'];
                                $showLimit = $userLimit > 0;
                                $percent = $showLimit ? round(($userSpent / $userLimit) * 100) : 0;
                                $clampedPercent = min(100, $percent);
                                
                                // Zaawansowana logika kolorów
                                $statusColor = htmlspecialchars($u['color']);
                                $statusText = "Aktywny";
                                if ($showLimit) {
                                    if ($percent > 100) {
                                        $statusColor = "var(--red-color)";
                                        $statusText = "Przekroczono!";
                                    } elseif ($percent > 85) {
                                        $statusColor = "#f59e0b"; // Orange
                                        $statusText = "Uwaga!";
                                    } elseif ($percent > 65) {
                                        $statusColor = "#fbbf24"; // Yellow
                                        $statusText = "Półmetek";
                                    }
                                }

                                $remaining = $userLimit - $userSpent;
                                $daily = $remaining > 0 ? $remaining / $remainingDays : 0;
                            ?>
                            <div class="user-card" style="flex-direction: column; align-items: flex-start; gap: 0.8rem; border-left: 5px solid <?= $statusColor ?>;">
                                <div style="display: flex; align-items: center; gap: 1rem; width: 100%;">
                                     <div class="user-avatar" style="border-color: <?= htmlspecialchars($u['color']) ?>; box-shadow: 0 0 15px <?= htmlspecialchars($u['color']) ?>44;">
                                        <img src="https://robohash.org/<?= urlencode($u['avatar'] ?? 'cat1') ?>.png?set=set4&size=150x150" alt="<?= htmlspecialchars($u['name']) ?>">
                                    </div>
                                    <div class="user-info" style="flex: 1;">
                                        <h4 style="font-size: 0.95rem;"><?= htmlspecialchars($u['name']) ?></h4>
                                        <span class="user-badge" style="background-color: <?= htmlspecialchars($u['color']) ?>44; color: <?= htmlspecialchars($u['color']) ?>; font-size: 0.75rem; white-space: nowrap;">
                                            <?= $showLimit ? 'Limit: '.number_format($userLimit, 0, ',', ' ').' zł' : 'Aktywny' ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                        <?= number_format($userSpent, 2, ',', ' ') ?> zł
                                    </div>
                                </div>
                                
                                <?php if ($showLimit): ?>
                                    <div style="width: 100%;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.3rem; color: var(--text-secondary);">
                                            <span>Zużycie limitu</span>
                                            <span style="font-weight: 600; color: <?= $percent > 90 ? 'var(--error)' : 'var(--text-primary)' ?>;"><?= $percent ?>%</span>
                                        </div>
                                        <div style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden;">
                                            <div style="width: <?= $clampedPercent ?>%; height: 100%; background: <?= $percent > 90 ? 'var(--error)' : ($percent > 65 ? '#f59e0b' : '#10b981') ?>; border-radius: 10px; transition: width 0.5s ease;"></div>
                                        </div>
                                        <?php if ($remaining > 0): ?>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary); background: #f8fafc; padding: 4px 8px; border-radius: 6px; display: inline-block;">
                                                Możesz wydawać: <strong><?= number_format($daily, 2, ',', ' ') ?> zł / dzień</strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal dodawania transakcji -->
    <div id="addModal" class="modal-overlay hidden">
        <div class="modal glass-effect">
            <div class="modal-header">
                <h3>Nowy wpis do budżetu</h3>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form action="add.php" method="POST" class="add-form">
                <div class="form-group">
                    <label>Co to jest?</label>
                    <div class="type-toggle">
                        <input type="radio" id="type_expense" name="type" value="expense" checked onchange="toggleCategories()">
                        <label for="type_expense" class="radio-label red">Wydatek (-)</label>
                        <input type="radio" id="type_income" name="type" value="income" onchange="toggleCategories()">
                        <label for="type_income" class="radio-label green">Przychód (+)</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="amount">Kwota (PLN)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="date">Kiedy?</label>
                        <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="user_id">Dla kogo / Kto to?</label>
                    <select id="user_id" name="user_id" required>
                        <option value="" disabled selected>-- Wybierz osobę --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_id">Kategoria</label>
                    <select id="category_id" name="category_id" required>
                        <option value="" disabled selected>-- Wybierz z listy --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" data-type="<?= $c['type'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Kilka słów tytułem (opcjonalnie)</label>
                    <input type="text" id="description" name="description" placeholder="Napisz na co dokładnie poszły pieniądze...">
                </div>

                <button type="submit" class="btn-primary full-width">Księguj wpis!</button>
            </form>
        </div>
    </div>

    </div>

    <script>
        function openModal() {
            document.getElementById('addModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('addModal').style.opacity = '1';
                document.querySelector('.modal').style.transform = 'translateY(0)';
            }, 10);
            toggleCategories(); // Ustaw od razu filtrowanie kategorii przy starcie modala
        }

        function closeModal() {
            document.getElementById('addModal').style.opacity = '0';
            document.querySelector('.modal').style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.getElementById('addModal').classList.add('hidden');
            }, 300);
        }

        // Ukrywanie modali na overlay
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Proste filtrowanie selecta kategorii zależnie od typu (income vs expense)
        function toggleCategories() {
            const isExpense = document.getElementById('type_expense').checked;
            const targetType = isExpense ? 'expense' : 'income';
            const selectDiv = document.getElementById('category_id');
            const options = selectDiv.getElementsByTagName('option');
            
            let firstValid = null;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === "") continue; // pomijaj disabled
                const optType = options[i].getAttribute('data-type');
                if (optType === targetType) {
                    options[i].style.display = '';
                    if (!firstValid) firstValid = options[i];
                } else {
                    options[i].style.display = 'none';
                }
            }
            
            // Zresetuj wybór
            if (firstValid) {
                firstValid.selected = true;
            } else {
                selectDiv.selectedIndex = 0;
            }
        }
    </script>
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
