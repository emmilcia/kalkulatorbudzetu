<?php
require_once 'db.php';

// Pobierz parametry filtrowania
$q = $_GET['q'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Budowanie zapytania SQL
$sql = "SELECT t.*, u.name as user_name, u.color, c.name as category_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($q)) {
    $sql .= " AND (t.description LIKE ? OR c.name LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if (!empty($user_id)) {
    $sql .= " AND t.user_id = ?";
    $params[] = $user_id;
}

if (!empty($category_id)) {
    $sql .= " AND t.category_id = ?";
    $params[] = $category_id;
}

if (!empty($date_from)) {
    $sql .= " AND t.date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND t.date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY t.date DESC, t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz dane do filtrów
$users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT id, name, type FROM categories ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);

// Pobierz nieprzeczytane powiadomienia
$unreadNotifs = $db->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count($unreadNotifs);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia - Skarbonka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-wrapper">
        <header class="app-header">
            <div class="logo">
                <div class="logo-icon">🐷</div>
                <h1>Skarbonka</h1>
            </div>

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
            <nav class="top-nav">
                <a href="index.php">Pulpit</a>
                <a href="members.php">Rodzina</a>
                <a href="history.php" class="active">Historia</a>
                <a href="reports.php">Raporty</a>
                <a href="settings.php">Ustawienia</a>
            </nav>
        </header>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2 class="greeting">Historia wpisów 📜</h2>
                    <p class="subtitle">Przeglądaj, filtruj i analizuj każdą wydaną złotówkę.</p>
                </div>
            </header>

            <!-- Pasek filtrów -->
            <section class="glass-effect section-box filter-bar">
                <form method="GET" action="history.php" style="display: contents;">
                    <div class="filter-group">
                        <label for="q">Szukaj</label>
                        <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Opis lub kategoria...">
                    </div>
                    <div class="filter-group">
                        <label for="user_id">Domownik</label>
                        <select id="user_id" name="user_id">
                            <option value="">Wszyscy</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $user_id == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="category_id">Kategoria</label>
                        <select id="category_id" name="category_id">
                            <option value="">Wszystkie</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> (<?= $c['type'] == 'income' ? '+' : '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Od</label>
                        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Do</label>
                        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <button type="submit" class="btn-primary">Filtruj</button>
                    <a href="history.php" class="btn-secondary" style="text-decoration: none; padding: 0.9rem 1.5rem; background: var(--bg-color); border-radius: 12px; color: var(--primary); font-weight: 700; border: 1px solid var(--border);">Reset</a>
                </form>
            </section>

            <!-- Lista wyników -->
            <section class="glass-effect section-box">
                <div class="section-header">
                    <h3>Wyniki wyszukiwania (<?= count($transactions) ?>)</h3>
                    <?php if (count($transactions) > 0): ?>
                        <button class="btn-secondary" onclick="exportToCSV()" style="font-size: 0.8rem; padding: 0.5rem 1rem;">📥 Eksportuj CSV</button>
                    <?php endif; ?>
                </div>

                <div class="history-list">
                    <?php if (empty($transactions)): ?>
                        <div class="empty-state">
                            <span>🔍</span>
                            <p>Nie znaleźliśmy nic pasującego do Twoich filtrów.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($transactions as $t): ?>
                            <div class="transaction-item history-item">
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
                                <form action="delete.php" method="POST" style="margin: 0; opacity: 0;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="redirect" value="history.php?<?= http_build_query($_GET) ?>">
                                    <button type="submit" class="btn-delete" title="Usuń wpis" style="border: none; background: none; font-size: 1.5rem; color: var(--error); cursor: pointer;">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function exportToCSV() {
            let csv = 'Data;Osoba;Kategoria;Opis;Kwota;Typ\n';
            <?php foreach($transactions as $t): ?>
                csv += '<?= $t['date'] ?>;';
                csv += '<?= addslashes($t['user_name']) ?>;';
                csv += '<?= addslashes($t['category_name']) ?>;';
                csv += '<?= addslashes(str_replace(["\r", "\n", ";"], " ", $t['description'])) ?>;';
                csv += '<?= $t['amount'] ?>;';
                csv += '<?= $t['type'] ?>\n';
            <?php endforeach; ?>

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "historia_skarbonka_<?= date('Y-m-d') ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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
