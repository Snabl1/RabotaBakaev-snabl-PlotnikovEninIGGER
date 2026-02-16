<?php
require_once 'functions.php';
requireAdmin();

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Добавление клиента (полноценный пользователь с логином/паролем)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
        if (empty($username) || empty($email) || empty($password)) {
            redirectWithMessage('clients.php', 'error', 'Заполните логин, email и пароль!');
        }
        if ($password !== $confirm) {
            redirectWithMessage('clients.php', 'error', 'Пароли не совпадают!');
        }
        if (strlen($password) < 6) {
            redirectWithMessage('clients.php', 'error', 'Пароль не менее 6 символов!');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('clients.php', 'error', 'Некорректный email!');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            redirectWithMessage('clients.php', 'error', 'Пользователь с таким логином или email уже есть!');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("
                INSERT INTO clients (username, password, email, last_name, first_name, middle_name, phone, address, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $username, $hash, $email,
                $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'] ?? '',
                $_POST['phone'] ?? '', $_POST['address'], $role
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'is_active') !== false) {
                $stmt = $pdo->prepare("
                    INSERT INTO clients (username, password, email, last_name, first_name, middle_name, phone, address, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $username, $hash, $email,
                    $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'] ?? '',
                    $_POST['phone'] ?? '', $_POST['address'], $role
                ]);
            } else {
                throw $e;
            }
        }
        redirectWithMessage('clients.php', 'success', 'Клиент успешно добавлен!');
    }
    
    // Редактирование клиента
    if (isset($_POST['edit'])) {
        $client_id = (int) $_POST['client_id'];
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
        if (empty($username) || empty($email)) {
            redirectWithMessage('clients.php', 'error', 'Логин и email обязательны!');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('clients.php', 'error', 'Некорректный email!');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE client_id != ? AND (username = ? OR email = ?)");
        $stmt->execute([$client_id, $username, $email]);
        if ($stmt->fetchColumn() > 0) {
            redirectWithMessage('clients.php', 'error', 'Логин или email уже заняты другим пользователем!');
        }
        if ($new_password !== '') {
            if (strlen($new_password) < 6) {
                redirectWithMessage('clients.php', 'error', 'Новый пароль не менее 6 символов!');
            }
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE clients SET username = ?, email = ?, password = ?, last_name = ?, first_name = ?, middle_name = ?, address = ?, phone = ?, role = ? WHERE client_id = ?");
            $stmt->execute([
                $username, $email, $hash,
                $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'] ?? '',
                $_POST['address'], $_POST['phone'] ?? '', $role, $client_id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE clients SET username = ?, email = ?, last_name = ?, first_name = ?, middle_name = ?, address = ?, phone = ?, role = ? WHERE client_id = ?");
            $stmt->execute([
                $username, $email,
                $_POST['last_name'], $_POST['first_name'], $_POST['middle_name'] ?? '',
                $_POST['address'], $_POST['phone'] ?? '', $role, $client_id
            ]);
        }
        redirectWithMessage('clients.php', 'success', 'Клиент успешно обновлен!');
    }
}

// Удаление клиента с проверкой заказов
if (isset($_GET['delete'])) {
    try {
        safeDeleteWithCheck($pdo, 'clients', 'client_id', $_GET['delete'], [
            'orders' => 'client_id'
        ]);
        redirectWithMessage('clients.php', 'success', 'Клиент успешно удален!');
    } catch (Exception $e) {
        redirectWithMessage('clients.php', 'error', $e->getMessage());
    }
}

// Получение клиента для редактирования
$edit_client = null;
if (isset($_GET['edit'])) {
    $edit_client = getRecord($pdo, 'clients', 'client_id', $_GET['edit']);
}

// Получение всех клиентов
$clients = $pdo->query("SELECT * FROM clients ORDER BY client_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты - Танковая База</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="scanline"></div>
    
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="tank-logo">
            <h1>🎯 КЛИЕНТСКАЯ БАЗА</h1>
            <div class="subtitle">УПРАВЛЕНИЕ СОЛДАТАМИ-ПОКУПАТЕЛЯМИ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <div class="tank-tabs">
            <div class="tank-tab <?= !$edit_client ? 'active' : '' ?>" onclick="window.location.href='clients.php'">
                Все клиенты
            </div>
            <div class="tank-tab <?= $edit_client ? 'active' : '' ?>">
                <?= $edit_client ? 'Редактирование' : 'Добавление' ?>
            </div>
        </div>
        
        <?php if ($edit_client): ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Редактирование клиента ID: <?= $edit_client['client_id'] ?></span></h2>
            <form method="POST">
                <input type="hidden" name="client_id" value="<?= $edit_client['client_id'] ?>">
                <div class="form-group">
                    <label>Логин:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($edit_client['username'] ?? '') ?>" 
                           class="edit-input" required pattern="[A-Za-z0-9_]{3,20}" title="Латиница, цифры, _, 3–20 символов">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_client['email'] ?? '') ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Новый пароль (оставьте пустым, чтобы не менять):</label>
                    <input type="password" name="new_password" class="edit-input" minlength="6" placeholder="Не заполняйте, если не меняете">
                </div>
                <div class="form-group">
                    <label>Роль:</label>
                    <select name="role" class="tank-select">
                        <option value="user" <?= ($edit_client['role'] ?? '') === 'user' ? 'selected' : '' ?>>Клиент (user)</option>
                        <option value="admin" <?= ($edit_client['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Администратор (admin)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($edit_client['last_name']) ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($edit_client['first_name']) ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($edit_client['middle_name'] ?? '') ?>" 
                           class="edit-input">
                </div>
                <div class="form-group">
                    <label>Адрес:</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($edit_client['address']) ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Телефон:</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($edit_client['phone'] ?? '') ?>" 
                           class="edit-input">
                </div>
                <button type="submit" name="edit" class="btn btn-edit">
                    <span class="icon-tank">Сохранить изменения</span>
                </button>
                <a href="clients.php" class="btn">Отмена</a>
            </form>
        </div>
        <?php else: ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Добавить нового клиента (пользователя)</span></h2>
            <form method="POST">
                <div class="form-group">
                    <label>Логин:</label>
                    <input type="text" name="username" placeholder="Латиница, цифры, _ (3–20 символов)" required pattern="[A-Za-z0-9_]{3,20}">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label>Пароль:</label>
                    <input type="password" name="password" placeholder="Не менее 6 символов" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Подтверждение пароля:</label>
                    <input type="password" name="confirm_password" placeholder="Повторите пароль" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Роль:</label>
                    <select name="role" class="tank-select">
                        <option value="user">Клиент (user)</option>
                        <option value="admin">Администратор (admin)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="last_name" placeholder="Введите фамилию" required>
                </div>
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="first_name" placeholder="Введите имя" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="middle_name" placeholder="Введите отчество">
                </div>
                <div class="form-group">
                    <label>Адрес:</label>
                    <input type="text" name="address" placeholder="Введите адрес" required>
                </div>
                <div class="form-group">
                    <label>Телефон:</label>
                    <input type="text" name="phone" placeholder="Введите телефон">
                </div>
                <button type="submit" name="add" class="btn btn-success">
                    <span class="icon-military">Добавить клиента</span>
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <h2><span class="icon-military">Список клиентов</span> <span class="tank-badge"><?= count($clients) ?></span></h2>
        
        <div class="tank-search">
            <input type="text" id="clientSearch" placeholder="Поиск клиента...">
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Фамилия</th>
                <th>Имя</th>
                <th>Отчество</th>
                <th>Адрес</th>
                <th>Телефон</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><span class="price">#<?= $client['client_id'] ?></span></td>
                <td><strong><?= htmlspecialchars($client['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($client['first_name']) ?></td>
                <td><?= htmlspecialchars($client['middle_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($client['address']) ?></td>
                <td><?= htmlspecialchars($client['phone'] ?? '-') ?></td>
                <td>
                    <a href="?edit=<?= $client['client_id'] ?>" class="btn btn-edit">✎ Редактировать</a>
                    <a href="#" onclick="confirmDelete(<?= $client['client_id'] ?>)" 
                       class="btn btn-delete">🗑️ Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">×</span>
                <h3>⚠️ ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ</h3>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этого клиента?</p>
                <p><strong>Это действие нельзя отменить!</strong></p>
                <div class="tank-progress">
                    <div class="progress-bar" style="width: 100%;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn">Отмена</button>
                <button id="confirmDeleteBtn" class="btn btn-delete">УДАЛИТЬ</button>
            </div>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Все системы в норме
    </div>
    
    <script>
        let clientToDelete = null;
        
        function confirmDelete(clientId) {
            clientToDelete = clientId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            clientToDelete = null;
        }
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (clientToDelete) {
                window.location.href = '?delete=' + clientToDelete;
            }
        };
        
        // Поиск клиентов
        document.getElementById('clientSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('table tr');
            
            rows.forEach((row, index) => {
                if (index === 0) return; // Пропускаем заголовок
                
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        };
        
        // Включить сканирующую линию
        document.querySelector('.scanline').style.display = 'block';
    </script>
</body>
</html>