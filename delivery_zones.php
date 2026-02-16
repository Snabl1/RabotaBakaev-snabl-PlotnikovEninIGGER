<?php
require_once 'functions.php';

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Получение зон доставки
$zones = getDeliveryZones();

// Добавление/редактирование зоны
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add'])) {
            $stmt = $pdo_delivery->prepare("
                INSERT INTO delivery_zones 
                (zone_name, base_price, price_per_km, min_distance, max_distance, delivery_time_hours, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['zone_name'],
                $_POST['base_price'],
                $_POST['price_per_km'],
                $_POST['min_distance'],
                $_POST['max_distance'] ?: null,
                $_POST['delivery_time_hours'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Зона доставки добавлена!'];
            header("Location: delivery_zones.php");
            exit;
        }
        
        if (isset($_POST['edit'])) {
            $stmt = $pdo_delivery->prepare("
                UPDATE delivery_zones SET 
                zone_name = ?, base_price = ?, price_per_km = ?, min_distance = ?, 
                max_distance = ?, delivery_time_hours = ?, is_active = ? 
                WHERE zone_id = ?
            ");
            $stmt->execute([
                $_POST['zone_name'],
                $_POST['base_price'],
                $_POST['price_per_km'],
                $_POST['min_distance'],
                $_POST['max_distance'] ?: null,
                $_POST['delivery_time_hours'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['zone_id']
            ]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Зона доставки обновлена!'];
            header("Location: delivery_zones.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()];
    }
}

// Удаление зоны
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo_delivery->prepare("DELETE FROM delivery_zones WHERE zone_id = ?");
        $stmt->execute([$_GET['delete']]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Зона доставки удалена!'];
        header("Location: delivery_zones.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Зоны доставки - Танковая База</title>
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
                <a href="components.php" class="icon-military">Комплектующие</a>
                
                <?php if ($clientRole === 'admin'): ?>
                    <a href="categories.php" class="icon-ammo">Категории</a>
                    <a href="delivery_zones.php" class="active icon-tank">Доставка</a>
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
            <h1>🗺️ ЗОНЫ ДОСТАВКИ</h1>
            <div class="subtitle">УПРАВЛЕНИЕ ТАРИФАМИ НА ДОСТАВКУ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Форма добавления/редактирования -->
        <div class="edit-card">
            <h2><span class="icon-tank"><?= isset($_GET['edit']) ? 'Редактирование' : 'Добавление' ?> зоны доставки</span></h2>
            <form method="POST">
                <?php if (isset($_GET['edit'])): 
                    $stmt = $pdo_delivery->prepare("SELECT * FROM delivery_zones WHERE zone_id = ?");
                    $stmt->execute([$_GET['edit']]);
                    $zone = $stmt->fetch();
                ?>
                    <input type="hidden" name="zone_id" value="<?= $zone['zone_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Название зоны:</label>
                    <input type="text" name="zone_name" 
                           value="<?= isset($zone) ? htmlspecialchars($zone['zone_name']) : '' ?>" 
                           placeholder="Например: Центр города" required>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label>Базовая стоимость (₽):</label>
                        <input type="number" step="0.01" name="base_price" 
                               value="<?= isset($zone) ? $zone['base_price'] : '0' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Цена за км (₽):</label>
                        <input type="number" step="0.01" name="price_per_km" 
                               value="<?= isset($zone) ? $zone['price_per_km'] : '0' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Мин. расстояние (км):</label>
                        <input type="number" name="min_distance" 
                               value="<?= isset($zone) ? $zone['min_distance'] : '0' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Макс. расстояние (км):</label>
                        <input type="number" name="max_distance" 
                               value="<?= isset($zone) ? $zone['max_distance'] : '' ?>" 
                               placeholder="Оставьте пустым для неограниченного">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Срок доставки (часов):</label>
                    <input type="number" name="delivery_time_hours" 
                           value="<?= isset($zone) ? $zone['delivery_time_hours'] : '24' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" 
                               <?= isset($zone) && $zone['is_active'] ? 'checked' : 'checked' ?>>
                        Активна
                    </label>
                </div>
                
                <button type="submit" name="<?= isset($_GET['edit']) ? 'edit' : 'add' ?>" class="btn btn-success">
                    <?= isset($_GET['edit']) ? 'Сохранить изменения' : 'Добавить зону' ?>
                </button>
                
                <?php if (isset($_GET['edit'])): ?>
                <a href="delivery_zones.php" class="btn">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Список зон доставки -->
        <h2><span class="icon-military">Список зон доставки</span> <span class="tank-badge"><?= count($zones) ?></span></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего зон</div>
                <div class="stat-number"><?= count($zones) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Средняя стоимость</div>
                <div class="stat-number">
                    <?php 
                    $avg_price = count($zones) > 0 ? 
                        array_sum(array_column($zones, 'base_price')) / count($zones) : 0;
                    echo number_format($avg_price, 2);
                    ?> ₽
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Макс. расстояние</div>
                <div class="stat-number">
                    <?php 
                    $max_dist = max(array_column($zones, 'max_distance'));
                    echo $max_dist ?: '∞';
                    ?> км
                </div>
            </div>
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Название зоны</th>
                <th>Тариф</th>
                <th>Дистанция</th>
                <th>Срок</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($zones as $zone): ?>
            <tr>
                <td><span class="price">#<?= $zone['zone_id'] ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($zone['zone_name']) ?></strong>
                    <?php if (!$zone['is_active']): ?>
                    <span class="tank-badge" style="background: var(--rust);">НЕАКТИВНА</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="price"><?= number_format($zone['base_price'], 2) ?> ₽</span>
                    <?php if ($zone['price_per_km'] > 0): ?>
                    <br><small>+ <?= number_format($zone['price_per_km'], 2) ?> ₽/км</small>
                    <?php endif; ?>
                </td>
                <td>
                    от <?= $zone['min_distance'] ?> км
                    <?php if ($zone['max_distance']): ?>
                    до <?= $zone['max_distance'] ?> км
                    <?php else: ?>
                    и далее
                    <?php endif; ?>
                </td>
                <td><?= $zone['delivery_time_hours'] ?> ч.</td>
                <td>
                    <span class="status <?= $zone['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $zone['is_active'] ? 'Активна' : 'Неактивна' ?>
                    </span>
                </td>
                <td>
                    <a href="?edit=<?= $zone['zone_id'] ?>" class="btn btn-edit" style="margin-bottom: 5px;">
                        ✎ Редактировать
                    </a>
                    <a href="#" onclick="confirmDelete(<?= $zone['zone_id'] ?>, '<?= htmlspecialchars($zone['zone_name']) ?>')" 
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
                <p>Вы уверены, что хотите удалить зону доставки:</p>
                <p id="deleteItemName" style="color: var(--ammo-gold); font-weight: bold; font-size: 1.2rem;"></p>
                <p><strong>Это действие нельзя отменить!</strong></p>
                <p style="color: var(--danger-red); font-size: 0.9rem;">
                    ⚠️ Если эта зона используется в заказах, они будут переведены в "Самовывоз"
                </p>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn">Отмена</button>
                <button id="confirmDeleteBtn" class="btn btn-delete">УДАЛИТЬ</button>
            </div>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Удаленная БД: Доставка | 
        Сервер: delivery.tankbase.ru
    </div>
    
    <script>
        let itemToDelete = null;
        
        function confirmDelete(id, name) {
            itemToDelete = id;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            itemToDelete = null;
        }
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (itemToDelete) {
                window.location.href = '?delete=' + itemToDelete;
            }
        };
        
        // Включить сканирующую линию
        document.querySelector('.scanline').style.display = 'block';
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>