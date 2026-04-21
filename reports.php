<?php
require_once 'db.php';

// Zmienna na pobranie miesiąca z URL (domyślnie obecny)
$month = $_GET['month'] ?? date('Y-m');

// Statystyki dla wydatków wg kategorii
$stmt = $db->prepare("
    SELECT c.name, SUM(t.amount) as total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.type = 'expense' AND t.date LIKE ?
    GROUP BY c.id
    ORDER BY total DESC
");
$stmt->execute([$month . '%']);
$expensesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Wydatki wg domownika
$stmt = $db->prepare("
    SELECT u.name, u.color, u.avatar, SUM(t.amount) as total
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.type = 'expense' AND t.date LIKE ?
    GROUP BY u.id
    ORDER BY total DESC
");
$stmt->execute([$month . '%']);
$expensesByUser = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Podliczenie wszystkich wydatkow i przychodów w miesiacu
$stmt = $db->prepare("SELECT type, SUM(amount) as total FROM transactions WHERE date LIKE ? GROUP BY type");
$stmt->execute([$month . '%']);
$totals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalExpense = $totals['expense'] ?? 0;
$totalIncome = $totals['income'] ?? 0;

$chartLabels = array_column($expensesByCategory, 'name');
$chartData = array_column($expensesByCategory, 'total');
$chartColors = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#f43f5e', '#64748b', '#84cc16'];

// --- LOGIKA PROGNOZY (tylko dla obecnego miesiąca) ---
$isCurrentMonth = ($month == date('Y-m'));
$forecastData = null;

if ($isCurrentMonth) {
    $daysInMonth = (int)date('t');
    $currentDay = (int)date('j');
    $daysLeft = $daysInMonth - $currentDay;

    // Pobierz całkowity bilans (z db.php / wszystkich czasów) dla bazy obliczeń
    $stmtBalance = $db->query("SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance FROM transactions");
    $currentBalance = (float)$stmtBalance->fetch(PDO::FETCH_ASSOC)['balance'];

    $avgDailyExpense = $currentDay > 0 ? (float)$totalExpense / $currentDay : 0;
    $projectedExpenseRemaining = $avgDailyExpense * $daysLeft;
    $projectedEndOfMonthBalance = $currentBalance - $projectedExpenseRemaining;
    
    $forecastData = [
        'avg_daily' => $avgDailyExpense,
        'days_left' => $daysLeft,
        'projected_expense' => $projectedExpenseRemaining,
        'final_balance' => $projectedEndOfMonthBalance,
        'current_balance' => $currentBalance
    ];
}

// Pobierz nieprzeczytane powiadomienia
$unreadNotifs = $db->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($unreadNotifs);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporty - Skarbonka</title>
    <!-- Nowoczesna czcionka -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="reports.php" class="active">Raporty</a>
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
            <header class="topbar" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
                <div style="text-align: left;">
                    <h2 class="greeting">Raporty i analizy 📈</h2>
                    <p class="subtitle">Zarządzaj i poznaj szczegóły domowego budżetu.</p>
                </div>
                <!-- Wybór miesiąca -->
                <form method="GET" style="display: flex; gap: 0.75rem; align-items: center; background: var(--surface); padding: 0.6rem 1.4rem; border-radius: var(--br-full); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <label for="month" style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--primary);">Miesiąc:</label>
                    <input type="month" id="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" style="padding: 0.3rem; border: none; background: transparent; width: auto; font-family: inherit; font-weight: 800; cursor: pointer; color: var(--accent);">
                </form>
            </header>

            <div class="dashboard-cards" style="margin-bottom: 2rem;">
                <div class="card glass-effect gradient-green">
                    <div class="card-icon">💎</div>
                    <h3>Wpływy</h3>
                    <p class="amount">+<?= number_format($totalIncome, 2, ',', ' ') ?> PLN</p>
                </div>
                <div class="card glass-effect gradient-red">
                    <div class="card-icon">🛒</div>
                    <h3>Wydatki</h3>
                    <p class="amount">-<?= number_format($totalExpense, 2, ',', ' ') ?> PLN</p>
                </div>
                <div class="card glass-effect gradient-blue">
                    <div class="card-icon">⚖️</div>
                    <h3>Bilans</h3>
                    <p class="amount">
                        <?= number_format($totalIncome - $totalExpense, 2, ',', ' ') ?> PLN
                    </p>
                </div>
            </div>

            <?php if ($forecastData): ?>
                <section class="forecast-section glass-effect section-box" style="margin-bottom: 2rem; border-left: 8px solid var(--accent-color);">
                    <div class="section-header">
                        <h3>Przewidywana przyszłość finansowa 🔮</h3>
                        <span class="badge" style="background: var(--accent-color); color: white; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem;">Analiza Smart</span>
                    </div>
                    <div class="forecast-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                        <div class="forecast-item">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Średnie dzienne wydatki</p>
                            <h4 style="font-size: 1.4rem;"><?= number_format($forecastData['avg_daily'], 2, ',', ' ') ?> zł</h4>
                            <small style="color: var(--text-secondary);">Wyliczone z <?= (int)date('j') ?> dni</small>
                        </div>
                        <div class="forecast-item">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Dni do końca miesiąca</p>
                            <h4 style="font-size: 1.4rem;"><?= $forecastData['days_left'] ?> dni</h4>
                            <small style="color: var(--text-secondary);">Pozostało czasu</small>
                        </div>
                        <div class="forecast-item">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Przewidywane wydatki</p>
                            <h4 style="color: var(--red-color); font-size: 1.4rem;">-<?= number_format($forecastData['projected_expense'], 2, ',', ' ') ?> zł</h4>
                            <small style="color: var(--text-secondary);">Szacunkowy koszt</small>
                        </div>
                        <div class="forecast-item highlighted-result" style="background: var(--bg-color); padding: 1.5rem; border-radius: 15px; border: 1px solid var(--border-color);">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">Przewidywany stan na koniec</p>
                            <h4 style="font-size: 1.6rem; color: <?= $forecastData['final_balance'] >= 0 ? 'var(--green-color)' : 'var(--red-color)' ?>;">
                                <?= number_format($forecastData['final_balance'], 2, ',', ' ') ?> zł
                            </h4>
                            <small style="font-weight: 600;"><?= $forecastData['final_balance'] >= 0 ? '✅ Będziesz na plusie!' : '⚠️ Może zabraknąć środków' ?></small>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <div class="content-grid">
                <section class="glass-effect section-box">
                    <div class="section-header">
                        <h3>Wydatki według kategorii</h3>
                    </div>
                    <?php if (!empty($expensesByCategory)): ?>
                        <div style="width: 100%; max-width: 320px; margin: 0 auto 2.5rem; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                        <?php if (empty($expensesByCategory)): ?>
                            <div class="empty-state">
                                <p style="color: var(--text-secondary); text-align: center;">Brak wydatków w zapisanym miesiącu.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($expensesByCategory as $cat): ?>
                                <?php $percent = $totalExpense > 0 ? round(($cat['total'] / $totalExpense) * 100) : 0; ?>
                                <div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem;">
                                        <span style="font-weight: 500;"><?= htmlspecialchars($cat['name']) ?></span>
                                        <strong><?= number_format($cat['total'], 2, ',', ' ') ?> PLN <span style="color:var(--text-secondary); font-size:0.85rem; font-weight:normal;">(<?= $percent ?>%)</span></strong>
                                    </div>
                                    <div style="width: 100%; background: #e2e8f0; border-radius: 6px; height: 12px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="width: <?= $percent ?>%; background: linear-gradient(90deg, #ef4444, #f87171); height: 100%; transition: width 0.5s ease-out; border-radius: 6px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="glass-effect section-box">
                    <div class="section-header">
                        <h3>Kto wydaje najwięcej?</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <?php if (empty($expensesByUser)): ?>
                             <div class="empty-state">
                                 <p style="color: var(--text-secondary); text-align: center;">Brak historii w tym miesiącu.</p>
                             </div>
                        <?php else: ?>
                            <?php foreach($expensesByUser as $u): ?>
                                <?php $uPercent = $totalExpense > 0 ? round(($u['total'] / $totalExpense) * 100) : 0; ?>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <div class="user-avatar" style="width: 50px; height: 50px; border: 2px solid <?= htmlspecialchars($u['color']) ?>; box-shadow: 0 0 10px <?= htmlspecialchars($u['color']) ?>44; background-color: #f1f5f9;">
                                        <img src="https://robohash.org/<?= urlencode($u['avatar'] ?? 'cat1') ?>.png?set=set4&size=150x150" alt="<?= htmlspecialchars($u['name']) ?>">
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                                            <span style="font-weight: 600;"><?= htmlspecialchars($u['name']) ?></span>
                                            <strong><?= number_format($u['total'], 2, ',', ' ') ?> PLN</strong>
                                        </div>
                                        <div style="width: 100%; background: #e2e8f0; border-radius: 6px; height: 10px; overflow: hidden;">
                                            <div style="width: <?= $uPercent ?>%; background: <?= htmlspecialchars($u['color']) ?>; height: 100%; border-radius: 6px;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
        <!-- Stopka -->
        <footer class="app-footer">
            Made by <span>Emilia Dudzik</span>
        </footer>
    </div>

    <?php if (!empty($expensesByCategory)): ?>
    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        // Powielanie kolorów, jeśli kategorii jest więcej niż w predefiniowanej palecie
        let baseColors = <?= json_encode($chartColors) ?>;
        let dataCount = <?= count($chartData) ?>;
        let colors = [];
        for(let i=0; i<dataCount; i++){ colors.push(baseColors[i % baseColors.length]); }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Outfit', size: 12 },
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1e293b',
                        bodyColor: '#334155',
                        bodyFont: { family: 'Outfit', size: 14, weight: 'bold' },
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
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
