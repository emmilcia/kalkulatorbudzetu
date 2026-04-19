<?php
require_once 'db.php';

// Pobierz uzytkowników
$stmt = $db->query("SELECT * FROM users");
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
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rodzinny Kalkulator Budżetu</title>
    <!-- Nowoczesna czcionka -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
                <li class="active"><a href="index.php"><span class="nav-icon">📊</span> Pulpit</a></li>
                <li><a href="members.php"><span class="nav-icon">👥</span> Członkowie rodziny</a></li>
                <li><a href="reports.php"><span class="nav-icon">📈</span> Raporty</a></li>
                <li><a href="settings.php"><span class="nav-icon">⚙️</span> Ustawienia bazy</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Wersja 1.0 (Lokalna)</p>
            </div>
        </nav>

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
                    <div class="card-glow"></div>
                </div>
                <div class="card glass-effect gradient-green">
                    <div class="card-icon">📈</div>
                    <h3>Wpływy w miesiącu</h3>
                    <p class="amount">+<?= $income ?> PLN</p>
                    <div class="card-glow"></div>
                </div>
                <div class="card glass-effect gradient-red">
                    <div class="card-icon">🔪</div>
                    <h3>Wydatki w miesiącu</h3>
                    <p class="amount">-<?= $expense ?> PLN</p>
                    <div class="card-glow"></div>
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
                        <?php foreach($users as $u): ?>
                            <div class="user-card">
                                <div class="user-avatar" style="border-color: <?= htmlspecialchars($u['color']) ?>; box-shadow: 0 0 15px <?= htmlspecialchars($u['color']) ?>44; background-color: #f1f5f9;">
                                    <!-- Robohash Cats -->
                                    <img src="https://robohash.org/<?= urlencode($u['avatar'] ?? 'cat1') ?>.png?set=set4&size=150x150" alt="<?= htmlspecialchars($u['name']) ?>">
                                </div>
                                <div class="user-info">
                                    <h4><?= htmlspecialchars($u['name']) ?></h4>
                                    <span class="user-badge" style="background-color: <?= htmlspecialchars($u['color']) ?>44; color: <?= htmlspecialchars($u['color']) ?>;">Atywny</span>
                                </div>
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

        // Ukrywanie modala na overlay
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
</body>
</html>
