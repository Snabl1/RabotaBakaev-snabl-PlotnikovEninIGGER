<?php
require_once 'functions.php';

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Получаем статистику
$stats = [
    'clients' => $pdo->query("SELECT COUNT(*) as count FROM clients")->fetch()['count'],
    'components' => $pdo->query("SELECT COUNT(*) as count FROM components")->fetch()['count'],
    'categories' => $pdo->query("SELECT COUNT(*) as count FROM component_categories")->fetch()['count'],
    'orders' => $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
    'order_items' => $pdo->query("SELECT COUNT(*) as count FROM order_items")->fetch()['count'],
    'receipts' => $pdo->query("SELECT COUNT(*) as count FROM receipts")->fetch()['count'],
];

// Общая выручка
$revenue = $pdo->query("SELECT SUM(total_cost) as total FROM orders")->fetch()['total'] ?? 0;

// Последние заказы
$recent_orders = $pdo->query("
    SELECT o.*, c.last_name, c.first_name 
    FROM orders o 
    LEFT JOIN clients c ON o.client_id = c.client_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetchAll();

// Комплектующие с низким запасом
$low_stock = $pdo->query("
    SELECT * FROM components 
    WHERE in_stock = 0 
    ORDER BY component_id DESC 
    LIMIT 5
")->fetchAll();

// Статистика по доставке
$delivery_stats = $pdo_delivery->query("
    SELECT COUNT(*) as zones_count, 
           SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_zones,
           AVG(base_price) as avg_price
    FROM delivery_zones
")->fetch();

// Статистика по гарантиям
$warranty_stats = $pdo_warranty->query("
    SELECT COUNT(*) as warranties_count,
           AVG(warranty_period_months) as avg_months,
           COUNT(DISTINCT component_id) as unique_components
    FROM warranties
")->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Танковая База Комплектующих</title>
    <style>
        /* Стили для главного спиннера */
        .main-spinner-container {
            position: fixed;
            top: 100px;
            right: 20px;
            width: 180px;
            height: 180px;
            z-index: 999;
            cursor: pointer;
        }
        
        .main-spinner {
            width: 100%;
            height: 100%;
            position: relative;
            transition: transform 0.3s;
        }
        
        .spinner-wheel-main {
            width: 100%;
            height: 100%;
            background: conic-gradient(
                var(--danger-red) 0deg 45deg,
                var(--ammo-gold) 45deg 90deg,
                var(--digital-green) 90deg 135deg,
                var(--camo-green) 135deg 180deg,
                var(--camo-brown) 180deg 225deg,
                var(--rust) 225deg 270deg,
                var(--metal-light) 270deg 315deg,
                var(--digital-green) 315deg 360deg
            );
            border-radius: 50%;
            border: 8px solid var(--metal-dark);
            box-shadow: 
                0 0 40px rgba(0, 0, 0, 0.9),
                inset 0 0 30px rgba(0, 0, 0, 0.9),
                0 0 0 5px rgba(255, 215, 0, 0.3);
            position: relative;
            transition: all 0.5s;
        }
        
        .spinner-center-main {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            background: var(--metal-dark);
            border-radius: 50%;
            border: 4px solid var(--ammo-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--digital-green);
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.8);
            z-index: 3;
        }
        
        .spinner-notch-main {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 25px;
            height: 25px;
            background: var(--metal-light);
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            border-radius: 3px;
            z-index: 2;
        }
        
        .spinner-glow {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(255,215,0,0.3) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .main-spinner:hover .spinner-glow {
            opacity: 1;
        }
        
        .main-spinner:hover .spinner-wheel-main {
            transform: scale(1.05);
            box-shadow: 
                0 0 60px rgba(218, 165, 32, 0.5),
                inset 0 0 40px rgba(0, 0, 0, 0.9),
                0 0 0 5px rgba(255, 215, 0, 0.5);
        }
        
        .spinner-label {
            position: absolute;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            color: var(--digital-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
            animation: pulse 2s infinite;
        }
        
        .spinner-stats {
            position: absolute;
            top: -60px;
            left: 0;
            right: 0;
            text-align: center;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--camo-green);
            border-radius: 10px;
            padding: 10px;
            color: var(--digital-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .main-spinner:hover .spinner-stats {
            opacity: 1;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes spinFast {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(720deg); }
        }
        
        @keyframes spinSlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinning {
            animation: spin 0.5s linear infinite;
        }
        
        .spinning-fast {
            animation: spinFast 0.3s linear infinite;
        }
        
        .spinning-slow {
            animation: spinSlow 1s linear infinite;
        }
        
        /* Анимация выигрыша */
        @keyframes winPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .win-animation {
            animation: winPulse 0.3s 3;
        }
        
        /* Оверлей результата */
        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s;
            backdrop-filter: blur(10px);
        }
        
        .result-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
        
        .result-card {
            background: linear-gradient(145deg, #1a1a1a, #0a0a0a);
            border: 5px solid;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            transform: scale(0.8);
            transition: transform 0.5s;
            position: relative;
            overflow: hidden;
        }
        
        .result-overlay.show .result-card {
            transform: scale(1);
        }
        
        .result-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .result-prize {
            font-size: 3rem;
            margin: 20px 0;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .result-stats {
            color: var(--camo-tan);
            margin: 20px 0;
            font-size: 0.9rem;
        }
        
        /* Адаптивность */
        @media (max-width: 1200px) {
            .main-spinner-container {
                width: 150px;
                height: 150px;
            }
        }
        
        @media (max-width: 768px) {
            .main-spinner-container {
                position: relative;
                top: auto;
                right: auto;
                margin: 20px auto;
                width: 200px;
                height: 200px;
            }
            
            .spinner-label {
                position: relative;
                bottom: auto;
                margin-top: 10px;
            }
            
            .spinner-stats {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <!-- Главный фиджет-спиннер -->
    <div class="main-spinner-container" id="mainSpinnerContainer">
        <div class="spinner-stats">
            <div>СПИНОВ: <span id="spinCounter">0</span></div>
            <div>УРОВЕНЬ: <span id="levelCounter">1</span></div>
            <div>ОПЫТ: <span id="xpCounter">0/100</span></div>
        </div>
        
        <div class="main-spinner" id="mainSpinner" onclick="spinMainWheel()">
            <div class="spinner-glow"></div>
            <div class="spinner-wheel-main" id="spinnerWheelMain">
                <div class="spinner-notch-main"></div>
                <div class="spinner-center-main">SPIN</div>
                
                <!-- Метки на спиннере -->
                <div class="spinner-marker" style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); color: white; font-weight: bold;">🎮</div>
                <div class="spinner-marker" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); color: white; font-weight: bold;">🎯</div>
                <div class="spinner-marker" style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); color: white; font-weight: bold;">⚡</div>
                <div class="spinner-marker" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); color: white; font-weight: bold;">💰</div>
            </div>
        </div>
        
        <div class="spinner-label">
            КРУТИ СПИННЕР!
        </div>
    </div>
    
    <!-- Оверлей результата -->
    <div class="result-overlay" id="resultOverlay" onclick="closeResult()">
        <div class="result-card" id="resultCard">
            <h2 class="result-title" id="resultTitle">ПОБЕДА!</h2>
            <div class="result-prize" id="resultPrize">🎮</div>
            <div id="resultDescription" style="color: white; font-size: 1.2rem; margin: 20px 0;">
                Вы выиграли бонус!
            </div>
            <div class="result-stats">
                <div>Спинов сегодня: <span id="resultSpinCount">0</span></div>
                <div>Опыт получен: <span id="resultXPGained">0</span></div>
                <div>Следующий уровень: <span id="resultNextLevel">100</span> XP</div>
            </div>
            <button class="btn btn-success" onclick="closeResult()" style="margin-top: 20px; padding: 15px 30px; font-size: 1.2rem;">
                КРУТИТЬ ЕЩЁ!
            </button>
        </div>
    </div>
    
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="tank-logo">
            <h1>🚀 ТАНКОВАЯ БАЗА КОМПЛЕКТУЮЩИХ</h1>
            <div class="subtitle">СИСТЕМА УПРАВЛЕНИЯ ВОЕННЫМИ ПОСТАВКАМИ</div>
        </div>
        
        <!-- Баннер спиннера -->
        <div style="background: linear-gradient(145deg, rgba(74, 93, 35, 0.5), rgba(42, 42, 42, 0.8)); 
             padding: 20px; border-radius: 10px; border: 3px solid var(--ammo-gold); 
             margin: 20px 0; text-align: center;">
            <h2 style="color: var(--digital-green); margin-bottom: 10px;">
                🎮 НОВЫЙ ФИДЖЕТ-СПИННЕР!
            </h2>
            <p style="color: var(--camo-tan);">
                Крути спиннер справа вверху для получения случайных бонусов и достижений!
                Каждый спин дает опыт и повышает уровень.
            </p>
            <div style="display: inline-flex; gap: 20px; margin-top: 15px; flex-wrap: wrap; justify-content: center;">
                <div style="text-align: center;">
                    <div style="color: var(--digital-green); font-weight: bold;">🎯</div>
                    <div style="font-size: 0.9rem; color: var(--camo-tan);">Бонусы</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: var(--digital-green); font-weight: bold;">⚡</div>
                    <div style="font-size: 0.9rem; color: var(--camo-tan);">Опыт</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: var(--digital-green); font-weight: bold;">🏆</div>
                    <div style="font-size: 0.9rem; color: var(--camo-tan);">Достижения</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: var(--digital-green); font-weight: bold;">💰</div>
                    <div style="font-size: 0.9rem; color: var(--camo-tan);">Награды</div>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Клиенты</div>
                <div class="stat-number"><?= $stats['clients'] ?></div>
                <div class="stat-label">солдат в базе</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Комплектующие</div>
                <div class="stat-number"><?= $stats['components'] ?></div>
                <div class="stat-label">единиц в арсенале</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Заказы</div>
                <div class="stat-number"><?= $stats['orders'] ?></div>
                <div class="stat-label">боевых операций</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Выручка</div>
                <div class="stat-number"><?= number_format($revenue, 2) ?> ₽</div>
                <div class="stat-label">военный бюджет</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Зон доставки</div>
                <div class="stat-number"><?= $delivery_stats['zones_count'] ?></div>
                <div class="stat-label"><?= $delivery_stats['active_zones'] ?> активных</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Гарантий</div>
                <div class="stat-number"><?= $warranty_stats['warranties_count'] ?></div>
                <div class="stat-label">средний срок <?= round($warranty_stats['avg_months']) ?> мес.</div>
            </div>
        </div>
        
        <div class="tank-tabs">
            <div class="tank-tab active" onclick="showTab('recent')">
                Последние заказы
            </div>
            <div class="tank-tab" onclick="showTab('stock')">
                Требуют пополнения
            </div>
            <div class="tank-tab" onclick="showTab('stats')">
                Полная статистика
            </div>
            <div class="tank-tab" onclick="showTab('game')">
                🎮 Игровая статистика
            </div>
        </div>
        
        <div id="recentTab" class="tab-content">
            <h2><span class="icon-military">Последние боевые операции (заказы)</span></h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Дата</th>
                    <th>Доставка</th>
                    <th>Гарантия</th>
                    <th>Итого</th>
                </tr>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><span class="price">#<?= $order['order_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name']) ?></strong></td>
                    <td><?= $order['order_date'] ?></td>
                    <td>
                        <span class="status <?= $order['delivery_needed'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $order['delivery_needed'] ? 'Требуется' : 'Самовывоз' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($order['warranty_id']): ?>
                        <span class="tank-badge" style="background: var(--ammo-gold); color: black;">ГАРАНТИЯ</span>
                        <?php else: ?>
                        <span style="color: var(--camo-tan);">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="price"><?= number_format($order['total_cost'], 2) ?> ₽</span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div id="stockTab" class="tab-content" style="display: none;">
            <h2><span class="icon-military">Требуют пополнения боезапаса</span></h2>
            <?php if (count($low_stock) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Описание</th>
                    <th>Действия</th>
                </tr>
                <?php foreach ($low_stock as $comp): ?>
                <tr>
                    <td><span class="price">#<?= $comp['component_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($comp['component_name']) ?></strong></td>
                    <td><span class="price"><?= number_format($comp['price'], 2) ?> ₽</span></td>
                    <td><?= htmlspecialchars($comp['description'] ?? '-') ?></td>
                    <td>
                        <a href="components.php?edit=<?= $comp['component_id'] ?>" class="btn btn-edit">
                            ✎ Пополнить
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <div class="alert alert-success">
                🎉 Все комплектующие в наличии! Боезапас полный!
            </div>
            <?php endif; ?>
        </div>
        
        <div id="statsTab" class="tab-content" style="display: none;">
            <h2><span class="icon-military">Полная статистика базы</span></h2>
            <table>
                <tr>
                    <th>Таблица</th>
                    <th>Записей</th>
                    <th>База данных</th>
                    <th>Статус</th>
                </tr>
                <?php foreach ($stats as $table => $count): ?>
                <tr>
                    <td><strong><?= ucfirst($table) ?></strong></td>
                    <td><span class="price"><?= $count ?></span></td>
                    <td><span style="color: var(--digital-green);">Основная</span></td>
                    <td>
                        <span class="status <?= $count > 0 ? 'status-active' : 'status-inactive' ?>">
                            <?= $count > 0 ? 'Активна' : 'Пуста' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Delivery Zones</strong></td>
                    <td><span class="price"><?= $delivery_stats['zones_count'] ?></span></td>
                    <td><span style="color: var(--ammo-gold);">Доставка</span></td>
                    <td>
                        <span class="status status-active">
                            Удаленная БД
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Warranties</strong></td>
                    <td><span class="price"><?= $warranty_stats['warranties_count'] ?></span></td>
                    <td><span style="color: var(--danger-red);">Гарантии</span></td>
                    <td>
                        <span class="status status-active">
                            Удаленная БД
                        </span>
                    </td>
                </tr>
            </table>
            
            <div class="alert alert-warning" style="margin-top: 20px;">
                ⚡ <strong>Системное сообщение:</strong> Все 3 базы данных работают в штатном режиме. 
                Последняя проверка: <?= date('d.m.Y H:i:s') ?>
            </div>
        </div>
        
        <div id="gameTab" class="tab-content" style="display: none;">
            <h2><span class="icon-military">🎮 ИГРОВАЯ СТАТИСТИКА СПИННЕРА</span></h2>
            
            <div style="text-align: center; padding: 20px;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Всего спинов</div>
                        <div class="stat-number" id="totalSpins">0</div>
                        <div class="stat-label">сегодня</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Уровень</div>
                        <div class="stat-number" id="playerLevel">1</div>
                        <div class="stat-label">опыт</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Опыт</div>
                        <div class="stat-number" id="playerXP">0/100</div>
                        <div class="stat-label">до след. уровня</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Рекорд скорости</div>
                        <div class="stat-number" id="recordTime">0.0</div>
                        <div class="stat-label">секунд</div>
                    </div>
                </div>
                
                <!-- Достижения -->
                <div style="margin-top: 40px; background: rgba(42,42,42,0.7); padding: 20px; border-radius: 10px; border: 2px solid var(--camo-green);">
                    <h3 style="color: var(--digital-green); margin-bottom: 20px; text-align: center;">
                        🏆 ВАШИ ДОСТИЖЕНИЯ
                    </h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;" id="achievementsContainer">
                        <!-- Достижения будут загружены динамически -->
                    </div>
                </div>
                
                <!-- История спинов -->
                <div style="margin-top: 40px;">
                    <h3 style="color: var(--ammo-gold); margin-bottom: 15px;">📊 ПОСЛЕДНИЕ СПИНЫ</h3>
                    <div id="spinHistory" style="
                        max-height: 200px;
                        overflow-y: auto;
                        background: rgba(30,30,30,0.8);
                        border-radius: 8px;
                        padding: 15px;
                    ">
                        <!-- История будет загружена динамически -->
                        <div style="text-align: center; color: var(--camo-tan); padding: 20px;">
                            Спинов пока не было. Крутите спиннер!
                        </div>
                    </div>
                </div>
                
                <!-- Кнопка быстрого спина -->
                <button onclick="spinMainWheel()" class="btn btn-success" 
                        style="font-size: 1.5rem; padding: 20px 50px; margin: 30px 0;">
                    🎮 КРУТИТЬ СПИННЕР СЕЙЧАС!
                </button>
            </div>
        </div>
        
        <div class="tank-progress" style="margin: 30px 0;">
            <div class="progress-bar" style="width: <?= min(100, ($stats['components'] / 100) * 100) ?>%;"></div>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="orders.php" class="btn btn-success" style="font-size: 1.2rem; padding: 15px 30px;">
                🚀 НАЧАТЬ НОВУЮ ОПЕРАЦИЮ (СОЗДАТЬ ЗАКАЗ)
            </a>
        </div>
    </div>
    
    <?php if (!empty($clientRole) && $clientRole === 'admin'): ?>
    <div class="admin-test-block" style="margin: 24px auto; text-align: center;">
        <a href="report.php" class="btn" style="font-size: 1rem; padding: 12px 24px;">📊 Результаты тестирования</a>
        <a href="backups.php" class="btn" style="margin-left: 12px; font-size: 1rem; padding: 12px 24px;">💾 Просмотр бэкапов</a>
    </div>
    <?php endif; ?>

    <style>.secret-casino-link{opacity:0.2;color:inherit;text-decoration:none;}.secret-casino-link:hover{opacity:0.7;color:var(--ammo-gold);}</style>
    <div class="tank-footer" id="secretFooter" title="Тройной клик откроет 1TANK">
        Танковая база данных <a href="order.php" class="icon-military">©</a> 2024
        <a href="casino.php" class="secret-casino-link" title="1TANK">·</a> |
        Статус: <span style="color: var(--digital-green);">● ОПЕРАТИВНЫЙ</span> | 
        Игровой режим: <span style="color: var(--ammo-gold);" id="gameModeStatus">АКТИВЕН</span> |
        Спинов сегодня: <span id="footerSpinCount" style="color: var(--digital-green);">0</span>
    </div>
    
    <script>
        // ===== ПЕРЕМЕННЫЕ ИГРЫ =====
        let spinCount = parseInt(localStorage.getItem('tankMainSpinCount')) || 0;
        let totalSpins = parseInt(localStorage.getItem('tankMainTotalSpins')) || 0;
        let playerLevel = parseInt(localStorage.getItem('tankMainPlayerLevel')) || 1;
        let playerXP = parseInt(localStorage.getItem('tankMainPlayerXP')) || 0;
        let spinRecord = parseFloat(localStorage.getItem('tankMainSpinRecord')) || 0;
        let spinHistory = JSON.parse(localStorage.getItem('tankMainSpinHistory')) || [];
        let achievements = JSON.parse(localStorage.getItem('tankMainAchievements')) || [
            {id: 'first_spin', name: 'Первый спин', earned: false, icon: '🎯'},
            {id: 'fast_spin', name: 'Спин за 0.5с', earned: false, icon: '⚡'},
            {id: 'spinner_10', name: '10 спинов', earned: false, icon: '🔥'},
            {id: 'spinner_50', name: '50 спинов', earned: false, icon: '🏅'},
            {id: 'level_5', name: '5 уровень', earned: false, icon: '⭐'},
            {id: 'level_10', name: '10 уровень', earned: false, icon: '👑'},
            {id: 'perfect_spin', name: 'Идеальный спин', earned: false, icon: '🎮'},
            {id: 'daily_spinner', name: 'Ежедневный спиннер', earned: false, icon: '📅'}
        ];
        
        // Призы для спиннера
        const prizes = [
            {icon: '🎮', name: 'ИГРОВОЙ БОНУС', xp: 25, color: '#ff0000', message: 'Бонус к игровым возможностям!'},
            {icon: '⚡', name: 'ЭНЕРГИЯ', xp: 20, color: '#ff8800', message: 'Прилив энергии для новых свершений!'},
            {icon: '💰', name: 'СОКРОВИЩЕ', xp: 15, color: '#ffff00', message: 'Найдено ценное сокровище!'},
            {icon: '🛡️', name: 'ЗАЩИТА', xp: 12, color: '#00ff00', message: 'Защита от неприятностей!'},
            {icon: '🔧', name: 'ИНСТРУМЕНТ', xp: 10, color: '#0088ff', message: 'Новый полезный инструмент!'},
            {icon: '🎯', name: 'ТОЧНОСТЬ', xp: 8, color: '#0000ff', message: 'Повышение точности действий!'},
            {icon: '🚀', name: 'УСКОРЕНИЕ', xp: 6, color: '#8800ff', message: 'Время ускорить процессы!'},
            {icon: '💡', name: 'ИДЕЯ', xp: 5, color: '#ff00ff', message: 'Блестящая новая идея!'}
        ];
        
        // ===== ОБНОВЛЕНИЕ СТАТИСТИКИ =====
        function updateGameStats() {
            // Обновляем счетчики на странице
            document.getElementById('spinCounter').textContent = spinCount;
            document.getElementById('levelCounter').textContent = playerLevel;
            document.getElementById('xpCounter').textContent = playerXP + "/" + (playerLevel * 100);
            
            document.getElementById('totalSpins').textContent = totalSpins;
            document.getElementById('playerLevel').textContent = playerLevel;
            document.getElementById('playerXP').textContent = playerXP + "/" + (playerLevel * 100);
            document.getElementById('recordTime').textContent = spinRecord.toFixed(1);
            document.getElementById('footerSpinCount').textContent = spinCount;
            
            // Обновляем достижения
            updateAchievements();
            
            // Обновляем историю
            updateSpinHistory();
            
            // Сохраняем в localStorage
            localStorage.setItem('tankMainSpinCount', spinCount);
            localStorage.setItem('tankMainTotalSpins', totalSpins);
            localStorage.setItem('tankMainPlayerLevel', playerLevel);
            localStorage.setItem('tankMainPlayerXP', playerXP);
            localStorage.setItem('tankMainSpinRecord', spinRecord);
            localStorage.setItem('tankMainSpinHistory', JSON.stringify(spinHistory));
            localStorage.setItem('tankMainAchievements', JSON.stringify(achievements));
        }
        
        // ===== ВРАЩЕНИЕ СПИННЕРА =====
        function spinMainWheel() {
            const spinner = document.getElementById('spinnerWheelMain');
            const spinnerContainer = document.getElementById('mainSpinner');
            
            // Отключаем кнопку на время вращения
            spinnerContainer.style.pointerEvents = 'none';
            
            // Засекаем время
            const startTime = Date.now();
            
            // Случайное количество вращений
            const spins = 3 + Math.floor(Math.random() * 4);
            const degrees = spins * 360 + Math.floor(Math.random() * 360);
            
            // Время вращения
            const spinTime = 2 + Math.random();
            
            // Анимация вращения
            spinner.style.transition = `transform ${spinTime}s cubic-bezier(0.1, 0.8, 0.2, 1)`;
            spinner.style.transform = `rotate(${degrees}deg)`;
            
            // Добавляем класс для анимации
            spinner.classList.add('spinning-fast');
            
            // Определяем приз
            setTimeout(() => {
                const normalizedDegrees = degrees % 360;
                const prizeIndex = Math.floor((360 - normalizedDegrees) / 45) % 8;
                const prize = prizes[prizeIndex];
                
                // Вычисляем время вращения
                const endTime = Date.now();
                const spinDuration = (endTime - startTime) / 1000;
                
                // Обновляем статистику
                spinCount++;
                totalSpins++;
                
                // Проверяем рекорд скорости
                if (spinRecord === 0 || spinDuration < spinRecord) {
                    spinRecord = spinDuration;
                    checkAchievement('fast_spin');
                }
                
                // Добавляем опыт
                playerXP += prize.xp;
                
                // Проверяем уровень
                const xpNeeded = playerLevel * 100;
                let levelUp = false;
                
                if (playerXP >= xpNeeded) {
                    playerLevel++;
                    playerXP = playerXP - xpNeeded;
                    levelUp = true;
                    
                    // Проверяем достижения уровней
                    if (playerLevel >= 5) checkAchievement('level_5');
                    if (playerLevel >= 10) checkAchievement('level_10');
                }
                
                // Сохраняем в историю
                const spinData = {
                    id: Date.now(),
                    prize: prize.name,
                    icon: prize.icon,
                    xp: prize.xp,
                    time: new Date().toLocaleTimeString('ru-RU'),
                    duration: spinDuration.toFixed(2)
                };
                
                spinHistory.unshift(spinData);
                if (spinHistory.length > 10) spinHistory = spinHistory.slice(0, 10);
                
                // Проверяем достижения
                checkAchievement('first_spin');
                if (totalSpins >= 10) checkAchievement('spinner_10');
                if (totalSpins >= 50) checkAchievement('spinner_50');
                
                // Показываем результат
                showResult(prize, spinDuration, levelUp);
                
                // Включаем кнопку
                setTimeout(() => {
                    spinnerContainer.style.pointerEvents = 'auto';
                    spinner.classList.remove('spinning-fast');
                }, 1000);
                
                // Обновляем статистику
                updateGameStats();
                
            }, spinTime * 1000);
        }
        
        // ===== ПОКАЗ РЕЗУЛЬТАТА =====
        function showResult(prize, duration, levelUp = false) {
            const overlay = document.getElementById('resultOverlay');
            const resultCard = document.getElementById('resultCard');
            
            // Настраиваем цвета и контент
            resultCard.style.borderColor = prize.color;
            document.getElementById('resultTitle').textContent = levelUp ? 'УРОВЕНЬ ПОВЫШЕН!' : 'ВЫ ВЫИГРАЛИ!';
            document.getElementById('resultTitle').style.color = prize.color;
            document.getElementById('resultPrize').textContent = prize.icon;
            document.getElementById('resultPrize').style.color = prize.color;
            document.getElementById('resultDescription').textContent = prize.message;
            document.getElementById('resultDescription').style.color = prize.color;
            
            document.getElementById('resultSpinCount').textContent = spinCount;
            document.getElementById('resultXPGained').textContent = prize.xp;
            document.getElementById('resultNextLevel').textContent = playerLevel * 100;
            
            // Анимация появления
            overlay.classList.add('show');
            
            // Звуковой эффект (если нужен)
            playSpinSound();
            
            // Эффект победы
            resultCard.classList.add('win-animation');
            setTimeout(() => {
                resultCard.classList.remove('win-animation');
            }, 1000);
        }
        
        function closeResult() {
            const overlay = document.getElementById('resultOverlay');
            overlay.classList.remove('show');
        }
        
        // ===== ДОСТИЖЕНИЯ =====
        function checkAchievement(achievementId) {
            const achievement = achievements.find(a => a.id === achievementId);
            if (achievement && !achievement.earned) {
                achievement.earned = true;
                
                // Показываем уведомление о достижении
                showAchievementNotification(achievement);
                
                return true;
            }
            return false;
        }
        
        function showAchievementNotification(achievement) {
            // Создаем уведомление
            const notification = document.createElement('div');
            notification.className = 'tank-notification show';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(145deg, rgba(218,165,32,0.9), rgba(139,69,19,0.9));
                color: black;
                padding: 15px 20px;
                border-radius: 8px;
                border: 3px solid var(--ammo-gold);
                z-index: 10000;
                animation: slideIn 0.5s;
                font-family: 'Orbitron', sans-serif;
                font-weight: bold;
                max-width: 300px;
                box-shadow: 0 5px 20px rgba(218,165,32,0.5);
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-size: 2rem;">${achievement.icon}</div>
                    <div>
                        <div style="font-size: 1.2rem;">🏆 ДОСТИЖЕНИЕ!</div>
                        <div style="font-size: 0.9rem;">${achievement.name}</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Автоматическое скрытие
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
        
        function updateAchievements() {
            const container = document.getElementById('achievementsContainer');
            container.innerHTML = '';
            
            achievements.forEach(achievement => {
                const achDiv = document.createElement('div');
                achDiv.className = 'achievement';
                achDiv.style.cssText = `
                    padding: 10px 15px;
                    border-radius: 20px;
                    font-weight: bold;
                    font-size: 0.9rem;
                    text-align: center;
                    min-width: 120px;
                    transition: all 0.3s;
                    cursor: pointer;
                `;
                
                if (achievement.earned) {
                    achDiv.style.background = 'linear-gradient(145deg, var(--ammo-gold), var(--rust))';
                    achDiv.style.color = 'black';
                    achDiv.style.boxShadow = '0 5px 15px rgba(218,165,32,0.3)';
                } else {
                    achDiv.style.background = 'rgba(42,42,42,0.7)';
                    achDiv.style.color = 'var(--camo-tan)';
                    achDiv.style.opacity = '0.5';
                }
                
                achDiv.innerHTML = `
                    <div style="font-size: 1.5rem;">${achievement.icon}</div>
                    <div>${achievement.name}</div>
                `;
                
                achDiv.title = achievement.earned ? 'Получено!' : 'Еще не получено';
                container.appendChild(achDiv);
            });
        }
        
        // ===== ИСТОРИЯ СПИНОВ =====
        function updateSpinHistory() {
            const container = document.getElementById('spinHistory');
            
            if (spinHistory.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--camo-tan); padding: 20px;">
                        Спинов пока не было. Крутите спиннер!
                    </div>
                `;
                return;
            }
            
            let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
            
            spinHistory.forEach(spin => {
                html += `
                    <div style="display: flex; justify-content: space-between; align-items: center; 
                         padding: 10px; background: rgba(0,0,0,0.3); border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="font-size: 1.5rem;">${spin.icon}</div>
                            <div>
                                <div style="color: white; font-weight: bold;">${spin.prize}</div>
                                <div style="color: var(--camo-tan); font-size: 0.8rem;">
                                    ${spin.time} • ${spin.duration} сек
                                </div>
                            </div>
                        </div>
                        <div style="color: var(--digital-green); font-weight: bold;">
                            +${spin.xp} XP
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        // ===== ЗВУКИ =====
        function playSpinSound() {
            // Создаем звуковой элемент
            const audio = new Audio();
            audio.src = 'https://assets.mixkit.co/sfx/preview/mixkit-slot-machine-wheel-spin-1387.mp3';
            audio.volume = 0.3;
            
            try {
                audio.play();
            } catch (e) {
                console.log("Звук не воспроизводится:", e);
            }
        }
        
        // ===== УПРАВЛЕНИЕ ВКЛАДКАМИ =====
        function showTab(tabName) {
            // Скрыть все табы
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Убрать активный класс у всех вкладок
            document.querySelectorAll('.tank-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Показать выбранный таб
            document.getElementById(tabName + 'Tab').style.display = 'block';
            
            // Сделать активной выбранную вкладку
            event.target.classList.add('active');
            
            // Если выбрана игровая вкладка, обновляем статистику
            if (tabName === 'game') {
                updateGameStats();
            }
        }
        
        // ===== ИНИЦИАЛИЗАЦИЯ =====
        document.addEventListener('DOMContentLoaded', function() {
            // Включить сканирующую линию
            document.querySelector('.scanline').style.display = 'block';
            
            // Обновить статистику
            updateGameStats();
            
            // Анимация прогресс-бара
            setTimeout(() => {
                const progressBar = document.querySelector('.progress-bar');
                let width = parseInt(progressBar.style.width) || 0;
                let targetWidth = <?= min(100, ($stats['components'] / 100) * 100) ?>;
                
                let interval = setInterval(() => {
                    if (width >= targetWidth) {
                        clearInterval(interval);
                        return;
                    }
                    width++;
                    progressBar.style.width = width + '%';
                }, 20);
            }, 1000);
            
            // Анимация спиннера при наведении
            const mainSpinner = document.getElementById('mainSpinner');
            mainSpinner.addEventListener('mouseenter', () => {
                document.querySelector('.spinner-wheel-main').style.transform = 'scale(1.05)';
            });
            
            mainSpinner.addEventListener('mouseleave', () => {
                document.querySelector('.spinner-wheel-main').style.transform = 'scale(1)';
            });
            
            // Проверяем ежедневное достижение
            checkDailyAchievement();
        });
        
        // Проверка ежедневного достижения
        function checkDailyAchievement() {
            const today = new Date().toDateString();
            const lastSpinDate = localStorage.getItem('tankMainLastSpinDate');
            
            if (lastSpinDate === today) {
                // Уже крутили сегодня
                const dailyAchievement = achievements.find(a => a.id === 'daily_spinner');
                if (dailyAchievement && !dailyAchievement.earned) {
                    dailyAchievement.earned = true;
                    updateGameStats();
                }
            } else {
                // Первый спин сегодня
                localStorage.setItem('tankMainLastSpinDate', today);
            }
        }
        
        // Закрытие оверлея по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeResult();
            }
        });
        
        // Стили для анимаций
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .achievement:hover {
                transform: translateY(-3px) scale(1.05);
            }
        `;
        document.head.appendChild(style);

        // Секрет: тройной клик по футеру открывает 1TANK
        (function() {
            var footer = document.getElementById('secretFooter');
            if (!footer) return;
            var clicks = 0, timer = null;
            footer.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' && e.target.href) return;
                clicks++;
                if (timer) clearTimeout(timer);
                if (clicks >= 3) {
                    clicks = 0;
                    window.open('casino.php', '_blank');
                }
                timer = setTimeout(function() { clicks = 0; }, 800);
            });
        })();
    </script>
</body>
</html>