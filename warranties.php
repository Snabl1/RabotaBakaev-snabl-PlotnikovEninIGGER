<?php
require_once 'functions.php';
requireAdmin();

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Получение компонентов для привязки гарантий
$components = $pdo->query("SELECT * FROM components")->fetchAll();

// Получение гарантий
$warranties = getWarranties();

// Добавление/редактирование гарантии
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add'])) {
            $stmt = $pdo_warranty->prepare("
                INSERT INTO warranties 
                (component_id, warranty_period_months, warranty_type, description, 
                 price_multiplier, coverage, exclusions) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['component_id'] ?: null,
                $_POST['warranty_period_months'],
                $_POST['warranty_type'],
                $_POST['description'],
                $_POST['price_multiplier'],
                $_POST['coverage'],
                $_POST['exclusions']
            ]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Гарантия добавлена!'];
            header("Location: warranties.php");
            exit;
        }
        
        if (isset($_POST['edit'])) {
            $stmt = $pdo_warranty->prepare("
                UPDATE warranties SET 
                component_id = ?, warranty_period_months = ?, warranty_type = ?, description = ?, 
                price_multiplier = ?, coverage = ?, exclusions = ? 
                WHERE warranty_id = ?
            ");
            $stmt->execute([
                $_POST['component_id'] ?: null,
                $_POST['warranty_period_months'],
                $_POST['warranty_type'],
                $_POST['description'],
                $_POST['price_multiplier'],
                $_POST['coverage'],
                $_POST['exclusions'],
                $_POST['warranty_id']
            ]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Гарантия обновлена!'];
            header("Location: warranties.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()];
    }
}

// Удаление гарантии
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo_warranty->prepare("DELETE FROM warranties WHERE warranty_id = ?");
        $stmt->execute([$_GET['delete']]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Гарантия удалена!'];
        header("Location: warranties.php");
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
    <title>💝 Гарантии - ANIME WORLD</title>
    <link rel="stylesheet" href="style-anime-world.css">
    <style>
        .warranty-card {
            background: linear-gradient(145deg, rgba(42, 42, 42, 0.9), rgba(30, 30, 30, 0.9));
            border: 2px solid;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s;
        }
        
        .warranty-standard { border-color: var(--camo-green); }
        .warranty-extended { border-color: var(--ammo-gold); }
        .warranty-premium { border-color: var(--danger-red); }
        
        .coverage-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        
        .coverage-item {
            background: rgba(0,0,0,0.3);
            padding: 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .coverage-good { color: var(--digital-green); }
        .coverage-bad { color: var(--danger-red); }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="tank-logo">
            <h1>🛡️ ГАРАНТИЙНЫЕ УСЛОВИЯ</h1>
            <div class="subtitle">УПРАВЛЕНИЕ ГАРАНТИЯМИ НА КОМПЛЕКТУЮЩИЕ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Форма добавления/редактирования -->
        <div class="edit-card">
            <h2><span class="icon-tank"><?= isset($_GET['edit']) ? 'Редактирование' : 'Добавление' ?> гарантии</span></h2>
            <form method="POST">
                <?php if (isset($_GET['edit'])): 
                    $stmt = $pdo_warranty->prepare("SELECT * FROM warranties WHERE warranty_id = ?");
                    $stmt->execute([$_GET['edit']]);
                    $warranty = $stmt->fetch();
                ?>
                    <input type="hidden" name="warranty_id" value="<?= $warranty['warranty_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Привязка к компоненту:</label>
                    <select name="component_id">
                        <option value="">На всю сборку (общая гарантия)</option>
                        <?php foreach ($components as $comp): ?>
                        <option value="<?= $comp['component_id'] ?>"
                            <?= (isset($warranty) && $warranty['component_id'] == $comp['component_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($comp['component_name']) ?> (<?= number_format($comp['price'], 2) ?> ₽)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--camo-tan);">
                        Если оставить пустым - гарантия будет применяться ко всей сборке
                    </small>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label>Тип гарантии:</label>
                        <select name="warranty_type" required>
                            <option value="standard" <?= (isset($warranty) && $warranty['warranty_type'] == 'standard') ? 'selected' : '' ?>>Standard (базовая)</option>
                            <option value="extended" <?= (isset($warranty) && $warranty['warranty_type'] == 'extended') ? 'selected' : '' ?>>Extended (расширенная)</option>
                            <option value="premium" <?= (isset($warranty) && $warranty['warranty_type'] == 'premium') ? 'selected' : '' ?>>Premium (премиум)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Срок (месяцев):</label>
                        <input type="number" name="warranty_period_months" min="1" max="120" 
                               value="<?= isset($warranty) ? $warranty['warranty_period_months'] : '12' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Множитель цены:</label>
                        <input type="number" step="0.01" min="1.0" max="2.0" name="price_multiplier" 
                               value="<?= isset($warranty) ? $warranty['price_multiplier'] : '1.00' ?>" required>
                        <small style="color: var(--camo-tan);">1.10 = +10% к стоимости</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" rows="3" placeholder="Краткое описание гарантии"><?= isset($warranty) ? htmlspecialchars($warranty['description']) : '' ?></textarea>
                </div>
                
                <div class="coverage-list">
                    <div style="grid-column: 1 / -1;">
                        <label>Что покрывает гарантия:</label>
                        <textarea name="coverage" rows="4" placeholder="Перечислите что покрывается гарантией (каждая строка - отдельный пункт)"><?= isset($warranty) ? htmlspecialchars($warranty['coverage']) : '' ?></textarea>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Что НЕ покрывает гарантия:</label>
                        <textarea name="exclusions" rows="4" placeholder="Перечислите что НЕ покрывается гарантией"><?= isset($warranty) ? htmlspecialchars($warranty['exclusions']) : '' ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="<?= isset($_GET['edit']) ? 'edit' : 'add' ?>" class="btn btn-success">
                    <?= isset($_GET['edit']) ? 'Сохранить изменения' : 'Добавить гарантию' ?>
                </button>
                
                <?php if (isset($_GET['edit'])): ?>
                <a href="warranties.php" class="btn">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Список гарантий -->
        <h2><span class="icon-military">Список гарантий</span> <span class="tank-badge"><?= count($warranties) ?></span></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Всего гарантий</div>
                <div class="stat-number"><?= count($warranties) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">На сборки</div>
                <div class="stat-number">
                    <?= count(array_filter($warranties, fn($w) => $w['component_id'] === null)) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">На компоненты</div>
                <div class="stat-number">
                    <?= count(array_filter($warranties, fn($w) => $w['component_id'] !== null)) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Средний срок</div>
                <div class="stat-number">
                    <?php 
                    $avg_months = count($warranties) > 0 ? 
                        array_sum(array_column($warranties, 'warranty_period_months')) / count($warranties) : 0;
                    echo round($avg_months, 1);
                    ?> мес.
                </div>
            </div>
        </div>
        
        <?php foreach ($warranties as $warranty): 
            $type_class = 'warranty-' . $warranty['warranty_type'];
            $percentage = ($warranty['price_multiplier'] - 1) * 100;
        ?>
        <div class="warranty-card <?= $type_class ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h3 style="color: var(--digital-green); margin: 0;">
                        <?= strtoupper($warranty['warranty_type']) ?> ГАРАНТИЯ
                        <span class="tank-badge" style="background: var(--camo-green);">
                            <?= $warranty['warranty_period_months'] ?> мес.
                        </span>
                        <span class="tank-badge" style="background: var(--ammo-gold); color: black;">
                            +<?= number_format($percentage, 0) ?>%
                        </span>
                    </h3>
                    <div style="color: var(--camo-tan); margin-top: 5px;">
                        <?php if ($warranty['component_id']): ?>
                        Для конкретного компонента (ID: <?= $warranty['component_id'] ?>)
                        <?php else: ?>
                        Для всей сборки
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <a href="?edit=<?= $warranty['warranty_id'] ?>" class="btn btn-edit">✎ Редактировать</a>
                    <a href="#" onclick="confirmDelete(<?= $warranty['warranty_id'] ?>, 'Гарантия <?= $warranty['warranty_type'] ?>')" 
                       class="btn btn-delete">🗑️ Удалить</a>
                </div>
            </div>
            
            <?php if ($warranty['description']): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= nl2br(htmlspecialchars($warranty['description'])) ?>
            </div>
            <?php endif; ?>
            
            <div class="coverage-list">
                <?php if ($warranty['coverage']): 
                    $coverage_items = explode("\n", $warranty['coverage']);
                ?>
                <div>
                    <strong style="color: var(--digital-green);">✅ ПОКРЫВАЕТ:</strong>
                    <?php foreach ($coverage_items as $item): 
                        if (trim($item)): ?>
                    <div class="coverage-item coverage-good">• <?= htmlspecialchars(trim($item)) ?></div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($warranty['exclusions']): 
                    $exclusion_items = explode("\n", $warranty['exclusions']);
                ?>
                <div>
                    <strong style="color: var(--danger-red);">❌ НЕ ПОКРЫВАЕТ:</strong>
                    <?php foreach ($exclusion_items as $item): 
                        if (trim($item)): ?>
                    <div class="coverage-item coverage-bad">• <?= htmlspecialchars(trim($item)) ?></div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">×</span>
                <h3>⚠️ ПОДТВЕРЖДЕНИЕ УДАЛЕНИЯ</h3>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить эту гарантию?</p>
                <p id="deleteItemName" style="color: var(--ammo-gold); font-weight: bold; font-size: 1.2rem;"></p>
                <p><strong>Это действие нельзя отменить!</strong></p>
                <p style="color: var(--danger-red); font-size: 0.9rem;">
                    ⚠️ Если эта гарантия используется в заказах, они останутся без гарантии
                </p>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn">Отмена</button>
                <button id="confirmDeleteBtn" class="btn btn-delete">УДАЛИТЬ</button>
            </div>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Удаленная БД: Гарантии | 
        Сервер: warranty.tankbase.ru
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
        
        // Анимация карточек гарантий
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.warranty-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                    this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.4)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
<!-- CYBERPUNK EFFECTS SCRIPT -->
    <script src="anime-effects.js"></script>
<script src="anime-effects.js"></script></body>
</html>