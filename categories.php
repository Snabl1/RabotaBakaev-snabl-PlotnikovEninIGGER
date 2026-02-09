<?php
require_once 'functions.php';

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO component_categories (category_name) VALUES (?)");
        $stmt->execute([$_POST['category_name']]);
        redirectWithMessage('categories.php', 'success', 'Категория успешно добавлена!');
    }
    
    // Редактирование категории
    if (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE component_categories SET category_name = ? WHERE category_id = ?");
        $stmt->execute([
            $_POST['category_name'],
            $_POST['category_id']
        ]);
        redirectWithMessage('categories.php', 'success', 'Категория успешно обновлена!');
    }
}

// Удаление с проверкой
if (isset($_GET['delete'])) {
    try {
        safeDeleteWithCheck($pdo, 'component_categories', 'category_id', $_GET['delete'], [
            'components' => 'category_id'
        ]);
        redirectWithMessage('categories.php', 'success', 'Категория успешно удалена!');
    } catch (Exception $e) {
        redirectWithMessage('categories.php', 'error', $e->getMessage());
    }
}

// Получение категории для редактирования
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_category = getRecord($pdo, 'component_categories', 'category_id', $_GET['edit']);
}

// Получение всех категорий с количеством товаров
$categories = $pdo->query("
    SELECT cc.*, COUNT(c.component_id) as item_count 
    FROM component_categories cc 
    LEFT JOIN components c ON cc.category_id = c.category_id 
    GROUP BY cc.category_id 
    ORDER BY cc.category_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Категории - Танковая База</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="scanline"></div>
    
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php" class="icon-tank">Главная</a>
            <a href="clients.php" class="icon-military">Клиенты</a>
            <a href="categories.php" class="active icon-ammo">Категории</a>
            <a href="components.php" class="icon-tank">Комплектующие</a>
            <a href="orders.php" class="icon-military">Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="tank-logo">
            <h1>⚙️ КАТЕГОРИИ КОМПЛЕКТУЮЩИХ</h1>
            <div class="subtitle">КЛАССИФИКАЦИЯ ТАНКОВЫХ ДЕТАЛЕЙ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <div class="tank-tabs">
            <div class="tank-tab <?= !$edit_category ? 'active' : '' ?>" onclick="window.location.href='categories.php'">
                Все категории
            </div>
            <div class="tank-tab <?= $edit_category ? 'active' : '' ?>">
                <?= $edit_category ? 'Редактирование' : 'Добавление' ?>
            </div>
        </div>
        
        <?php if ($edit_category): ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Редактирование категории ID: <?= $edit_category['category_id'] ?></span></h2>
            <form method="POST">
                <input type="hidden" name="category_id" value="<?= $edit_category['category_id'] ?>">
                <div class="form-group">
                    <label>Название категории:</label>
                    <input type="text" name="category_name" 
                           value="<?= htmlspecialchars($edit_category['category_name']) ?>" 
                           class="edit-input" required
                           placeholder="Введите название категории">
                </div>
                <button type="submit" name="edit" class="btn btn-edit">
                    <span class="icon-tank">Сохранить изменения</span>
                </button>
                <a href="categories.php" class="btn">Отмена</a>
            </form>
        </div>
        <?php else: ?>
        <div class="edit-card">
            <h2><span class="icon-tank">Добавить новую категорию</span></h2>
            <form method="POST">
                <div class="form-group">
                    <label>Название категории:</label>
                    <input type="text" name="category_name" 
                           placeholder="Введите название категории" required>
                </div>
                <button type="submit" name="add" class="btn btn-success">
                    <span class="icon-military">Добавить категорию</span>
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <h2><span class="icon-military">Список категорий</span> <span class="tank-badge"><?= count($categories) ?></span></h2>
        
        <!-- Статистика категорий -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего категорий</div>
                <div class="stat-number"><?= count($categories) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">С товарами</div>
                <div class="stat-number">
                    <?= count(array_filter($categories, fn($cat) => $cat['item_count'] > 0)) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Всего товаров</div>
                <div class="stat-number">
                    <?= array_sum(array_column($categories, 'item_count')) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Среднее на категорию</div>
                <div class="stat-number">
                    <?= count($categories) > 0 ? 
                        round(array_sum(array_column($categories, 'item_count')) / count($categories), 1) : 0 ?>
                </div>
            </div>
        </div>
        
        <div class="tank-search">
            <input type="text" id="categorySearch" placeholder="Поиск категории...">
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Название категории</th>
                <th>Товаров</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><span class="price">#<?= $cat['category_id'] ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($cat['category_name']) ?></strong>
                    <?php if ($cat['item_count'] == 0): ?>
                        <span class="tank-badge badge-new" style="margin-left: 10px; font-size: 0.7rem;">
                            ПУСТА
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="price"><?= $cat['item_count'] ?></span>
                        <?php if ($cat['item_count'] > 0): ?>
                        <div class="tank-progress" style="flex-grow: 1; height: 8px;">
                            <?php 
                            $maxItems = max(array_column($categories, 'item_count'));
                            $width = $maxItems > 0 ? ($cat['item_count'] / $maxItems) * 100 : 0;
                            ?>
                            <div class="progress-bar" style="width: <?= $width ?>%;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <span class="status <?= $cat['item_count'] > 0 ? 'status-active' : 'status-inactive' ?>">
                        <?= $cat['item_count'] > 0 ? 'Активна' : 'Пуста' ?>
                    </span>
                </td>
                <td>
                    <a href="?edit=<?= $cat['category_id'] ?>" class="btn btn-edit" style="margin-bottom: 5px;">
                        ✎ Редактировать
                    </a>
                    <?php if ($cat['item_count'] == 0): ?>
                        <a href="#" onclick="confirmDelete(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['category_name']) ?>')" 
                           class="btn btn-delete">🗑️ Удалить</a>
                    <?php else: ?>
                        <button class="btn" style="opacity: 0.6; cursor: not-allowed;" 
                                title="Нельзя удалить категорию с товарами">
                            🚫 Удалить
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (count($categories) == 0): ?>
        <div class="empty-gallery" style="text-align: center; padding: 40px; color: var(--camo-tan);">
            <div style="font-size: 3rem; margin-bottom: 20px;">📂</div>
            <h3>КАТЕГОРИЙ НЕТ</h3>
            <p>Добавьте первую категорию для классификации комплектующих</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">×</span>
                <h3>⚠️ ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ</h3>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить категорию:</p>
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
        Танковая база данных © 2024 | Категорий: <span style="color: var(--digital-green);"><?= count($categories) ?></span>
    </div>
    
    <script>
        let categoryToDelete = null;
        let categoryName = '';
        
        function confirmDelete(id, name) {
            categoryToDelete = id;
            categoryName = name;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            categoryToDelete = null;
            categoryName = '';
        }
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (categoryToDelete) {
                window.location.href = '?delete=' + categoryToDelete;
            }
        };
        
        // Поиск категорий
        document.getElementById('categorySearch').addEventListener('input', function(e) {
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
        
        // Анимация прогресс-баров
        setTimeout(() => {
            document.querySelectorAll('.progress-bar').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        }, 500);
    </script>
</body>
</html>