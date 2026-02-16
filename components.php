<?php
require_once 'functions.php';

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Получение категорий
$categories = $pdo->query("SELECT * FROM component_categories")->fetchAll();

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO components (category_id, component_name, price, description, in_stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['category_id'],
            $_POST['component_name'],
            $_POST['price'],
            $_POST['description'],
            isset($_POST['in_stock']) ? 1 : 0
        ]);
        redirectWithMessage('components.php', 'success', 'Комплектующее успешно добавлено!');
    }
    
    if (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE components SET category_id = ?, component_name = ?, price = ?, description = ?, in_stock = ? WHERE component_id = ?");
        $stmt->execute([
            $_POST['category_id'],
            $_POST['component_name'],
            $_POST['price'],
            $_POST['description'],
            isset($_POST['in_stock']) ? 1 : 0,
            $_POST['component_id']
        ]);
        redirectWithMessage('components.php', 'success', 'Комплектующее успешно обновлено!');
    }
}

// Удаление с проверкой
if (isset($_GET['delete'])) {
    try {
        safeDeleteWithCheck($pdo, 'components', 'component_id', $_GET['delete'], [
            'order_items' => 'component_id',
            'receipts' => 'component_id'
        ]);
        redirectWithMessage('components.php', 'success', 'Комплектующее успешно удалено!');
    } catch (Exception $e) {
        redirectWithMessage('components.php', 'error', $e->getMessage());
    }
}

// Получение для редактирования
$edit_component = null;
if (isset($_GET['edit'])) {
    $edit_component = getRecord($pdo, 'components', 'component_id', $_GET['edit']);
}

// Получение всех комплектующих
$components = $pdo->query("
    SELECT c.*, cc.category_name 
    FROM components c 
    LEFT JOIN component_categories cc ON c.category_id = cc.category_id 
    ORDER BY c.component_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Комплектующие - Танковая База</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="scanline"></div>
    
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php" class="icon-tank">Главная</a>
            
            <?php if ($isLoggedIn): ?>
                <a href="orders.php" class="icon-military">Заказы</a>
                <a href="clients.php" class="icon-tank">Клиенты</a>
                <a href="components.php" class="active icon-military">Комплектующие</a>
                
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
            <h1>⚙️ АРСЕНАЛ КОМПЛЕКТУЮЩИХ</h1>
            <div class="subtitle">УПРАВЛЕНИЕ ТАНКОВЫМИ ДЕТАЛЯМИ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <div class="tank-tabs">
            <div class="tank-tab <?= !$edit_component ? 'active' : '' ?>" onclick="window.location.href='components.php'">
                Все комплектующие
            </div>
            <div class="tank-tab <?= $edit_component ? 'active' : '' ?>">
                <?= $edit_component ? 'Редактирование' : 'Добавление' ?>
            </div>
        </div>
        
        <?php if ($edit_component): ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Редактирование комплектующего ID: <?= $edit_component['component_id'] ?></span></h2>
            <form method="POST">
                <input type="hidden" name="component_id" value="<?= $edit_component['component_id'] ?>">
                <div class="form-group">
                    <label>Категория:</label>
                    <select name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" 
                            <?= $cat['category_id'] == $edit_component['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="component_name" 
                           value="<?= htmlspecialchars($edit_component['component_name']) ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Цена:</label>
                    <input type="number" step="0.01" name="price" 
                           value="<?= $edit_component['price'] ?>" 
                           class="edit-input" required>
                </div>
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" class="edit-input"><?= htmlspecialchars($edit_component['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="in_stock" 
                               <?= $edit_component['in_stock'] ? 'checked' : '' ?>>
                        В наличии
                    </label>
                </div>
                <button type="submit" name="edit" class="btn btn-edit">
                    <span class="icon-tank">Сохранить изменения</span>
                </button>
                <a href="components.php" class="btn">Отмена</a>
            </form>
        </div>
        <?php else: ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Добавить комплектующее</span></h2>
            <form method="POST">
                <div class="form-group">
                    <label>Категория:</label>
                    <select name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="component_name" placeholder="Введите название" required>
                </div>
                <div class="form-group">
                    <label>Цена:</label>
                    <input type="number" step="0.01" name="price" placeholder="Введите цену" required>
                </div>
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" placeholder="Введите описание"></textarea>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="in_stock" checked> В наличии</label>
                </div>
                <button type="submit" name="add" class="btn btn-success">
                    <span class="icon-military">Добавить комплектующее</span>
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <h2><span class="icon-military">Список комплектующих</span> <span class="tank-badge"><?= count($components) ?></span></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего позиций</div>
                <div class="stat-number"><?= count($components) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">В наличии</div>
                <div class="stat-number">
                    <?= count(array_filter($components, fn($c) => $c['in_stock'])) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Средняя цена</div>
                <div class="stat-number">
                    <?php 
                    $avg = count($components) > 0 ? 
                        array_sum(array_column($components, 'price')) / count($components) : 0;
                    echo number_format($avg, 2);
                    ?> ₽
                </div>
            </div>
        </div>
        
        <div class="tank-search">
            <input type="text" id="componentSearch" placeholder="Поиск комплектующих...">
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Категория</th>
                <th>Название</th>
                <th>Цена</th>
                <th>Описание</th>
                <th>В наличии</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($components as $comp): ?>
            <tr>
                <td><span class="price">#<?= $comp['component_id'] ?></span></td>
                <td><?= htmlspecialchars($comp['category_name']) ?></td>
                <td><strong><?= htmlspecialchars($comp['component_name']) ?></strong></td>
                <td><span class="price"><?= number_format($comp['price'], 2) ?> ₽</span></td>
                <td><?= htmlspecialchars($comp['description']) ?: '-' ?></td>
                <td>
                    <span class="status <?= $comp['in_stock'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $comp['in_stock'] ? 'В наличии' : 'Нет в наличии' ?>
                    </span>
                </td>
                <td>
                    <a href="?edit=<?= $comp['component_id'] ?>" class="btn btn-edit">✎ Редактировать</a>
                    <a href="#" onclick="confirmDelete(<?= $comp['component_id'] ?>, '<?= htmlspecialchars($comp['component_name']) ?>')" 
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
                <p>Вы уверены, что хотите удалить комплектующее:</p>
                <p id="deleteItemName" style="color: var(--ammo-gold); font-weight: bold; font-size: 1.2rem;"></p>
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
        let itemToDelete = null;
        let itemName = '';
        
        function confirmDelete(id, name) {
            itemToDelete = id;
            itemName = name;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            itemToDelete = null;
            itemName = '';
        }
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (itemToDelete) {
                window.location.href = '?delete=' + itemToDelete;
            }
        };
        
        // Поиск
        document.getElementById('componentSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('table tr');
            
            rows.forEach((row, index) => {
                if (index === 0) return;
                
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