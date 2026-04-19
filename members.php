<?php
require_once 'db.php';

// Pobierz uzytkowników z podliczeniem transakcji
$stmt = $db->query("
    SELECT u.*, COUNT(t.id) as tx_count 
    FROM users u 
    LEFT JOIN transactions t ON u.id = t.user_id 
    GROUP BY u.id 
    ORDER BY u.name
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Członkowie rodziny - KalkoBudżet</title>
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
                <li><a href="index.php"><span class="nav-icon">📊</span> Pulpit</a></li>
                <li class="active"><a href="members.php"><span class="nav-icon">👥</span> Członkowie rodziny</a></li>
                <li><a href="#"><span class="nav-icon">📈</span> Raporty</a></li>
                <li><a href="#"><span class="nav-icon">⚙️</span> Ustawienia bazy</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Wersja 1.0 (Lokalna)</p>
            </div>
        </nav>

        <!-- Główna zawartość -->
        <main class="main-content">
            <header class="topbar" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 class="greeting">Nasi domownicy 👨‍👩‍👧‍👦</h2>
                    <p class="subtitle">Zarządzaj osobami, które mają wpływ na domowy budżet.</p>
                </div>
                <button class="btn-primary" onclick="openMemberModal()">+ Dodaj osobę</button>
            </header>

            <div class="content-grid" style="grid-template-columns: 1fr; margin-top: 2rem;">
                <section class="family-members glass-effect section-box">
                    <div class="users-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                        <?php foreach($users as $u): ?>
                            <div class="user-card" style="justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div class="user-avatar" style="border-color: <?= htmlspecialchars($u['color']) ?>; box-shadow: 0 0 15px <?= htmlspecialchars($u['color']) ?>44;">
                                        <img src="https://api.dicebear.com/7.x/<?= urlencode($u['avatar'] ?? 'avataaars') ?>/svg?seed=<?= urlencode($u['name']) ?>&backgroundColor=b6e3f4,c0aede,d1d4f9" alt="<?= htmlspecialchars($u['name']) ?>">
                                    </div>
                                    <div class="user-info">
                                        <h4><?= htmlspecialchars($u['name']) ?></h4>
                                        <span style="font-size: 0.8rem; color: #718096; background: rgba(255,255,255,0.5); padding: 2px 8px; border-radius: 12px;">Wpisy: <?= $u['tx_count'] ?></span>
                                    </div>
                                </div>
                                <form action="delete_member.php" method="POST" style="margin: 0;" onsubmit="return confirm('Na pewno usunąć tę osobę?<?= $u['tx_count'] > 0 ? '\n\nUWAGA: Trwale usunięte zostaną również wszystkie jej wpisy i transakcje (' . $u['tx_count'] . ')!' : '' ?>');">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-delete" title="Usuń członka rodziny">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Przycisk dodawania jako karta -->
                        <div class="user-card" style="justify-content: center; cursor: pointer; border: 2px dashed #cbd5e1; background: rgba(255,255,255,0.3);" onclick="openMemberModal()">
                            <span style="font-size: 2rem; color: #94a3b8; font-weight: 300;">+</span>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal dodawania członka -->
    <div id="addMemberModal" class="modal-overlay hidden">
        <div class="modal glass-effect">
            <div class="modal-header">
                <h3>Nowy członek rodziny</h3>
                <button class="close-btn" onclick="closeMemberModal()">×</button>
            </div>
            <form action="add_member.php" method="POST" class="add-form">
                <div class="form-group">
                    <label for="name">Imię / Nazwa</label>
                    <input type="text" id="name" name="name" required placeholder="np. Kasia">
                </div>
                
                <div class="form-group">
                    <label for="color">Kolor indentyfikacyjny (awatary i układ)</label>
                    <input type="color" id="color" name="color" value="#3b82f6" style="width: 100%; height: 50px; border: none; border-radius: 12px; cursor: pointer; background: transparent; padding: 0;">
                </div>

                <div class="form-group">
                    <label>Styl Awatara</label>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding: 0.5rem 0;">
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="avataaars" checked>
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Kasia" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto;">
                            <span style="font-size: 0.8rem;">Klasyczny</span>
                        </label>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="bottts">
                            <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Kasia" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto;">
                            <span style="font-size: 0.8rem;">Robot</span>
                        </label>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="fun-emoji">
                            <img src="https://api.dicebear.com/7.x/fun-emoji/svg?seed=Kasia" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto;">
                            <span style="font-size: 0.8rem;">Emoji</span>
                        </label>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="adventurer">
                            <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Kasia" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto;">
                            <span style="font-size: 0.8rem;">Przygoda</span>
                        </label>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="micah">
                            <img src="https://api.dicebear.com/7.x/micah/svg?seed=Kasia" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto;">
                            <span style="font-size: 0.8rem;">Elegancki</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary full-width" style="margin-top: 1rem;">Dodaj osobę!</button>
            </form>
        </div>
    </div>

    <script>
        function openMemberModal() {
            document.getElementById('addMemberModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('addMemberModal').style.opacity = '1';
                document.querySelector('#addMemberModal .modal').style.transform = 'translateY(0)';
            }, 10);
        }

        function closeMemberModal() {
            document.getElementById('addMemberModal').style.opacity = '0';
            document.querySelector('#addMemberModal .modal').style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.getElementById('addMemberModal').classList.add('hidden');
            }, 300);
        }

        document.getElementById('addMemberModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMemberModal();
            }
        });
    </script>
</body>
</html>
