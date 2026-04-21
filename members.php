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
    <div class="app-wrapper">
        <!-- Pasek górny -->
        <header class="app-header">
            <div class="logo">
                <div class="logo-icon">☕</div>
                <h1>KalkoBudżet</h1>
            </div>
            
            <nav class="top-nav">
                <a href="index.php">Pulpit</a>
                <a href="members.php" class="active">Rodzina</a>
                <a href="reports.php">Raporty</a>
                <a href="settings.php">Ustawienia</a>
            </nav>
        </header>

        <!-- Główna zawartość -->
        <main class="main-content">
            <header class="topbar" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="text-align: left;">
                    <h2 class="greeting">Nasi domownicy 👨‍👩‍👧‍👦</h2>
                    <p class="subtitle">Zarządzaj osobami, które mają wpływ na domowy budżet.</p>
                </div>
                <button class="btn-primary" onclick="openMemberModal()">+ Dodaj osobę</button>
            </header>

            <div class="content-grid" style="grid-template-columns: 1fr; margin-top: 2rem;">
                <section class="family-members glass-effect section-box">
                    <div class="users-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php foreach($users as $u): ?>
                            <div class="user-card" style="border-left-color: <?= htmlspecialchars($u['color']) ?>; padding: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1.25rem; flex: 1;">
                                    <div class="user-avatar" style="border-color: <?= htmlspecialchars($u['color']) ?>; box-shadow: 0 0 15px <?= htmlspecialchars($u['color']) ?>44;">
                                        <img src="https://robohash.org/<?= urlencode($u['avatar'] ?? 'cat1') ?>.png?set=set4&size=150x150" alt="<?= htmlspecialchars($u['name']) ?>">
                                    </div>
                                    <div class="user-info">
                                        <h4 style="font-size: 1.2rem;"><?= htmlspecialchars($u['name']) ?></h4>
                                        <span class="user-badge" style="background-color: <?= htmlspecialchars($u['color']) ?>22; color: <?= htmlspecialchars($u['color']) ?>;">Wpisy: <?= $u['tx_count'] ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.75rem; align-items: center;">
                                    <button type="button" class="btn-edit" title="Edytuj" onclick="openEditMemberModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars($u['color']) ?>', '<?= htmlspecialchars($u['avatar'] ?? 'micah') ?>', <?= $u['monthly_limit'] ?? 0 ?>)" style="background: white; color: var(--accent); border: 1px solid var(--border); width: 44px; height: 44px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition);">✎</button>
                                    <form action="delete_member.php" method="POST" style="margin: 0;" onsubmit="return confirm('Na pewno usunąć tę osobę?<?= $u['tx_count'] > 0 ? '\n\nUWAGA: Trwale usunięte zostaną również wszystkie jej wpisy i transakcje (' . $u['tx_count'] . ')!' : '' ?>');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-delete" title="Usuń" style="width: 44px; height: 44px;">×</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Przycisk dodawania jako karta -->
                        <div class="user-card" style="justify-content: center; cursor: pointer; border: 2px dashed var(--border); background: rgba(255,255,255,0.3); min-height: 100px; transition: var(--transition);" onclick="openMemberModal()" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border)';" >
                            <span style="font-size: 2.5rem; color: var(--text-muted); font-weight: 300;">+</span>
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
                    <label for="monthly_limit">Miesięczny limit wydatków (opcjonalnie)</label>
                    <input type="number" id="monthly_limit" name="monthly_limit" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Wybierz Kotka 🐈</label>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding: 0.5rem 0;">
                        <?php for($i=1; $i<=6; $i++): ?>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" value="cat<?= $i ?>" <?= $i==1 ? 'checked' : '' ?>>
                            <img src="https://robohash.org/cat<?= $i ?>.png?set=set4&size=60x60" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto; background-color: #f1f5f9; border: 2px solid transparent;">
                            <span style="font-size: 0.8rem;">Kotek <?= $i ?></span>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="btn-primary full-width" style="margin-top: 1rem;">Dodaj osobę!</button>
            </form>
        </div>
    </div>

    <!-- Modal edycji członka -->
    <div id="editMemberModal" class="modal-overlay hidden">
        <div class="modal glass-effect">
            <div class="modal-header">
                <h3>Edytuj domownika</h3>
                <button class="close-btn" onclick="closeEditMemberModal()">×</button>
            </div>
            <form action="edit_member.php" method="POST" class="add-form">
                <input type="hidden" id="edit_id" name="id" value="">
                <div class="form-group">
                    <label for="edit_name">Imię / Nazwa</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_color">Kolor indentyfikacyjny</label>
                    <input type="color" id="edit_color" name="color" style="width: 100%; height: 50px; border: none; border-radius: 12px; cursor: pointer; background: transparent; padding: 0;">
                </div>

                <div class="form-group">
                    <label for="edit_monthly_limit">Miesięczny limit wydatków (opcjonalnie)</label>
                    <input type="number" id="edit_monthly_limit" name="monthly_limit" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Wybierz Kotka 🐈</label>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding: 0.5rem 0;">
                        <?php for($i=1; $i<=6; $i++): ?>
                        <label style="cursor: pointer; text-align: center;">
                            <input type="radio" name="avatar" id="edit_avatar_cat<?= $i ?>" value="cat<?= $i ?>">
                            <img src="https://robohash.org/cat<?= $i ?>.png?set=set4&size=60x60" style="width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto; background-color: #f1f5f9; border: 2px solid transparent;">
                            <span style="font-size: 0.8rem;">Kotek <?= $i ?></span>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="btn-primary full-width" style="margin-top: 1rem;">Zapisz zmiany!</button>
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

        function openEditMemberModal(id, name, color, avatar, limit) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_color').value = color;
            document.getElementById('edit_monthly_limit').value = limit;
            
            let avatarRadio = document.getElementById('edit_avatar_' + avatar);
            if(avatarRadio) avatarRadio.checked = true;

            document.getElementById('editMemberModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('editMemberModal').style.opacity = '1';
                document.querySelector('#editMemberModal .modal').style.transform = 'translateY(0)';
            }, 10);
        }

        function closeEditMemberModal() {
            document.getElementById('editMemberModal').style.opacity = '0';
            document.querySelector('#editMemberModal .modal').style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.getElementById('editMemberModal').classList.add('hidden');
            }, 300);
        }

        document.getElementById('editMemberModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditMemberModal();
            }
        });
    </script>
</body>
</html>
