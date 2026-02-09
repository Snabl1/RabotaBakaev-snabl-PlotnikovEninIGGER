<?php
require_once 'functions.php';

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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Танковая База Комплектующих</title>
</head>
<body>
    <div class="scanline"></div>
    
    <!-- Танковый спиннер -->
    <div class="tank-spinner" onclick="openSpinnerGame()">
        <div class="spinner-wheel" id="mainSpinner">
            <div class="spinner-notch"></div>
            <div class="spinner-center">Spin</div>
            <div class="spinner-base"></div>
        </div>
    </div>
    
    <!-- Игра-спиннер -->
    <div id="spinnerGame" class="spinner-game">
        <div class="close-game" onclick="closeSpinnerGame()">×</div>
        <div class="game-container">
            <h1 style="color: var(--ammo-gold); margin-bottom: 30px;">🎡 ТАНКОВАЯ РУЛЕТКА 🎡</h1>
            
            <div class="game-wheel">
                <div class="wheel-pointer"></div>
                <div class="wheel-inner" id="gameWheel"></div>
                <div class="wheel-numbers" id="wheelNumbers"></div>
            </div>
            
            <div class="game-result" id="gameResult">
                Нажми SPIN чтобы крутануть!
            </div>
            
            <button class="spin-button" onclick="spinWheel()">🚀 SPIN! 🚀</button>
            
            <div style="color: var(--camo-tan); margin-top: 20px;">
                <p>Крути рулетку и получи случайный бонус!</p>
                <p id="stats" style="font-size: 0.9rem; color: var(--digital-green);">
                    Спинов сегодня: <span id="spinCount">0</span> | 
                    Рекорд: <span id="spinRecord">0</span> сек
                </p>
            </div>
        </div>
    </div>
    
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php" class="active icon-tank">Главная</a>
            <a href="clients.php" class="icon-military">Клиенты</a>
            <a href="categories.php" class="icon-ammo">Категории</a>
            <a href="components.php" class="icon-tank">Комплектующие</a>
            <a href="orders.php" class="icon-military">Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="tank-logo">
            <h1>🚀 ТАНКОВАЯ БАЗА КОМПЛЕКТУЮЩИХ</h1>
            <div class="subtitle">СИСТЕМА УПРАВЛЕНИЯ ВОЕННЫМИ ПОСТАВКАМИ</div>
        </div>
        
        <div style="text-align: center; margin: 20px 0; color: var(--camo-tan);">
            <p>🎮 <strong>Новая фича!</strong> Крути спиннер справа вверху для мини-игры!</p>
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
                🎮 Мини-игры
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
                    <th>Итого</th>
                </tr>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><span class="price">#<?= $order['order_id'] ?></span></td>
                    <td><strong><?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name']) ?></strong></td>
                    <td><?= $order['order_date'] ?></td>
                    <td>
                        <a href="casino.php" class="status <?= $order['delivery_needed'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $order['delivery_needed'] ? 'Требуется' : 'Самовывоз' ?>
                        </a>
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
                    <th>Статус</th>
                </tr>
                <?php foreach ($stats as $table => $count): ?>
                <tr>
                    <td><strong><?= ucfirst($table) ?></strong></td>
                    <td><span class="price"><?= $count ?></span></td>
                    <td>
                        <span class="status <?= $count > 0 ? 'status-active' : 'status-inactive' ?>">
                            <?= $count > 0 ? 'Активна' : 'Пуста' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <div class="alert alert-warning" style="margin-top: 20px;">
                ⚡ <strong>Системное сообщение:</strong> Все системы работают в штатном режиме. 
                Последняя проверка: <?= date('d.m.Y H:i:s') ?>
            </div>
        </div>
        
        <div id="gameTab" class="tab-content" style="display: none;">
            <h2><span class="icon-military">🎮 ИГРОВАЯ ЗОНА</span></h2>
            
            <div style="text-align: center; padding: 30px;">
                <div style="font-size: 4rem; margin-bottom: 20px;">🎡</div>
                <h3 style="color: var(--ammo-gold);">ТАНКОВАЯ РУЛЕТКА</h3>
                <p style="color: var(--camo-tan); margin-bottom: 30px;">
                    Крути рулетку и получай виртуальные достижения!
                </p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Всего спинов</div>
                        <div class="stat-number" id="totalSpins">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Рекорд скорости</div>
                        <div class="stat-number" id="recordTime">0.0</div>
                        <div class="stat-label">секунд</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Уровень</div>
                        <div class="stat-number" id="playerLevel">1</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Опыт</div>
                        <div class="stat-number" id="playerXP">0/100</div>
                    </div>
                </div>
                
                <a href="game.php" class="spin-button" style="font-size: 1.5rem; padding: 20px 50px;">
                    🚀 ИГРАТЬ СЕЙЧАС! 🚀
                </a>
                
                <div style="margin-top: 40px; background: rgba(42,42,42,0.7); padding: 20px; border-radius: 10px; border: 2px solid var(--camo-green);">
                    <h4 style="color: var(--digital-green); margin-bottom: 15px;">🏆 ДОСТИЖЕНИЯ</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                        <div class="achievement" data-achieved="true">🎯 Первый спин</div>
                        <div class="achievement" data-achieved="false">⚡ Быстрый как танк</div>
                        <div class="achievement" data-achieved="false">🔥 10 спинов</div>
                        <div class="achievement" data-achieved="false">🏅 Мастер рулетки</div>
                        <div class="achievement" data-achieved="false">💎 Легенда спиннера</div>
                    </div>
                </div>
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
    
    <div class="tank-footer">
        Танковая база данных <a href="order.php" class="icon-military">©</a> 2024 | Статус: <span style="color: var(--digital-green);">● ОПЕРАТИВНЫЙ</span> | 
        Игровой режим: <span style="color: var(--ammo-gold);" id="gameModeStatus">АКТИВЕН</span>
    </div>
    
    <script>
        // Игровые переменные
        let spinCount = parseInt(localStorage.getItem('tankSpinCount')) || 0;
        let spinRecord = parseFloat(localStorage.getItem('tankSpinRecord')) || 0;
        let totalSpins = parseInt(localStorage.getItem('tankTotalSpins')) || 0;
        let playerLevel = parseInt(localStorage.getItem('tankPlayerLevel')) || 1;
        let playerXP = parseInt(localStorage.getItem('tankPlayerXP')) || 0;
        
        // Призы в рулетке
        const prizes = [
            { text: "🎖️ БОЕВОЙ ОРДЕН!", color: "#ff0000", xp: 25 },
            { text: "💰 ТАНКОВОЕ ЗОЛОТО!", color: "#ff8800", xp: 20 },
            { text: "⚡ ЭНЕРГЕТИЧЕСКИЙ БУСТ!", color: "#ffff00", xp: 15 },
            { text: "🛡️ БРОНЕПОВЫШЕНИЕ!", color: "#00ff00", xp: 12 },
            { text: "🔧 РЕМКОМПЛЕКТ!", color: "#0088ff", xp: 10 },
            { text: "🎯 СНАЙПЕРСКАЯ ТОЧНОСТЬ!", color: "#0000ff", xp: 8 },
            { text: "🚀 РАКЕТНЫЙ УСКОРИТЕЛЬ!", color: "#8800ff", xp: 6 },
            { text: "💣 ТРОЙНАЯ БОМБА!", color: "#ff00ff", xp: 5 },
            { text: "🛠️ ИНЖЕНЕРНЫЙ НАБОР!", color: "#ff0088", xp: 4 },
            { text: "🎪 ЦИРКУЛЬНЫЙ ТЮНИНГ!", color: "#ff0000", xp: 3 },
            { text: "🔄 ПЕРЕЗАРЯДКА!", color: "#ff8800", xp: 2 },
            { text: "📈 НЕБОЛЬШОЙ БУСТ!", color: "#ffff00", xp: 1 }
        ];
        
        // Обновление статистики
        function updateGameStats() {
            document.getElementById('spinCount').textContent = spinCount;
            document.getElementById('spinRecord').textContent = spinRecord.toFixed(1);
            document.getElementById('totalSpins').textContent = totalSpins;
            document.getElementById('recordTime').textContent = spinRecord.toFixed(1);
            document.getElementById('playerLevel').textContent = playerLevel;
            document.getElementById('playerXP').textContent = playerXP + "/" + (playerLevel * 100);
            
            // Сохраняем в localStorage
            localStorage.setItem('tankSpinCount', spinCount);
            localStorage.setItem('tankSpinRecord', spinRecord);
            localStorage.setItem('tankTotalSpins', totalSpins);
            localStorage.setItem('tankPlayerLevel', playerLevel);
            localStorage.setItem('tankPlayerXP', playerXP);
        }
        
        // Открытие игры
        function openSpinnerGame() {
            document.getElementById('spinnerGame').style.display = 'block';
            document.body.style.overflow = 'hidden';
            updateGameStats();
        }
        
        // Закрытие игры
        function closeSpinnerGame() {
            document.getElementById('spinnerGame').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Вращение рулетки
        function spinWheel() {
            const wheel = document.getElementById('gameWheel');
            const result = document.getElementById('gameResult');
            const spinButton = document.querySelector('.spin-button');
            
            // Отключаем кнопку на время вращения
            spinButton.disabled = true;
            spinButton.textContent = "🌀 ВРАЩАЕТСЯ...";
            
            // Случайное количество вращений (5-10 полных оборотов)
            const spins = 5 + Math.floor(Math.random() * 6);
            const degrees = spins * 360 + Math.floor(Math.random() * 360);
            
            // Время вращения
            const spinTime = 3 + Math.random() * 2;
            
            // Засекаем время
            const startTime = Date.now();
            
            // Вращаем рулетку
            wheel.style.transition = `transform ${spinTime}s cubic-bezier(0.17, 0.67, 0.23, 1)`;
            wheel.style.transform = `rotate(${degrees}deg)`;
            
            // Определяем приз
            setTimeout(() => {
                const normalizedDegrees = degrees % 360;
                const prizeIndex = Math.floor((360 - normalizedDegrees) / 30) % 12;
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
                }
                
                // Добавляем опыт
                playerXP += prize.xp;
                
                // Проверяем уровень
                const xpNeeded = playerLevel * 100;
                if (playerXP >= xpNeeded) {
                    playerLevel++;
                    playerXP = playerXP - xpNeeded;
                    result.innerHTML = `<div style="color: var(--digital-green); font-size: 2rem;">🎉 УРОВЕНЬ ${playerLevel}!</div>`;
                    setTimeout(() => {
                        showPrize(prize, spinDuration);
                    }, 2000);
                } else {
                    showPrize(prize, spinDuration);
                }
                
                // Включаем кнопку
                setTimeout(() => {
                    spinButton.disabled = false;
                    spinButton.textContent = "🚀 SPIN! 🚀";
                }, 1000);
                
                // Обновляем статистику
                updateGameStats();
                
            }, spinTime * 1000);
        }
        
        // Показ приза
        function showPrize(prize, duration) {
            const result = document.getElementById('gameResult');
            result.innerHTML = `
                <div style="color: ${prize.color}; font-size: 1.8rem; text-shadow: 0 0 10px rgba(255,255,255,0.3);">
                    ${prize.text}
                </div>
                <div style="margin-top: 10px; color: var(--camo-tan);">
                    +${prize.xp} опыта | Время: ${duration.toFixed(2)} сек
                </div>
            `;
            
            // Эффект победы
            document.getElementById('gameWheel').style.boxShadow = `0 0 50px ${prize.color}`;
            setTimeout(() => {
                document.getElementById('gameWheel').style.boxShadow = 'none';
            }, 2000);
        }
        
        // Создание чисел на рулетке
        // Простой рабочий вариант создания цифр
        function createWheelNumbers() {
            const wheelNumbers = document.getElementById('wheelNumbers');
            wheelNumbers.innerHTML = '';
            
            const radius = 140; // Расстояние от центра
            const centerX = 150; // Центр колеса
            const centerY = 150;
            
            for (let i = 1; i <= 12; i++) {
                const angle = (i - 1) * 30 * (Math.PI / 180); // В радианах
                const x = centerX + radius * Math.cos(angle);
                const y = centerY + radius * Math.sin(angle);
                
                const numberDiv = document.createElement('div');
                numberDiv.className = 'wheel-number';
                numberDiv.textContent = i;
                numberDiv.style.position = 'absolute';
                numberDiv.style.left = x + 'px';
                numberDiv.style.top = y + 'px';
                numberDiv.style.transform = 'translate(-50%, -50%)';
                numberDiv.style.color = 'white';
                numberDiv.style.fontWeight = 'bold';
                numberDiv.style.fontSize = '24px';
                numberDiv.style.textShadow = '2px 2px 4px rgba(0,0,0,0.8)';
                numberDiv.style.zIndex = '3';
                
                wheelNumbers.appendChild(numberDiv);
            }
        }
        
        // Управление вкладками
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
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Включить сканирующую линию
            document.querySelector('.scanline').style.display = 'block';
            
            // Создать числа на рулетке
            createWheelNumbers();
            
            // Обновить статистику
            updateGameStats();
            
            // Анимация прогресс-бара
            setTimeout(() => {
                const progressBar = document.querySelector('.progress-bar');
                let width = parseInt(progressBar.style.width);
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
            
            // Анимация достижений
            document.querySelectorAll('.achievement').forEach(ach => {
                if (ach.dataset.achieved === 'true') {
                    ach.style.background = 'linear-gradient(145deg, var(--ammo-gold), var(--rust))';
                    ach.style.color = 'black';
                    ach.style.padding = '8px 15px';
                    ach.style.borderRadius = '20px';
                    ach.style.fontWeight = 'bold';
                } else {
                    ach.style.background = 'rgba(42,42,42,0.7)';
                    ach.style.color = 'var(--camo-tan)';
                    ach.style.padding = '8px 15px';
                    ach.style.borderRadius = '20px';
                    ach.style.opacity = '0.5';
                }
            });
            
            // Анимация главного спиннера при наведении
            const mainSpinner = document.getElementById('mainSpinner');
            mainSpinner.addEventListener('mouseenter', () => {
                mainSpinner.style.animation = 'spin 0.5s linear infinite';
            });
            
            mainSpinner.addEventListener('mouseleave', () => {
                mainSpinner.style.animation = 'spin 5s linear infinite';
            });
        });
        
        // Закрытие игры по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSpinnerGame();
            }
        });
        
        // Закрытие игры при клике вне ее
        document.getElementById('spinnerGame').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSpinnerGame();
            }
        });
    </script>
</body>
</html>