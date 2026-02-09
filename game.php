<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Танковая Аркада</title>
    <style>
        /* ===== СБРОС СТИЛЕЙ ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* ===== ОСНОВНЫЕ СТИЛИ ===== */
        body {
            font-family: 'Orbitron', sans-serif;
            background: #000;
            color: white;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
        }
        
        /* ===== КОНТЕЙНЕР ИГРЫ ===== */
        .game-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }
        
        /* ===== CANVAS ===== */
        #gameCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            display: block;
            background: 
                radial-gradient(circle at 20% 30%, rgba(42, 74, 106, 0.3) 0%, transparent 25%),
                radial-gradient(circle at 80% 70%, rgba(42, 74, 106, 0.2) 0%, transparent 25%),
                linear-gradient(180deg, #0a1929 0%, #1a2a3a 100%);
            cursor: crosshair;
        }
        
        /* ===== ИНТЕРФЕЙС ===== */
        .game-ui {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 100;
        }
        
        /* ===== СТАТИСТИКА ===== */
        .game-stats {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.85);
            border: 3px solid #32cd32;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-family: 'Orbitron', sans-serif;
            min-width: 220px;
            backdrop-filter: blur(5px);
            z-index: 101;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .stat-value {
            color: #32cd32;
            font-weight: bold;
        }
        
        .health-bar {
            width: 100%;
            height: 15px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #32cd32;
            border-radius: 8px;
            overflow: hidden;
            margin: 8px 0;
        }
        
        .health-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff0000, #ff4500, #ffd700, #32cd32);
            transition: width 0.3s;
            width: 100%;
        }
        
        /* ===== МЕНЮ ===== */
        .game-menu {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            border: 5px solid #32cd32;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            z-index: 200;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 50px rgba(50, 205, 50, 0.5);
        }
        
        .game-title {
            color: #32cd32;
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 20px rgba(50, 205, 50, 0.7);
        }
        
        .game-subtitle {
            color: #ffd700;
            margin-bottom: 20px;
            font-size: 1rem;
        }
        
        /* ===== КНОПКИ ===== */
        .game-button {
            background: linear-gradient(145deg, #32cd32, #006400);
            color: white;
            border: none;
            padding: 12px 20px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 8px;
            cursor: pointer;
            margin: 8px 0;
            width: 100%;
            transition: all 0.3s;
            border: 2px solid transparent;
            pointer-events: auto;
        }
        
        .game-button:hover {
            background: linear-gradient(145deg, #00ff00, #32cd32);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(50, 205, 50, 0.4);
            border-color: #ffd700;
        }
        
        /* ===== СООБЩЕНИЯ ===== */
        .game-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.95);
            border: 5px solid;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            z-index: 300;
            backdrop-filter: blur(10px);
            display: none;
        }
        
        .message-win {
            border-color: #32cd32;
            box-shadow: 0 0 50px rgba(50, 205, 50, 0.5);
        }
        
        .message-lose {
            border-color: #dc143c;
            box-shadow: 0 0 50px rgba(220, 20, 60, 0.5);
        }
        
        .message-title {
            font-size: 2.2rem;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .win-title { color: #32cd32; }
        .lose-title { color: #dc143c; }
        
        /* ===== УПРАВЛЕНИЕ ===== */
        .controls-info {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.85);
            border: 2px solid #ffd700;
            border-radius: 8px;
            padding: 12px;
            color: white;
            font-family: 'Orbitron', sans-serif;
            max-width: 280px;
            font-size: 12px;
            z-index: 101;
        }
        
        .control-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .control-key {
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 4px;
            margin-right: 8px;
            min-width: 35px;
            text-align: center;
            border: 1px solid #ffd700;
            font-size: 11px;
        }
        
        /* ===== ПАУЗА ===== */
        .pause-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 150;
        }
        
        .pause-text {
            color: #ffd700;
            font-size: 4rem;
            text-transform: uppercase;
            letter-spacing: 8px;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.7);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        
        /* ===== МОБИЛЬНОЕ УПРАВЛЕНИЕ ===== */
        .mobile-controls {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: none;
            gap: 8px;
            pointer-events: auto;
            z-index: 101;
        }
        
        .mobile-btn {
            width: 60px;
            height: 60px;
            background: rgba(0, 0, 0, 0.7);
            border: 3px solid #32cd32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            user-select: none;
            touch-action: manipulation;
        }
        
        .mobile-btn:active {
            background: rgba(50, 205, 50, 0.7);
            transform: scale(0.95);
        }
        
        /* ===== АДАПТИВНОСТЬ ===== */
        @media (max-width: 768px) {
            .game-stats {
                top: 5px;
                left: 5px;
                padding: 8px;
                min-width: 180px;
                font-size: 12px;
            }
            
            .controls-info {
                display: none;
            }
            
            .mobile-controls {
                display: flex;
            }
            
            .game-menu, .game-message {
                width: 95%;
                padding: 20px;
            }
            
            .game-title {
                font-size: 1.8rem;
            }
            
            .health-bar {
                height: 12px;
            }
        }
        
        @media (max-height: 600px) {
            .game-stats {
                top: 3px;
                left: 3px;
                padding: 6px;
                min-width: 160px;
                font-size: 11px;
            }
            
            .health-bar {
                height: 10px;
            }
            
            .controls-info {
                bottom: 5px;
                left: 5px;
                padding: 8px;
                max-width: 220px;
            }
        }
        
        /* ===== ФИКС ДЛЯ ВСЕХ УСТРОЙСТВ ===== */
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        canvas {
            display: block;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Основной контейнер игры -->
    <div class="game-container">
        <canvas id="gameCanvas"></canvas>
        
        <!-- Интерфейс игры -->
        <div class="game-ui">
            <div class="game-stats">
                <div class="stat-row">
                    <span>УРОВЕНЬ:</span>
                    <span class="stat-value" id="level">1</span>
                </div>
                <div class="stat-row">
                    <span>СЧЕТ:</span>
                    <span class="stat-value" id="score">0</span>
                </div>
                <div class="stat-row">
                    <span>ВРАГИ:</span>
                    <span class="stat-value" id="enemies">10/10</span>
                </div>
                <div class="stat-row">
                    <span>ПАТРОНЫ:</span>
                    <span class="stat-value" id="ammo">30/30</span>
                </div>
                <div class="stat-row">
                    <span>БОМБЫ:</span>
                    <span class="stat-value" id="bombs">3</span>
                </div>
                
                <div class="health-bar">
                    <div class="health-fill" id="healthBar"></div>
                </div>
                
                <div class="stat-row">
                    <span>ЗДОРОВЬЕ:</span>
                    <span class="stat-value" id="health">100/100</span>
                </div>
            </div>
            
            <!-- Управление -->
            <div class="controls-info">
                <div class="control-item">
                    <div class="control-key">WASD</div>
                    <span>Движение</span>
                </div>
                <div class="control-item">
                    <div class="control-key">МЫШЬ</div>
                    <span>Прицеливание</span>
                </div>
                <div class="control-item">
                    <div class="control-key">ЛКМ</div>
                    <span>Стрельба</span>
                </div>
                <div class="control-item">
                    <div class="control-key">ПРОБЕЛ</div>
                    <span>Прыжок</span>
                </div>
                <div class="control-item">
                    <div class="control-key">Q</div>
                    <span>Граната</span>
                </div>
                <div class="control-item">
                    <div class="control-key">ESC</div>
                    <span>Пауза</span>
                </div>
            </div>
            
            <!-- Мобильное управление -->
            <div class="mobile-controls">
                <div class="mobile-btn" id="btnJump">↑</div>
                <div class="mobile-btn" id="btnShoot">🔫</div>
                <div class="mobile-btn" id="btnGrenade">💣</div>
            </div>
        </div>
        
        <!-- Пауза -->
        <div class="pause-overlay" id="pauseOverlay">
            <div class="pause-text">ПАУЗА</div>
        </div>
        
        <!-- Главное меню -->
        <div class="game-menu" id="mainMenu">
            <h1 class="game-title">ТАНКОВАЯ АРКАДА</h1>
            <div class="game-subtitle">УНИЧТОЖАЙ ВРАГОВ, ВЫЖИВАЙ, ПОБЕЖДАЙ!</div>
            
            <button class="game-button" onclick="startGame()">🎮 НАЧАТЬ ИГРУ</button>
            <button class="game-button" onclick="showInstructions()">📖 ОБУЧЕНИЕ</button>
            <button class="game-button" onclick="showHighscores()">🏆 РЕКОРДЫ</button>
            <button class="game-button" onclick="exitGame()">🚪 ВЫХОД</button>
        </div>
        
        <!-- Меню паузы -->
        <div class="game-menu" id="pauseMenu" style="display: none;">
            <h1 class="game-title">ПАУЗА</h1>
            <div class="game-subtitle" id="pauseStats">Уровень: 1 | Счет: 0</div>
            
            <button class="game-button" onclick="resumeGame()">▶ ПРОДОЛЖИТЬ</button>
            <button class="game-button" onclick="restartGame()">🔄 ЗАНОВО</button>
            <button class="game-button" onclick="exitToMenu()">📋 В МЕНЮ</button>
        </div>
        
        <!-- Обучение -->
        <div class="game-menu" id="instructionsMenu" style="display: none;">
            <h1 class="game-title">ОБУЧЕНИЕ</h1>
            <div style="text-align: left; color: white; margin: 15px 0; line-height: 1.5; font-size: 14px;">
                <h3 style="color: #32cd32; margin: 10px 0;">🎯 ЦЕЛЬ:</h3>
                <p>Уничтожить всех врагов!</p>
                
                <h3 style="color: #32cd32; margin: 10px 0;">🎮 УПРАВЛЕНИЕ:</h3>
                <p><strong>WASD</strong> - движение<br>
                <strong>Мышь</strong> - прицеливание<br>
                <strong>ЛКМ</strong> - стрельба<br>
                <strong>Пробел</strong> - прыжок<br>
                <strong>Q</strong> - граната<br>
                <strong>ESC</strong> - пауза</p>
                
                <h3 style="color: #32cd32; margin: 10px 0;">💪 БОНУСЫ:</h3>
                <p><span style="color: #32cd32;">💚</span> Здоровье<br>
                <span style="color: #4169e1;">🔵</span> Патроны<br>
                <span style="color: #ff4500;">💣</span> Гранаты<br>
                <span style="color: #ffd700;">⚡</span> Ускорение<br>
                <span style="color: #9370db;">🛡️</span> Броня</p>
            </div>
            
            <button class="game-button" onclick="hideInstructions()">← НАЗАД</button>
        </div>
        
        <!-- Рекорды -->
        <div class="game-menu" id="highscoresMenu" style="display: none;">
            <h1 class="game-title">РЕКОРДЫ</h1>
            
            <div id="highscoresList" style="text-align: left; color: white; margin: 15px 0; min-height: 200px; font-size: 14px;">
                <div style="text-align: center; padding: 20px; color: #888;">
                    Загрузка рекордов...
                </div>
            </div>
            
            <button class="game-button" onclick="hideHighscores()">← НАЗАД</button>
        </div>
        
        <!-- Сообщение о победе -->
        <div class="game-message message-win" id="winMessage">
            <h2 class="message-title win-title">ПОБЕДА! 🏆</h2>
            <div style="color: white; font-size: 1.2rem; margin: 15px 0;" id="winStats">
                Уровень пройден!
            </div>
            <button class="game-button" onclick="nextLevel()">▶ СЛЕДУЮЩИЙ УРОВЕНЬ</button>
            <button class="game-button" onclick="exitToMenu()">📋 В МЕНЮ</button>
        </div>
        
        <!-- Сообщение о поражении -->
        <div class="game-message message-lose" id="loseMessage">
            <h2 class="message-title lose-title">ПОРАЖЕНИЕ 💀</h2>
            <div style="color: white; font-size: 1.2rem; margin: 15px 0;" id="loseStats">
                Вас уничтожили!
            </div>
            <button class="game-button" onclick="restartGame()">🔄 ИГРАТЬ СНОВА</button>
            <button class="game-button" onclick="exitToMenu()">📋 В МЕНЮ</button>
        </div>
    </div>
    
    <!-- Звуковые эффекты (скрытые аудио элементы) -->
    <audio id="shootSound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-gun-shoot-1661.mp3" type="audio/mpeg">
    </audio>
    <audio id="explosionSound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-bomb-explosion-2800.mp3" type="audio/mpeg">
    </audio>
    <audio id="hitSound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-sword-slash-2768.mp3" type="audio/mpeg">
    </audio>
    <audio id="jumpSound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-jump-arcade-game-166.mp3" type="audio/mpeg">
    </audio>
    <audio id="pickupSound" preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-winning-chimes-2015.mp3" type="audio/mpeg">
    </audio>
    <audio id="bgMusic" loop preload="auto">
        <source src="https://assets.mixkit.co/music/preview/mixkit-game-level-music-689.mp3" type="audio/mpeg">
    </audio>
    
    <script>
        // ===== ОСНОВНЫЕ ПЕРЕМЕННЫЕ ИГРЫ =====
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        
        // Установка размеров canvas ПРАВИЛЬНО
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            console.log("Canvas размеры:", canvas.width, canvas.height);
        }
        
        // Инициализация размеров
        resizeCanvas();
        
        // Состояние игры
        let gameState = 'menu'; // menu, playing, paused, win, lose
        let score = 0;
        let level = 1;
        let health = 100;
        let maxHealth = 100;
        let ammo = 30;
        let maxAmmo = 30;
        let bombs = 3;
        let enemiesKilled = 0;
        let enemiesTotal = 10;
        
        // Игрок
        const player = {
            x: canvas.width / 2,
            y: canvas.height / 2,
            width: 40,
            height: 60,
            speed: 5,
            velocityX: 0,
            velocityY: 0,
            isJumping: false,
            jumpPower: 15,
            gravity: 0.8,
            facing: 0, // угол в радианах
            color: '#32cd32',
            lastShot: 0
        };
        
        // Враги
        let enemies = [];
        
        // Пули
        let bullets = [];
        
        // Гранаты
        let grenades = [];
        
        // Взрывы
        let explosions = [];
        
        // Стены/препятствия
        let walls = [];
        
        // Бонусы
        let powerups = [];
        
        // Кровь (эффекты)
        let bloodEffects = [];
        
        // Следы от пуль
        let bulletTracers = [];
        
        // Управление
        const keys = {};
        const mouse = {
            x: canvas.width / 2,
            y: canvas.height / 2,
            pressed: false
        };
        
        // Время
        let lastTime = 0;
        let gameTime = 0;
        
        // Рекорды
        let highscores = JSON.parse(localStorage.getItem('tankGameHighscores')) || [];
        
        // ===== ИНИЦИАЛИЗАЦИЯ ИГРЫ =====
        function initGame() {
            console.log("Инициализация игры...");
            
            // Сброс состояния
            score = 0;
            health = maxHealth;
            ammo = maxAmmo;
            bombs = 3;
            enemiesKilled = 0;
            enemiesTotal = 10 + level * 2;
            
            // Очистка массивов
            enemies = [];
            bullets = [];
            grenades = [];
            explosions = [];
            walls = [];
            powerups = [];
            bloodEffects = [];
            bulletTracers = [];
            
            // Создание стен
            createWalls();
            
            // Создание врагов
            createEnemies();
            
            // Создание бонусов
            createPowerups();
            
            // Позиция игрока по центру
            player.x = canvas.width / 2 - player.width / 2;
            player.y = canvas.height / 2 - player.height / 2;
            player.velocityX = 0;
            player.velocityY = 0;
            player.isJumping = false;
            
            // Обновление интерфейса
            updateUI();
            
            // Скрытие меню
            document.getElementById('mainMenu').style.display = 'none';
            document.getElementById('pauseMenu').style.display = 'none';
            
            // Изменение состояния
            gameState = 'playing';
            
            // Запуск музыки
            try {
                document.getElementById('bgMusic').volume = 0.2;
                document.getElementById('bgMusic').play();
            } catch (e) {
                console.log("Музыка не запустилась:", e);
            }
            
            // Запуск игрового цикла
            if (!lastTime) {
                lastTime = performance.now();
                requestAnimationFrame(gameLoop);
            }
            
            console.log("Игра инициализирована. Размеры:", canvas.width, canvas.height);
        }
        
        // ===== СОЗДАНИЕ УРОВНЯ =====
        function createWalls() {
            walls = [];
            
            // Границы уровня (немного отступаем от краев)
            const border = 20;
            walls.push({x: 0, y: 0, width: canvas.width, height: border}); // верх
            walls.push({x: 0, y: canvas.height - border, width: canvas.width, height: border}); // низ
            walls.push({x: 0, y: 0, width: border, height: canvas.height}); // лево
            walls.push({x: canvas.width - border, y: 0, width: border, height: canvas.height}); // право
            
            // Случайные стены
            const wallCount = 8 + level;
            for (let i = 0; i < wallCount; i++) {
                const width = 60 + Math.random() * 80;
                const height = 60 + Math.random() * 80;
                const x = border + 50 + Math.random() * (canvas.width - 2*border - 100 - width);
                const y = border + 50 + Math.random() * (canvas.height - 2*border - 100 - height);
                
                walls.push({x, y, width, height});
            }
        }
        
        function createEnemies() {
            enemies = [];
            
            for (let i = 0; i < enemiesTotal; i++) {
                let x, y;
                let validPosition = false;
                let attempts = 0;
                
                // Ищем позицию подальше от игрока
                while (!validPosition && attempts < 50) {
                    x = 100 + Math.random() * (canvas.width - 200);
                    y = 100 + Math.random() * (canvas.height - 200);
                    
                    // Проверяем расстояние до игрока
                    const distToPlayer = Math.sqrt((x - player.x) ** 2 + (y - player.y) ** 2);
                    
                    // Проверяем коллизии со стенами
                    let collisionWithWall = false;
                    for (const wall of walls) {
                        if (x < wall.x + wall.width && x + 30 > wall.x &&
                            y < wall.y + wall.height && y + 30 > wall.y) {
                            collisionWithWall = true;
                            break;
                        }
                    }
                    
                    if (distToPlayer > 200 && !collisionWithWall) {
                        validPosition = true;
                    }
                    
                    attempts++;
                }
                
                if (validPosition) {
                    enemies.push({
                        x,
                        y,
                        width: 30,
                        height: 30,
                        speed: 1 + level * 0.2,
                        health: 50 + level * 10,
                        maxHealth: 50 + level * 10,
                        color: '#dc143c',
                        type: Math.random() > 0.7 ? 'ranged' : 'melee',
                        lastShot: 0,
                        shotCooldown: 1000 + Math.random() * 1000,
                        targetX: x,
                        targetY: y,
                        state: 'patrol'
                    });
                }
            }
        }
        
        function createPowerups() {
            powerups = [];
            const powerupTypes = [
                {type: 'health', color: '#32cd32', symbol: '💚'},
                {type: 'ammo', color: '#4169e1', symbol: '🔵'},
                {type: 'bomb', color: '#ff4500', symbol: '💣'},
                {type: 'speed', color: '#ffd700', symbol: '⚡'},
                {type: 'shield', color: '#9370db', symbol: '🛡️'}
            ];
            
            for (let i = 0; i < 5; i++) {
                const type = powerupTypes[Math.floor(Math.random() * powerupTypes.length)];
                let x, y;
                let validPosition = false;
                let attempts = 0;
                
                while (!validPosition && attempts < 30) {
                    x = 50 + Math.random() * (canvas.width - 100);
                    y = 50 + Math.random() * (canvas.height - 100);
                    
                    // Проверяем коллизии
                    let collision = false;
                    for (const wall of walls) {
                        if (x < wall.x + wall.width && x + 20 > wall.x &&
                            y < wall.y + wall.height && y + 20 > wall.y) {
                            collision = true;
                            break;
                        }
                    }
                    
                    if (!collision) {
                        validPosition = true;
                    }
                    
                    attempts++;
                }
                
                if (validPosition) {
                    powerups.push({
                        x,
                        y,
                        width: 20,
                        height: 20,
                        type: type.type,
                        color: type.color,
                        symbol: type.symbol,
                        collected: false
                    });
                }
            }
        }
        
        // ===== ОБНОВЛЕНИЕ ИГРЫ =====
        function updateGame(deltaTime) {
            // Обновление времени игры
            gameTime += deltaTime;
            
            // Обновление игрока
            updatePlayer(deltaTime);
            
            // Обновление врагов
            updateEnemies(deltaTime);
            
            // Обновление пуль
            updateBullets(deltaTime);
            
            // Обновление гранат
            updateGrenades(deltaTime);
            
            // Обновление взрывов
            updateExplosions(deltaTime);
            
            // Обновление бонусов
            updatePowerups();
            
            // Обновление эффектов
            updateEffects(deltaTime);
            
            // Проверка столкновений
            checkCollisions();
            
            // Проверка условий победы/поражения
            checkGameConditions();
        }
        
        function updatePlayer(deltaTime) {
            // Движение
            let moveX = 0;
            let moveY = 0;
            
            if (keys['w'] || keys['arrowup']) moveY -= player.speed;
            if (keys['s'] || keys['arrowdown']) moveY += player.speed;
            if (keys['a'] || keys['arrowleft']) moveX -= player.speed;
            if (keys['d'] || keys['arrowright']) moveX += player.speed;
            
            // Нормализация диагонального движения
            if (moveX !== 0 && moveY !== 0) {
                moveX *= 0.7071;
                moveY *= 0.7071;
            }
            
            // Прыжок
            if ((keys[' '] || keys['spacebar']) && !player.isJumping) {
                player.velocityY = -player.jumpPower;
                player.isJumping = true;
                playSound('jumpSound');
            }
            
            // Гравитация
            player.velocityY += player.gravity;
            
            // Применение движения
            let newX = player.x + moveX + player.velocityX;
            let newY = player.y + moveY + player.velocityY;
            
            // Проверка коллизий со стенами по X
            player.velocityX = moveX;
            let futurePlayerX = {
                x: newX,
                y: player.y,
                width: player.width,
                height: player.height
            };
            
            let collisionX = false;
            for (const wall of walls) {
                if (rectCollision(futurePlayerX, wall)) {
                    collisionX = true;
                    player.velocityX = 0;
                    break;
                }
            }
            
            if (!collisionX) {
                player.x = newX;
            }
            
            // Проверка коллизий по Y
            player.velocityY = moveY + player.velocityY;
            let futurePlayerY = {
                x: player.x,
                y: newY,
                width: player.width,
                height: player.height
            };
            
            let collisionY = false;
            for (const wall of walls) {
                if (rectCollision(futurePlayerY, wall)) {
                    collisionY = true;
                    player.velocityY = 0;
                    
                    // Если столкнулись с полом, можно прыгать
                    if (player.velocityY > 0) {
                        player.isJumping = false;
                    }
                    break;
                }
            }
            
            if (!collisionY) {
                player.y = newY;
            } else {
                player.velocityY = 0;
            }
            
            // Ограничение движения в пределах экрана (с отступом)
            const border = 20;
            player.x = Math.max(border, Math.min(canvas.width - player.width - border, player.x));
            player.y = Math.max(border, Math.min(canvas.height - player.height - border, player.y));
            
            // Поворот игрока в сторону курсора
            const dx = mouse.x - (player.x + player.width / 2);
            const dy = mouse.y - (player.y + player.height / 2);
            player.facing = Math.atan2(dy, dx);
            
            // Стрельба при удержании ЛКМ
            if (mouse.pressed && ammo > 0 && gameTime - player.lastShot > 150) {
                shoot();
                player.lastShot = gameTime;
            }
        }
        
        function updateEnemies(deltaTime) {
            for (let i = enemies.length - 1; i >= 0; i--) {
                const enemy = enemies[i];
                
                // ИИ врагов
                const dx = player.x - enemy.x;
                const dy = player.y - enemy.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                // Определение состояния
                if (distance < 150) {
                    enemy.state = 'attack';
                } else if (distance < 400) {
                    enemy.state = 'chase';
                } else {
                    enemy.state = 'patrol';
                }
                
                // Действия в зависимости от состояния
                switch (enemy.state) {
                    case 'patrol':
                        if (Math.random() < 0.01 || 
                            Math.abs(enemy.x - enemy.targetX) < 10 && Math.abs(enemy.y - enemy.targetY) < 10) {
                            enemy.targetX = enemy.x + (Math.random() - 0.5) * 200;
                            enemy.targetY = enemy.y + (Math.random() - 0.5) * 200;
                            
                            // Ограничение в пределах экрана
                            enemy.targetX = Math.max(50, Math.min(canvas.width - 50, enemy.targetX));
                            enemy.targetY = Math.max(50, Math.min(canvas.height - 50, enemy.targetY));
                        }
                        
                        const patrolDx = enemy.targetX - enemy.x;
                        const patrolDy = enemy.targetY - enemy.y;
                        const patrolDist = Math.sqrt(patrolDx * patrolDx + patrolDy * patrolDy);
                        
                        if (patrolDist > 0) {
                            enemy.x += (patrolDx / patrolDist) * enemy.speed * 0.5;
                            enemy.y += (patrolDy / patrolDist) * enemy.speed * 0.5;
                        }
                        break;
                        
                    case 'chase':
                        if (distance > 0) {
                            enemy.x += (dx / distance) * enemy.speed;
                            enemy.y += (dy / distance) * enemy.speed;
                        }
                        break;
                        
                    case 'attack':
                        if (enemy.type === 'ranged') {
                            if (gameTime - enemy.lastShot > enemy.shotCooldown) {
                                shootEnemyBullet(enemy);
                                enemy.lastShot = gameTime;
                            }
                            
                            if (distance < 100) {
                                enemy.x -= (dx / distance) * enemy.speed * 0.7;
                                enemy.y -= (dy / distance) * enemy.speed * 0.7;
                            }
                        } else {
                            if (distance > 30) {
                                enemy.x += (dx / distance) * enemy.speed * 1.2;
                                enemy.y += (dy / distance) * enemy.speed * 1.2;
                            } else {
                                if (gameTime - enemy.lastShot > 500) {
                                    health -= 10;
                                    enemy.lastShot = gameTime;
                                    createBloodEffect(player.x + player.width/2, player.y + player.height/2);
                                    playSound('hitSound');
                                    screenShake();
                                }
                            }
                        }
                        break;
                }
                
                // Ограничение в пределах экрана
                enemy.x = Math.max(20, Math.min(canvas.width - enemy.width - 20, enemy.x));
                enemy.y = Math.max(20, Math.min(canvas.height - enemy.height - 20, enemy.y));
                
                // Проверка смерти врага
                if (enemy.health <= 0) {
                    createExplosion(enemy.x + enemy.width/2, enemy.y + enemy.height/2);
                    enemies.splice(i, 1);
                    enemiesKilled++;
                    score += 100;
                    
                    if (Math.random() < 0.3) {
                        createRandomPowerup(enemy.x, enemy.y);
                    }
                    
                    playSound('explosionSound');
                }
            }
        }
        
        function updateBullets(deltaTime) {
            for (let i = bullets.length - 1; i >= 0; i--) {
                const bullet = bullets[i];
                
                // Движение пули
                bullet.x += Math.cos(bullet.angle) * bullet.speed;
                bullet.y += Math.sin(bullet.angle) * bullet.speed;
                
                // Уменьшение времени жизни
                bullet.life -= deltaTime;
                
                // Проверка выхода за границы
                if (bullet.life <= 0 || 
                    bullet.x < 0 || bullet.x > canvas.width || 
                    bullet.y < 0 || bullet.y > canvas.height) {
                    bullets.splice(i, 1);
                    continue;
                }
                
                // Проверка столкновений со стенами
                let hitWall = false;
                for (const wall of walls) {
                    if (bullet.x > wall.x && bullet.x < wall.x + wall.width &&
                        bullet.y > wall.y && bullet.y < wall.y + wall.height) {
                        hitWall = true;
                        createBulletTracer(bullet.x, bullet.y, bullet.angle);
                        break;
                    }
                }
                
                if (hitWall) {
                    bullets.splice(i, 1);
                    continue;
                }
                
                // Проверка попадания во врагов
                if (bullet.owner === 'player') {
                    for (let j = enemies.length - 1; j >= 0; j--) {
                        const enemy = enemies[j];
                        
                        if (bullet.x > enemy.x && bullet.x < enemy.x + enemy.width &&
                            bullet.y > enemy.y && bullet.y < enemy.y + enemy.height) {
                            
                            enemy.health -= bullet.damage;
                            createBloodEffect(bullet.x, bullet.y);
                            bullets.splice(i, 1);
                            playSound('hitSound');
                            break;
                        }
                    }
                }
                // Проверка попадания в игрока
                else if (bullet.owner === 'enemy') {
                    if (bullet.x > player.x && bullet.x < player.x + player.width &&
                        bullet.y > player.y && bullet.y < player.y + player.height) {
                        
                        health -= bullet.damage;
                        createBloodEffect(bullet.x, bullet.y);
                        bullets.splice(i, 1);
                        screenShake();
                        playSound('hitSound');
                    }
                }
            }
        }
        
        function updateGrenades(deltaTime) {
            for (let i = grenades.length - 1; i >= 0; i--) {
                const grenade = grenades[i];
                
                grenade.x += grenade.velocityX;
                grenade.y += grenade.velocityY;
                
                grenade.velocityY += 0.5;
                
                grenade.timer -= deltaTime;
                
                if (grenade.timer <= 0) {
                    createExplosion(grenade.x, grenade.y, 150);
                    grenades.splice(i, 1);
                    playSound('explosionSound');
                    screenShake();
                }
            }
        }
        
        function updateExplosions(deltaTime) {
            for (let i = explosions.length - 1; i >= 0; i--) {
                const explosion = explosions[i];
                
                explosion.radius += explosion.growth;
                explosion.alpha -= 0.02;
                
                if (explosion.alpha <= 0) {
                    explosions.splice(i, 1);
                }
            }
        }
        
        function updatePowerups() {
            for (let i = powerups.length - 1; i >= 0; i--) {
                const powerup = powerups[i];
                
                if (powerup.collected) {
                    powerups.splice(i, 1);
                    continue;
                }
                
                // Проверка сбора игроком
                if (rectCollision(player, {
                    x: powerup.x - 15,
                    y: powerup.y - 15,
                    width: powerup.width + 30,
                    height: powerup.height + 30
                })) {
                    collectPowerup(powerup);
                    powerup.collected = true;
                }
            }
        }
        
        function updateEffects(deltaTime) {
            for (let i = bloodEffects.length - 1; i >= 0; i--) {
                bloodEffects[i].alpha -= 0.01;
                if (bloodEffects[i].alpha <= 0) {
                    bloodEffects.splice(i, 1);
                }
            }
            
            for (let i = bulletTracers.length - 1; i >= 0; i--) {
                bulletTracers[i].alpha -= 0.05;
                if (bulletTracers[i].alpha <= 0) {
                    bulletTracers.splice(i, 1);
                }
            }
        }
        
        // ===== СТОЛКНОВЕНИЯ =====
        function checkCollisions() {
            // Проверка столкновений врагов со стенами
            for (const enemy of enemies) {
                for (const wall of walls) {
                    if (rectCollision(enemy, wall)) {
                        const dx = (enemy.x + enemy.width/2) - (wall.x + wall.width/2);
                        const dy = (enemy.y + enemy.height/2) - (wall.y + wall.height/2);
                        
                        if (Math.abs(dx) > Math.abs(dy)) {
                            enemy.x += dx > 0 ? 2 : -2;
                        } else {
                            enemy.y += dy > 0 ? 2 : -2;
                        }
                    }
                }
            }
        }
        
        function rectCollision(rect1, rect2) {
            return rect1.x < rect2.x + rect2.width &&
                   rect1.x + rect1.width > rect2.x &&
                   rect1.y < rect2.y + rect2.height &&
                   rect1.y + rect1.height > rect2.y;
        }
        
        // ===== ИГРОВЫЕ ДЕЙСТВИЯ =====
        function shoot() {
            if (ammo <= 0) return;
            
            ammo--;
            
            const bullet = {
                x: player.x + player.width / 2,
                y: player.y + player.height / 2,
                angle: player.facing,
                speed: 15,
                damage: 25,
                life: 1000,
                owner: 'player',
                color: '#ffd700'
            };
            
            bullets.push(bullet);
            
            // Отдача
            player.x -= Math.cos(player.facing) * 2;
            player.y -= Math.sin(player.facing) * 2;
            
            playSound('shootSound');
            createBulletTracer(bullet.x, bullet.y, bullet.angle);
            updateUI();
        }
        
        function shootEnemyBullet(enemy) {
            const dx = player.x - enemy.x;
            const dy = player.y - enemy.y;
            const angle = Math.atan2(dy, dx);
            
            const bullet = {
                x: enemy.x + enemy.width / 2,
                y: enemy.y + enemy.height / 2,
                angle: angle,
                speed: 8,
                damage: 10,
                life: 2000,
                owner: 'enemy',
                color: '#dc143c'
            };
            
            bullets.push(bullet);
        }
        
        function throwGrenade() {
            if (bombs <= 0) return;
            
            bombs--;
            
            const grenade = {
                x: player.x + player.width / 2,
                y: player.y + player.height / 2,
                velocityX: Math.cos(player.facing) * 10,
                velocityY: Math.sin(player.facing) * 10,
                timer: 2000,
                radius: 10
            };
            
            grenades.push(grenade);
            updateUI();
        }
        
        function collectPowerup(powerup) {
            playSound('pickupSound');
            
            switch (powerup.type) {
                case 'health':
                    health = Math.min(maxHealth, health + 30);
                    showFloatingText('+30 ЗДОРОВЬЯ', powerup.x, powerup.y, '#32cd32');
                    break;
                    
                case 'ammo':
                    ammo = Math.min(maxAmmo, ammo + 15);
                    showFloatingText('+15 ПАТРОНОВ', powerup.x, powerup.y, '#4169e1');
                    break;
                    
                case 'bomb':
                    bombs += 2;
                    showFloatingText('+2 ГРАНАТЫ', powerup.x, powerup.y, '#ff4500');
                    break;
                    
                case 'speed':
                    player.speed += 2;
                    setTimeout(() => { player.speed -= 2; }, 10000);
                    showFloatingText('УСКОРЕНИЕ', powerup.x, powerup.y, '#ffd700');
                    break;
                    
                case 'shield':
                    health += 50;
                    showFloatingText('+50 БРОНИ', powerup.x, powerup.y, '#9370db');
                    break;
            }
            
            updateUI();
        }
        
        function createRandomPowerup(x, y) {
            const types = ['health', 'ammo', 'bomb', 'speed', 'shield'];
            const type = types[Math.floor(Math.random() * types.length)];
            
            let color, symbol;
            switch (type) {
                case 'health': color = '#32cd32'; symbol = '💚'; break;
                case 'ammo': color = '#4169e1'; symbol = '🔵'; break;
                case 'bomb': color = '#ff4500'; symbol = '💣'; break;
                case 'speed': color = '#ffd700'; symbol = '⚡'; break;
                case 'shield': color = '#9370db'; symbol = '🛡️'; break;
            }
            
            powerups.push({
                x,
                y,
                width: 20,
                height: 20,
                type,
                color,
                symbol,
                collected: false
            });
        }
        
        // ===== ВИЗУАЛЬНЫЕ ЭФФЕКТЫ =====
        function createExplosion(x, y, radius = 100) {
            explosions.push({
                x,
                y,
                radius: 10,
                maxRadius: radius,
                growth: 5,
                alpha: 1,
                color: '#ff4500'
            });
            
            // Урон от взрыва врагам
            for (const enemy of enemies) {
                const dx = enemy.x + enemy.width/2 - x;
                const dy = enemy.y + enemy.height/2 - y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < radius) {
                    const damage = Math.round((1 - distance / radius) * 100);
                    enemy.health -= damage;
                    
                    if (distance > 0) {
                        enemy.x += (dx / distance) * 20;
                        enemy.y += (dy / distance) * 20;
                    }
                }
            }
            
            // Урон игроку
            const dx = player.x + player.width/2 - x;
            const dy = player.y + player.height/2 - y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < radius) {
                const damage = Math.round((1 - distance / radius) * 50);
                health -= damage;
                createBloodEffect(player.x + player.width/2, player.y + player.height/2);
                screenShake();
            }
        }
        
        function createBloodEffect(x, y) {
            for (let i = 0; i < 5; i++) {
                bloodEffects.push({
                    x: x + (Math.random() - 0.5) * 30,
                    y: y + (Math.random() - 0.5) * 30,
                    radius: 3 + Math.random() * 7,
                    alpha: 0.7,
                    color: '#8b0000'
                });
            }
        }
        
        function createBulletTracer(x, y, angle) {
            bulletTracers.push({
                x,
                y,
                angle,
                length: 50,
                alpha: 1,
                color: '#ffd700'
            });
        }
        
        function showFloatingText(text, x, y, color) {
            const textElement = document.createElement('div');
            textElement.textContent = text;
            textElement.style.position = 'absolute';
            textElement.style.left = x + 'px';
            textElement.style.top = y + 'px';
            textElement.style.color = color;
            textElement.style.fontFamily = 'Orbitron, sans-serif';
            textElement.style.fontWeight = 'bold';
            textElement.style.fontSize = '16px';
            textElement.style.textShadow = '2px 2px 4px rgba(0,0,0,0.8)';
            textElement.style.pointerEvents = 'none';
            textElement.style.zIndex = '100';
            textElement.style.opacity = '1';
            textElement.style.transition = 'all 1s';
            
            document.querySelector('.game-container').appendChild(textElement);
            
            setTimeout(() => {
                textElement.style.opacity = '0';
                textElement.style.transform = 'translateY(-40px)';
            }, 10);
            
            setTimeout(() => {
                textElement.remove();
            }, 1000);
        }
        
        function screenShake() {
            const gameContainer = document.querySelector('.game-container');
            gameContainer.style.transform = 'translate(5px, 5px)';
            
            setTimeout(() => {
                gameContainer.style.transform = 'translate(-5px, -5px)';
            }, 50);
            
            setTimeout(() => {
                gameContainer.style.transform = 'translate(0, 0)';
            }, 100);
        }
        
        // ===== ПРОВЕРКА УСЛОВИЙ ИГРЫ =====
        function checkGameConditions() {
            if (health <= 0) {
                gameOver(false);
                return;
            }
            
            if (enemies.length === 0) {
                gameOver(true);
                return;
            }
        }
        
        function gameOver(isWin) {
            gameState = isWin ? 'win' : 'lose';
            
            document.getElementById('bgMusic').pause();
            document.getElementById('bgMusic').currentTime = 0;
            
            if (isWin) {
                saveHighscore();
                document.getElementById('winStats').textContent = 
                    `Уровень ${level} пройден! Счет: ${score}`;
                document.getElementById('winMessage').style.display = 'block';
            } else {
                document.getElementById('loseStats').textContent = 
                    `Вы погибли на уровне ${level}. Счет: ${score}`;
                document.getElementById('loseMessage').style.display = 'block';
            }
        }
        
        // ===== ОТРИСОВКА =====
        function draw() {
            // Очистка canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Рисование фона
            drawBackground();
            
            // Рисование стен
            drawWalls();
            
            // Рисование бонусов
            drawPowerups();
            
            // Рисование врагов
            drawEnemies();
            
            // Рисование гранат
            drawGrenades();
            
            // Рисование пуль
            drawBullets();
            
            // Рисование игрока
            drawPlayer();
            
            // Рисование взрывов
            drawExplosions();
            
            // Рисование эффектов
            drawEffects();
            
            // Рисование прицела
            if (gameState === 'playing') {
                drawCrosshair();
            }
        }
        
        function drawBackground() {
            const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
            gradient.addColorStop(0, '#0a1929');
            gradient.addColorStop(1, '#1a2a3a');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        function drawWalls() {
            ctx.fillStyle = '#8b4513';
            ctx.strokeStyle = '#654321';
            ctx.lineWidth = 2;
            
            for (const wall of walls) {
                ctx.fillRect(wall.x, wall.y, wall.width, wall.height);
                ctx.strokeRect(wall.x, wall.y, wall.width, wall.height);
            }
        }
        
        function drawPlayer() {
            ctx.save();
            ctx.translate(player.x + player.width / 2, player.y + player.height / 2);
            ctx.rotate(player.facing);
            
            // Тело танка
            ctx.fillStyle = player.color;
            ctx.fillRect(-player.width / 2, -player.height / 2, player.width, player.height);
            
            // Башня
            ctx.fillStyle = '#228b22';
            ctx.fillRect(-15, -15, 30, 30);
            
            // Дуло
            ctx.fillStyle = '#2f4f4f';
            ctx.fillRect(0, -5, 30, 10);
            
            // Детали
            ctx.fillStyle = '#006400';
            ctx.fillRect(-player.width / 2 + 5, -player.height / 2 + 5, player.width - 10, 10);
            
            // Окно
            ctx.fillStyle = '#87ceeb';
            ctx.fillRect(-10, -10, 20, 20);
            
            // Обводка
            ctx.strokeStyle = '#006400';
            ctx.lineWidth = 2;
            ctx.strokeRect(-player.width / 2, -player.height / 2, player.width, player.height);
            
            ctx.restore();
        }
        
        function drawEnemies() {
            for (const enemy of enemies) {
                ctx.save();
                ctx.translate(enemy.x + enemy.width / 2, enemy.y + enemy.height / 2);
                
                const dx = player.x - enemy.x;
                const dy = player.y - enemy.y;
                const angle = Math.atan2(dy, dx);
                ctx.rotate(angle);
                
                // Тело врага
                ctx.fillStyle = enemy.color;
                ctx.fillRect(-enemy.width / 2, -enemy.height / 2, enemy.width, enemy.height);
                
                if (enemy.type === 'ranged') {
                    ctx.fillStyle = '#8b0000';
                    ctx.fillRect(0, -3, 20, 6);
                } else {
                    ctx.fillStyle = '#696969';
                    ctx.fillRect(0, -2, 25, 4);
                }
                
                ctx.strokeStyle = '#8b0000';
                ctx.lineWidth = 2;
                ctx.strokeRect(-enemy.width / 2, -enemy.height / 2, enemy.width, enemy.height);
                
                ctx.restore();
                
                // Полоска здоровья
                if (enemy.health < enemy.maxHealth) {
                    const healthWidth = 30;
                    const healthPercent = enemy.health / enemy.maxHealth;
                    
                    ctx.fillStyle = '#8b0000';
                    ctx.fillRect(enemy.x, enemy.y - 10, healthWidth, 5);
                    
                    ctx.fillStyle = '#32cd32';
                    ctx.fillRect(enemy.x, enemy.y - 10, healthWidth * healthPercent, 5);
                }
            }
        }
        
        function drawBullets() {
            for (const bullet of bullets) {
                ctx.save();
                ctx.translate(bullet.x, bullet.y);
                ctx.rotate(bullet.angle);
                
                ctx.fillStyle = bullet.color;
                ctx.fillRect(-3, -1, 6, 2);
                
                ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                ctx.fillRect(-2, -0.5, 4, 1);
                
                ctx.restore();
            }
        }
        
        function drawGrenades() {
            for (const grenade of grenades) {
                ctx.fillStyle = '#ff4500';
                ctx.beginPath();
                ctx.arc(grenade.x, grenade.y, grenade.radius, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        function drawExplosions() {
            for (const explosion of explosions) {
                const gradient = ctx.createRadialGradient(
                    explosion.x, explosion.y, 0,
                    explosion.x, explosion.y, explosion.radius
                );
                gradient.addColorStop(0, `rgba(255, 69, 0, ${explosion.alpha})`);
                gradient.addColorStop(0.5, `rgba(255, 140, 0, ${explosion.alpha * 0.7})`);
                gradient.addColorStop(1, `rgba(255, 215, 0, 0)`);
                
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(explosion.x, explosion.y, explosion.radius, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.fillStyle = `rgba(255, 255, 255, ${explosion.alpha})`;
                ctx.beginPath();
                ctx.arc(explosion.x, explosion.y, explosion.radius * 0.3, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        function drawPowerups() {
            for (const powerup of powerups) {
                if (powerup.collected) continue;
                
                const pulse = Math.sin(gameTime / 200) * 5;
                
                ctx.fillStyle = powerup.color;
                ctx.beginPath();
                ctx.arc(powerup.x + 10, powerup.y + 10, 10 + pulse, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.fillStyle = 'white';
                ctx.beginPath();
                ctx.arc(powerup.x + 10, powerup.y + 10, 6 + pulse * 0.5, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.font = '16px Arial';
                ctx.fillStyle = powerup.color;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(powerup.symbol, powerup.x + 10, powerup.y + 10);
            }
        }
        
        function drawEffects() {
            for (const blood of bloodEffects) {
                ctx.fillStyle = `rgba(139, 0, 0, ${blood.alpha})`;
                ctx.beginPath();
                ctx.arc(blood.x, blood.y, blood.radius, 0, Math.PI * 2);
                ctx.fill();
            }
            
            for (const tracer of bulletTracers) {
                ctx.strokeStyle = `rgba(255, 215, 0, ${tracer.alpha})`;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(tracer.x, tracer.y);
                ctx.lineTo(
                    tracer.x - Math.cos(tracer.angle) * tracer.length,
                    tracer.y - Math.sin(tracer.angle) * tracer.length
                );
                ctx.stroke();
            }
        }
        
        function drawCrosshair() {
            const centerX = mouse.x;
            const centerY = mouse.y;
            
            ctx.strokeStyle = 'rgba(50, 205, 50, 0.8)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(centerX, centerY, 15, 0, Math.PI * 2);
            ctx.stroke();
            
            ctx.strokeStyle = 'rgba(255, 215, 0, 0.9)';
            ctx.lineWidth = 1;
            
            ctx.beginPath();
            ctx.moveTo(centerX - 8, centerY);
            ctx.lineTo(centerX + 8, centerY);
            ctx.stroke();
            
            ctx.beginPath();
            ctx.moveTo(centerX, centerY - 8);
            ctx.lineTo(centerX, centerY + 8);
            ctx.stroke();
            
            ctx.fillStyle = '#ff0000';
            ctx.beginPath();
            ctx.arc(centerX, centerY, 2, 0, Math.PI * 2);
            ctx.fill();
        }
        
        // ===== ИГРОВОЙ ЦИКЛ =====
        function gameLoop(currentTime) {
            const deltaTime = currentTime - lastTime;
            lastTime = currentTime;
            
            if (gameState === 'playing') {
                updateGame(deltaTime);
            }
            
            draw();
            requestAnimationFrame(gameLoop);
        }
        
        // ===== УПРАВЛЕНИЕ ИНТЕРФЕЙСОМ =====
        function updateUI() {
            document.getElementById('level').textContent = level;
            document.getElementById('score').textContent = score;
            document.getElementById('enemies').textContent = `${enemiesKilled}/${enemiesTotal}`;
            document.getElementById('ammo').textContent = `${ammo}/${maxAmmo}`;
            document.getElementById('bombs').textContent = bombs;
            document.getElementById('health').textContent = `${Math.max(0, Math.floor(health))}/${maxHealth}`;
            
            const healthPercent = Math.max(0, health) / maxHealth;
            document.getElementById('healthBar').style.width = `${healthPercent * 100}%`;
            
            let healthColor;
            if (healthPercent > 0.7) healthColor = '#32cd32';
            else if (healthPercent > 0.3) healthColor = '#ffd700';
            else healthColor = '#dc143c';
            
            document.getElementById('healthBar').style.background = 
                `linear-gradient(90deg, ${healthColor}, ${healthColor})`;
        }
        
        function startGame() {
            initGame();
        }
        
        function resumeGame() {
            gameState = 'playing';
            document.getElementById('pauseMenu').style.display = 'none';
            document.getElementById('pauseOverlay').style.display = 'none';
            document.getElementById('bgMusic').play();
        }
        
        function pauseGame() {
            if (gameState === 'playing') {
                gameState = 'paused';
                document.getElementById('pauseMenu').style.display = 'block';
                document.getElementById('pauseOverlay').style.display = 'flex';
                document.getElementById('pauseStats').textContent = 
                    `Уровень: ${level} | Счет: ${score}`;
                document.getElementById('bgMusic').pause();
            }
        }
        
        function restartGame() {
            hideAllMenus();
            initGame();
        }
        
        function nextLevel() {
            level++;
            hideAllMenus();
            initGame();
        }
        
        function exitToMenu() {
            gameState = 'menu';
            hideAllMenus();
            document.getElementById('mainMenu').style.display = 'block';
            document.getElementById('bgMusic').pause();
            document.getElementById('bgMusic').currentTime = 0;
        }
        
        function exitGame() {
            window.location.href = 'index.php';
        }
        
        function showInstructions() {
            document.getElementById('mainMenu').style.display = 'none';
            document.getElementById('instructionsMenu').style.display = 'block';
        }
        
        function hideInstructions() {
            document.getElementById('instructionsMenu').style.display = 'none';
            document.getElementById('mainMenu').style.display = 'block';
        }
        
        function showHighscores() {
            document.getElementById('mainMenu').style.display = 'none';
            document.getElementById('highscoresMenu').style.display = 'block';
            loadHighscores();
        }
        
        function hideHighscores() {
            document.getElementById('highscoresMenu').style.display = 'none';
            document.getElementById('mainMenu').style.display = 'block';
        }
        
        function hideAllMenus() {
            document.getElementById('mainMenu').style.display = 'none';
            document.getElementById('pauseMenu').style.display = 'none';
            document.getElementById('instructionsMenu').style.display = 'none';
            document.getElementById('highscoresMenu').style.display = 'none';
            document.getElementById('winMessage').style.display = 'none';
            document.getElementById('loseMessage').style.display = 'none';
            document.getElementById('pauseOverlay').style.display = 'none';
        }
        
        // ===== РЕКОРДЫ =====
        function saveHighscore() {
            const playerName = prompt('Введите ваше имя для таблицы рекордов:', 'Игрок');
            if (playerName) {
                const highscore = {
                    name: playerName.substring(0, 10),
                    score: score,
                    level: level,
                    date: new Date().toLocaleDateString('ru-RU')
                };
                
                highscores.push(highscore);
                highscores.sort((a, b) => b.score - a.score);
                highscores = highscores.slice(0, 10);
                localStorage.setItem('tankGameHighscores', JSON.stringify(highscores));
            }
        }
        
        function loadHighscores() {
            const highscoresList = document.getElementById('highscoresList');
            
            if (highscores.length === 0) {
                highscoresList.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #888;">
                        Рекордов пока нет. Сыграйте и установите первый рекорд!
                    </div>
                `;
                return;
            }
            
            let html = '<div style="max-height: 200px; overflow-y: auto;">';
            highscores.forEach((record, index) => {
                const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
                
                html += `<div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.1);">`;
                html += `<div style="color: #ffd700;">${medal}</div>`;
                html += `<div>${record.name}</div>`;
                html += `<div style="color: #32cd32; font-weight: bold;">${record.score}</div>`;
                html += `<div style="color: #888; font-size: 0.9rem;">${record.date}</div>`;
                html += `</div>`;
            });
            html += '</div>';
            highscoresList.innerHTML = html;
        }
        
        // ===== ЗВУКИ =====
        function playSound(soundId) {
            const sound = document.getElementById(soundId);
            if (sound) {
                sound.currentTime = 0;
                try {
                    sound.play();
                } catch (e) {
                    // Игнорируем ошибки воспроизведения
                }
            }
        }
        
        // ===== ОБРАБОТЧИКИ СОБЫТИЙ =====
        // Обработка клавиатуры
        document.addEventListener('keydown', (e) => {
            const key = e.key.toLowerCase();
            keys[key] = true;
            
            if (key === ' ') {
                e.preventDefault();
            }
            
            if (key === 'escape') {
                e.preventDefault();
                if (gameState === 'playing') {
                    pauseGame();
                } else if (gameState === 'paused') {
                    resumeGame();
                }
            }
            
            if (key === 'q' && gameState === 'playing') {
                throwGrenade();
            }
            
            if (key === 'r' && gameState === 'playing') {
                if (ammo < maxAmmo) {
                    ammo = maxAmmo;
                    updateUI();
                    showFloatingText('ПЕРЕЗАРЯДКА', player.x, player.y, '#4169e1');
                }
            }
        });
        
        document.addEventListener('keyup', (e) => {
            const key = e.key.toLowerCase();
            keys[key] = false;
        });
        
        // Обработка мыши
        canvas.addEventListener('mousemove', (e) => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });
        
        canvas.addEventListener('mousedown', (e) => {
            mouse.pressed = true;
            if (e.button === 0 && gameState === 'playing') {
                if (ammo > 0) {
                    shoot();
                }
            }
        });
        
        canvas.addEventListener('mouseup', () => {
            mouse.pressed = false;
        });
        
        canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });
        
        // Мобильное управление
        document.getElementById('btnJump').addEventListener('touchstart', (e) => {
            e.preventDefault();
            keys[' '] = true;
        });
        
        document.getElementById('btnJump').addEventListener('touchend', (e) => {
            e.preventDefault();
            keys[' '] = false;
        });
        
        document.getElementById('btnShoot').addEventListener('touchstart', (e) => {
            e.preventDefault();
            if (gameState === 'playing') {
                shoot();
            }
        });
        
        document.getElementById('btnGrenade').addEventListener('touchstart', (e) => {
            e.preventDefault();
            if (gameState === 'playing') {
                throwGrenade();
            }
        });
        
        // Изменение размера окна
        window.addEventListener('resize', () => {
            resizeCanvas();
            
            if (gameState === 'playing') {
                createWalls();
            }
        });
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', () => {
            highscores = JSON.parse(localStorage.getItem('tankGameHighscores')) || [];
            resizeCanvas();
            document.getElementById('mainMenu').style.display = 'block';
        });
    </script>
</body>
</html>