<?php
require_once 'functions.php';

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Добавление клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO clients (last_name, first_name, middle_name, address, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['last_name'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['address'],
            $_POST['phone']
        ]);
        redirectWithMessage('clients.php', 'success', 'Клиент успешно добавлен!');
    }
    
    // Редактирование клиента
    if (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE clients SET last_name = ?, first_name = ?, middle_name = ?, address = ?, phone = ? WHERE client_id = ?");
        $stmt->execute([
            $_POST['last_name'],
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['address'],
            $_POST['phone'],
            $_POST['client_id']
        ]);
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
    
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php" class="icon-tank">Главная</a>
            
            <?php if ($isLoggedIn): ?>
                <a href="orders.php" class="icon-military">Заказы</a>
                <a href="clients.php" class="active icon-tank">Клиенты</a>
                <a href="components.php" class="icon-military">Комплектующие</a>
                
                <?php if ($userRole === 'admin'): ?>
                    <a href="categories.php" class="icon-ammo">Категории</a>
                    <a href="delivery_zones.php" class="icon-tank">Доставка</a>
                    <a href="warranties.php" class="icon-military">Гарантии</a>
                <?php endif; ?>
                
                <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                    <span style="color: var(--digital-green);">
                        🎖️ <?= htmlspecialchars($username) ?>
                    </span>
                    <a href="logout.php" class="btn btn-delete" style="padding: 5px 10px;">Выход</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="icon-military">Вход</a>
                <a href="register.php" class="icon-ammo">Регистрация</a>
                <a href="orders.php" class="icon-tank">Заказы</a>
            <?php endif; ?>
        </nav>
    </div>
    
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
            <h2><span class="icon-tank">Добавить нового клиента</span></h2>
            <form method="POST">
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