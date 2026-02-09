<?php
require_once 'functions.php';

// Получаем статистику для отображения
$total_clients = $pdo->query("SELECT COUNT(*) as count FROM clients")->fetch()['count'];
$total_components = $pdo->query("SELECT COUNT(*) as count FROM components")->fetch()['count'];
$total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
$total_revenue = $pdo->query("SELECT SUM(total_cost) as total FROM orders")->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Танковое Казино</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== ОСНОВНЫЕ СТИЛИ КАЗИНО ===== */
        :root {
            --casino-gold: #FFD700;
            --casino-red: #DC143C;
            --casino-green: #32CD32;
            --casino-purple: #9370DB;
            --casino-blue: #4169E1;
            --casino-chip: #C0C0C0;
        }
        
        body.casino-mode {
            background: 
                radial-gradient(circle at 20% 30%, rgba(220, 20, 60, 0.2) 0%, transparent 25%),
                radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.2) 0%, transparent 25%),
                radial-gradient(circle at 40% 80%, rgba(50, 205, 50, 0.2) 0%, transparent 20%),
                linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0a0a0a 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        body.casino-mode::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255, 215, 0, 0.05) 10px, rgba(255, 215, 0, 0.05) 20px),
                repeating-linear-gradient(-45deg, transparent, transparent 10px, rgba(220, 20, 60, 0.05) 10px, rgba(220, 20, 60, 0.05) 20px);
            pointer-events: none;
            z-index: -1;
        }
        
        body.casino-mode::after {
            content: '♠️ ♥️ ♣️ ♦️';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10rem;
            opacity: 0.05;
            color: var(--casino-gold);
            z-index: -1;
            white-space: nowrap;
        }
        
        /* ===== КАЗИНО МЕНЮ ===== */
        .casino-menu {
            background: linear-gradient(145deg, 
                rgba(10, 10, 10, 0.95), 
                rgba(20, 20, 20, 0.95));
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 1400px;
            border-radius: 15px;
            border: 3px solid var(--casino-gold);
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.7),
                0 0 40px rgba(255, 215, 0, 0.3),
                inset 0 0 20px rgba(0, 0, 0, 0.8);
            position: relative;
            overflow: hidden;
        }
        
        .casino-menu::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, 
                var(--casino-red) 0%, 
                var(--casino-gold) 25%, 
                var(--casino-green) 50%,
                var(--casino-blue) 75%,
                var(--casino-purple) 100%);
        }
        
        .casino-nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .casino-nav a {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
            color: var(--casino-gold);
            border: 2px solid var(--casino-red);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            min-width: 140px;
            text-align: center;
        }
        
        .casino-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent);
            transition: left 0.6s;
        }
        
        .casino-nav a:hover {
            background: linear-gradient(145deg, #3a3a3a, #2a2a2a);
            color: white;
            border-color: var(--casino-gold);
            transform: translateY(-3px);
            box-shadow: 
                0 8px 20px rgba(255, 215, 0, 0.4),
                inset 0 0 30px rgba(255, 215, 0, 0.1);
        }
        
        .casino-nav a:hover::before {
            left: 100%;
        }
        
        .casino-nav a.active {
            background: linear-gradient(145deg, var(--casino-red), #8b0000);
            color: white;
            border-color: var(--casino-gold);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);
        }
        
        /* ===== КАЗИНО КОНТЕЙНЕР ===== */
        .casino-container {
            background: 
                linear-gradient(145deg, rgba(20, 20, 20, 0.95), rgba(10, 10, 10, 0.95)),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none"/><path d="M0,50 L100,50 M50,0 L50,100" stroke="rgba(255,215,0,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="40" stroke="rgba(220,20,60,0.1)" stroke-width="1" fill="none"/></svg>');
            padding: 30px;
            margin: 20px auto;
            max-width: 1400px;
            border-radius: 15px;
            border: 4px solid var(--casino-gold);
            box-shadow: 
                inset 0 0 50px rgba(0, 0, 0, 0.9),
                0 15px 40px rgba(0, 0, 0, 0.8),
                0 0 0 2px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .casino-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, 
                var(--casino-red) 0%, 
                var(--casino-gold) 20%, 
                var(--casino-green) 40%,
                var(--casino-blue) 60%,
                var(--casino-purple) 80%,
                var(--casino-red) 100%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
        
        .casino-container::after {
            content: '🎰 CASINO MODE 🎲';
            position: absolute;
            top: 15px;
            right: 15px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: var(--casino-gold);
            opacity: 0.7;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        /* ===== КАЗИНО ЗАГОЛОВКИ ===== */
        .casino-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .casino-title h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3.5rem;
            background: linear-gradient(45deg, var(--casino-gold), var(--casino-red), var(--casino-green), var(--casino-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }
        
        .casino-subtitle {
            color: var(--casino-chip);
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        /* ===== ИГРОВАЯ СТАТИСТИКА ===== */
        .casino-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .casino-stat {
            background: linear-gradient(145deg, rgba(30, 30, 30, 0.9), rgba(20, 20, 20, 0.9));
            padding: 20px;
            border-radius: 12px;
            border: 2px solid;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .casino-stat:nth-child(1) { border-color: var(--casino-red); }
        .casino-stat:nth-child(2) { border-color: var(--casino-gold); }
        .casino-stat:nth-child(3) { border-color: var(--casino-green); }
        .casino-stat:nth-child(4) { border-color: var(--casino-blue); }
        
        .casino-stat:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        }
        
        .casino-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: inherit;
        }
        
        .casino-stat:nth-child(1)::before { background: var(--casino-red); }
        .casino-stat:nth-child(2)::before { background: var(--casino-gold); }
        .casino-stat:nth-child(3)::before { background: var(--casino-green); }
        .casino-stat:nth-child(4)::before { background: var(--casino-blue); }
        
        .stat-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 900;
            margin: 15px 0;
            text-shadow: 0 0 20px currentColor;
        }
        
        .casino-stat:nth-child(1) .stat-value { color: var(--casino-red); }
        .casino-stat:nth-child(2) .stat-value { color: var(--casino-gold); }
        .casino-stat:nth-child(3) .stat-value { color: var(--casino-green); }
        .casino-stat:nth-child(4) .stat-value { color: var(--casino-blue); }
        
        .stat-label {
            color: var(--casino-chip);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
        }
        
        /* ===== ИГРОВЫЕ КНОПКИ ===== */
        .game-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .casino-btn {
            padding: 18px 35px;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            min-width: 200px;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .casino-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }
        
        .casino-btn:hover::before {
            left: 100%;
        }
        
        .btn-roulette {
            background: linear-gradient(145deg, var(--casino-red), #8b0000);
            color: white;
            border: 3px solid var(--casino-gold);
            box-shadow: 0 0 25px rgba(220, 20, 60, 0.4);
        }
        
        .btn-roulette:hover {
            background: linear-gradient(145deg, #ff0000, #cc0000);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(220, 20, 60, 0.6);
        }
        
        .btn-slots {
            background: linear-gradient(145deg, var(--casino-gold), #b8860b);
            color: black;
            border: 3px solid var(--casino-red);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.4);
        }
        
        .btn-slots:hover {
            background: linear-gradient(145deg, #ffff00, #ffd700);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.6);
        }
        
        .btn-blackjack {
            background: linear-gradient(145deg, var(--casino-green), #006400);
            color: white;
            border: 3px solid var(--casino-gold);
            box-shadow: 0 0 25px rgba(50, 205, 50, 0.4);
        }
        
        .btn-blackjack:hover {
            background: linear-gradient(145deg, #00ff00, #32cd32);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(50, 205, 50, 0.6);
        }
        
        .btn-poker {
            background: linear-gradient(145deg, var(--casino-blue), #00008b);
            color: white;
            border: 3px solid var(--casino-gold);
            box-shadow: 0 0 25px rgba(65, 105, 225, 0.4);
        }
        
        .btn-poker:hover {
            background: linear-gradient(145deg, #6495ed, #4169e1);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(65, 105, 225, 0.6);
        }
        
        /* ===== ИГРОВЫЕ АВТОМАТЫ ===== */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }
        
        .game-machine {
            background: linear-gradient(145deg, rgba(40, 40, 40, 0.95), rgba(25, 25, 25, 0.95));
            border-radius: 15px;
            padding: 25px;
            border: 3px solid var(--casino-gold);
            box-shadow: 
                inset 0 0 30px rgba(0, 0, 0, 0.8),
                0 10px 25px rgba(0, 0, 0, 0.6),
                0 0 0 2px rgba(255, 215, 0, 0.1);
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }
        
        .game-machine:hover {
            transform: translateY(-10px);
            box-shadow: 
                inset 0 0 40px rgba(0, 0, 0, 0.9),
                0 20px 40px rgba(0, 0, 0, 0.8),
                0 0 30px rgba(255, 215, 0, 0.3);
            border-color: var(--casino-red);
        }
        
        .game-machine::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                var(--casino-gold) 20%, 
                var(--casino-red) 40%,
                var(--casino-gold) 60%,
                var(--casino-red) 80%,
                transparent 100%);
        }
        
        .machine-icon {
            font-size: 4rem;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 0 20px currentColor;
        }
        
        .machine-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            color: var(--casino-gold);
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .machine-description {
            color: var(--casino-chip);
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .machine-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 215, 0, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: var(--casino-gold);
            font-weight: bold;
        }
        
        .stat-text {
            font-size: 0.8rem;
            color: var(--casino-chip);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* ===== ФИШКИ КАЗИНО ===== */
        .chips-display {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 40px 0;
            flex-wrap: wrap;
        }
        
        .casino-chip {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 1.5rem;
            color: black;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            border: 4px solid white;
            box-shadow: 
                0 5px 15px rgba(0, 0, 0, 0.5),
                inset 0 0 20px rgba(0, 0, 0, 0.3);
            animation: chipFloat 3s ease-in-out infinite;
        }
        
        .chip-1 { 
            background: radial-gradient(circle at 30% 30%, #ff0000, #8b0000);
            animation-delay: 0s;
        }
        .chip-5 { 
            background: radial-gradient(circle at 30% 30%, #ffd700, #b8860b);
            animation-delay: 0.3s;
        }
        .chip-10 { 
            background: radial-gradient(circle at 30% 30%, #00ff00, #006400);
            animation-delay: 0.6s;
        }
        .chip-25 { 
            background: radial-gradient(circle at 30% 30%, #0000ff, #00008b);
            animation-delay: 0.9s;
        }
        .chip-100 { 
            background: radial-gradient(circle at 30% 30%, #ffffff, #c0c0c0);
            animation-delay: 1.2s;
        }
        
        .casino-chip:hover {
            transform: translateY(-10px) scale(1.1);
            box-shadow: 
                0 15px 30px rgba(0, 0, 0, 0.6),
                inset 0 0 30px rgba(0, 0, 0, 0.4);
        }
        
        .casino-chip::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            right: 5px;
            bottom: 5px;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-radius: 50%;
        }
        
        @keyframes chipFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }
        
        /* ===== БАЛАНС ИГРОКА ===== */
        .player-balance {
            background: linear-gradient(145deg, rgba(30, 30, 30, 0.9), rgba(20, 20, 20, 0.9));
            padding: 25px;
            border-radius: 15px;
            border: 3px solid var(--casino-gold);
            margin: 40px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .balance-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: var(--casino-gold);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .balance-amount {
            font-family: 'Orbitron', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            color: var(--casino-green);
            text-shadow: 0 0 30px rgba(50, 205, 50, 0.5);
            margin: 20px 0;
        }
        
        .balance-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        
        /* ===== АНИМАЦИИ ===== */
        @keyframes slotSpin {
            0% { transform: translateY(0); }
            100% { transform: translateY(-1000px); }
        }
        
        @keyframes winFlash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        @keyframes dealCard {
            0% { transform: translateX(-100px) rotate(-45deg); opacity: 0; }
            100% { transform: translateX(0) rotate(0deg); opacity: 1; }
        }
        
        .winning {
            animation: winFlash 0.5s infinite;
        }
        
        /* ===== АДАПТИВНОСТЬ ===== */
        @media (max-width: 768px) {
            .casino-title h1 {
                font-size: 2.5rem;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
            }
            
            .casino-nav {
                flex-direction: column;
                align-items: center;
            }
            
            .casino-nav a {
                width: 100%;
            }
            
            .casino-btn {
                min-width: 100%;
            }
            
            .casino-chip {
                width: 60px;
                height: 60px;
                font-size: 1.2rem;
            }
        }
        
        /* ===== ДОПОЛНИТЕЛЬНЫЕ ЭФФЕКТЫ ===== */
        .confetti {
            position: fixed;
            width: 15px;
            height: 15px;
            background: var(--casino-gold);
            opacity: 0;
            z-index: 9999;
            pointer-events: none;
        }
        
        .jackpot-light {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 215, 0, 0.3), transparent 70%);
            pointer-events: none;
            z-index: 9998;
            opacity: 0;
        }
        
        /* ===== ПРОГРЕСС БАРЫ ===== */
        .casino-progress {
            height: 25px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 12px;
            border: 2px solid var(--casino-gold);
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        
        .casino-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--casino-red), var(--casino-gold), var(--casino-green));
            transition: width 0.5s;
            position: relative;
            overflow: hidden;
        }
        
        .casino-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: progressShine 2s infinite;
        }
        
        @keyframes progressShine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>
</head>
<body class="casino-mode">
    <div class="scanline"></div>
    
    <div class="casino-menu">
        <nav class="casino-nav">
            <a href="index.php" class="icon-tank">🏠 Главная</a>
            <a href="clients.php" class="icon-military">👥 Клиенты</a>
            <a href="categories.php" class="icon-ammo">📁 Категории</a>
            <a href="components.php" class="icon-tank">⚙️ Комплектующие</a>
            <a href="orders.php" class="icon-military">📦 Заказы</a>
            <a href="casino.php" class="active">🎰 Казино</a>
        </nav>
    </div>
    
    <div class="casino-container">
        <div class="casino-title">
            <h1>🎰 ТАНКОВОЕ КАЗИНО 🎲</h1>
            <div class="casino-subtitle">ИГРОВАЯ ЗОНА ПРЕМИУМ КЛАССА</div>
            
            <div class="casino-progress">
                <div class="casino-progress-bar" style="width: 85%;"></div>
            </div>
        </div>
        
        <!-- Статистика казино -->
        <div class="casino-stats">
            <div class="casino-stat">
                <div class="machine-icon">👥</div>
                <div class="stat-value"><?= $total_clients ?></div>
                <div class="stat-label">Игроков онлайн</div>
            </div>
            
            <div class="casino-stat">
                <div class="machine-icon">🎮</div>
                <div class="stat-value"><?= $total_components ?></div>
                <div class="stat-label">Игровых автоматов</div>
            </div>
            
            <div class="casino-stat">
                <div class="machine-icon">💰</div>
                <div class="stat-value"><?= number_format($total_orders) ?></div>
                <div class="stat-label">Выигрышей сегодня</div>
            </div>
            
            <div class="casino-stat">
                <div class="machine-icon">🏆</div>
                <div class="stat-value"><?= number_format($total_revenue / 1000, 1) ?>K</div>
                <div class="stat-label">Джекпот (₽)</div>
            </div>
        </div>
        
        <!-- Игровые фишки -->
        <div class="chips-display">
            <div class="casino-chip chip-1" onclick="betChip(1)">1</div>
            <div class="casino-chip chip-5" onclick="betChip(5)">5</div>
            <div class="casino-chip chip-10" onclick="betChip(10)">10</div>
            <div class="casino-chip chip-25" onclick="betChip(25)">25</div>
            <div class="casino-chip chip-100" onclick="betChip(100)">100</div>
        </div>
        
        <!-- Баланс игрока -->
        <div class="player-balance">
            <div class="balance-title">ВАШ БАЛАНС</div>
            <div class="balance-amount" id="playerBalance">10,000</div>
            <div class="balance-controls">
                <button class="casino-btn btn-roulette" onclick="addFunds(1000)">
                    <i class="fas fa-plus"></i> Пополнить
                </button>
                <button class="casino-btn btn-slots" onclick="cashOut()">
                    <i class="fas fa-coins"></i> Вывести
                </button>
                <button class="casino-btn btn-blackjack" onclick="resetBalance()">
                    <i class="fas fa-redo"></i> Сброс
                </button>
            </div>
        </div>
        
        <!-- Игровые кнопки -->
        <div class="game-buttons">
            <button class="casino-btn btn-roulette" onclick="playRoulette()">
                <i class="fas fa-circle-notch"></i> Рулетка
            </button>
            <button class="casino-btn btn-slots" onclick="playSlots()">
                <i class="fas fa-sliders-h"></i> Слоты
            </button>
            <button class="casino-btn btn-blackjack" onclick="playBlackjack()">
                <i class="fas fa-club"></i> Блэкджек
            </button>
            <button class="casino-btn btn-poker" onclick="playPoker()">
                <i class="fas fa-spade"></i> Покер
            </button>
        </div>
        
        <!-- Игровые автоматы -->
        <div class="games-grid">
            <div class="game-machine">
                <div class="machine-icon" style="color: var(--casino-red);">🎡</div>
                <div class="machine-title">ЕВРОПЕЙСКАЯ РУЛЕТКА</div>
                <div class="machine-description">
                    Классическая рулетка с одним зеро. Ставьте на числа, цвета или сектора.
                    Максимальный коэффициент выигрыша: 35x
                </div>
                <div class="machine-stats">
                    <div class="stat-item">
                        <div class="stat-number">97.3%</div>
                        <div class="stat-text">RTP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">35x</div>
                        <div class="stat-text">Макс выигрыш</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔥</div>
                        <div class="stat-text">Хит сезона</div>
                    </div>
                </div>
                <button class="casino-btn btn-roulette" style="width: 100%; margin-top: 15px;" onclick="playRoulette()">
                    ИГРАТЬ СЕЙЧАС
                </button>
            </div>
            
            <div class="game-machine">
                <div class="machine-icon" style="color: var(--casino-gold);">🎰</div>
                <div class="machine-title">ТАНКОВЫЕ СЛОТЫ</div>
                <div class="machine-description">
                    5 барабанов, 20 линий. Бонусные игры, фриспины и прогрессивный джекпот.
                    Символы в военной тематике.
                </div>
                <div class="machine-stats">
                    <div class="stat-item">
                        <div class="stat-number">96.5%</div>
                        <div class="stat-text">RTP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">10,000x</div>
                        <div class="stat-text">Джекпот</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🎯</div>
                        <div class="stat-text">Новинка</div>
                    </div>
                </div>
                <button class="casino-btn btn-slots" style="width: 100%; margin-top: 15px;" onclick="playSlots()">
                    ИГРАТЬ СЕЙЧАС
                </button>
            </div>
            
            <div class="game-machine">
                <div class="machine-icon" style="color: var(--casino-green);">♠️</div>
                <div class="machine-title">БЛЭКДЖЕК 21</div>
                <div class="machine-description">
                    Наберите 21 очко или ближе к этому числу, чем дилер.
                    Стратегическая карточная игра с высоким RTP.
                </div>
                <div class="machine-stats">
                    <div class="stat-item">
                        <div class="stat-number">99.5%</div>
                        <div class="stat-text">RTP</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1.5x</div>
                        <div class="stat-text">Блекджек</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🏆</div>
                        <div class="stat-text">Для профи</div>
                    </div>
                </div>
                <button class="casino-btn btn-blackjack" style="width: 100%; margin-top: 15px;" onclick="playBlackjack()">
                    ИГРАТЬ СЕЙЧАС
                </button>
            </div>
        </div>
        
        <!-- История игр -->
        <div style="margin-top: 60px; text-align: center;">
            <h2 style="color: var(--casino-gold); font-family: 'Orbitron', sans-serif; margin-bottom: 30px;">
                <i class="fas fa-history"></i> ПОСЛЕДНИЕ ВЫИГРЫШИ
            </h2>
            
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <div style="background: rgba(255,215,0,0.1); padding: 15px 25px; border-radius: 10px; border: 1px solid var(--casino-gold);">
                    <div style="color: var(--casino-green); font-weight: bold; font-size: 1.2rem;">Игрок #457</div>
                    <div style="color: var(--casino-chip);">Выиграл 5,250₽</div>
                    <div style="color: var(--casino-gold); font-size: 0.9rem;">🎰 Слоты "Tank Bonus"</div>
                </div>
                
                <div style="background: rgba(220,20,60,0.1); padding: 15px 25px; border-radius: 10px; border: 1px solid var(--casino-red);">
                    <div style="color: var(--casino-green); font-weight: bold; font-size: 1.2rem;">Игрок #892</div>
                    <div style="color: var(--casino-chip);">Выиграл 12,500₽</div>
                    <div style="color: var(--casino-gold); font-size: 0.9rem;">🎡 Рулетка (номер 17)</div>
                </div>
                
                <div style="background: rgba(50,205,50,0.1); padding: 15px 25px; border-radius: 10px; border: 1px solid var(--casino-green);">
                    <div style="color: var(--casino-green); font-weight: bold; font-size: 1.2rem;">Игрок #231</div>
                    <div style="color: var(--casino-chip);">Выиграл 8,750₽</div>
                    <div style="color: var(--casino-gold); font-size: 0.9rem;">♠️ Блэкджек (21 очко)</div>
                </div>
            </div>
            
            <div style="margin-top: 40px; color: var(--casino-chip); font-size: 0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> Играйте ответственно. Минимальный возраст: 18+
            </div>
        </div>
    </div>
    
    <!-- Модальные окна для игр -->
    <div id="rouletteModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px; background: linear-gradient(145deg, #1a1a1a, #0a0a0a);">
            <div class="modal-header" style="background: linear-gradient(90deg, var(--casino-red), var(--casino-gold));">
                <span class="close-modal" onclick="closeGame()">×</span>
                <h3>🎡 ЕВРОПЕЙСКАЯ РУЛЕТКА</h3>
            </div>
            <div class="modal-body" id="rouletteGame">
                <!-- Рулетка будет здесь -->
            </div>
        </div>
    </div>
    
    <div id="slotsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px; background: linear-gradient(145deg, #1a1a1a, #0a0a0a);">
            <div class="modal-header" style="background: linear-gradient(90deg, var(--casino-gold), var(--casino-green));">
                <span class="close-modal" onclick="closeGame()">×</span>
                <h3>🎰 ТАНКОВЫЕ СЛОТЫ</h3>
            </div>
            <div class="modal-body" id="slotsGame">
                <!-- Слоты будут здесь -->
            </div>
        </div>
    </div>
    
    <div class="tank-footer" style="border-top: 3px solid var(--casino-gold);">
        Танковое Казино © 2024 | Лицензия: <span style="color: var(--casino-green);">№RU-2024-777</span> | 
        <span id="onlineCount" style="color: var(--casino-red);">247</span> игроков онлайн
    </div>
    
    <!-- Эффекты -->
    <div id="jackpotLight" class="jackpot-light"></div>
    
    <script>
        // Игровые переменные
        let playerBalance = parseInt(localStorage.getItem('casinoBalance')) || 10000;
        let currentBet = 0;
        let totalWins = parseInt(localStorage.getItem('totalWins')) || 0;
        let totalLosses = parseInt(localStorage.getItem('totalLosses')) || 0;
        
        // Обновление баланса
        function updateBalance() {
            document.getElementById('playerBalance').textContent = 
                playerBalance.toLocaleString('ru-RU');
            localStorage.setItem('casinoBalance', playerBalance);
        }
        
        // Ставка фишкой
        function betChip(amount) {
            if (playerBalance >= amount) {
                currentBet = amount;
                playerBalance -= amount;
                updateBalance();
                
                // Анимация фишки
                event.target.style.transform = 'translateY(-20px) scale(1.2)';
                setTimeout(() => {
                    event.target.style.transform = '';
                }, 300);
                
                showNotification(`Ставка ${amount}₽ принята!`, 'success');
            } else {
                showNotification('Недостаточно средств!', 'error');
            }
        }
        
        // Пополнение баланса
        function addFunds(amount) {
            playerBalance += amount;
            updateBalance();
            showNotification(`Баланс пополнен на ${amount}₽!`, 'success');
            createConfetti();
        }
        
        // Вывод средств
        function cashOut() {
            if (playerBalance > 10000) {
                const profit = playerBalance - 10000;
                showNotification(`Вывод ${profit}₽ успешен! Чистая прибыль: ${profit}₽`, 'success');
                createConfetti();
            } else {
                showNotification(`Вывод ${playerBalance}₽ успешен`, 'info');
            }
            playerBalance = 10000;
            updateBalance();
        }
        
        // Сброс баланса
        function resetBalance() {
            if (confirm('Сбросить баланс до начальных 10,000₽?')) {
                playerBalance = 10000;
                currentBet = 0;
                updateBalance();
                showNotification('Баланс сброшен!', 'info');
            }
        }
        
        // Уведомления
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'tank-notification show';
            notification.style.backgroundColor = type === 'success' ? 
                'rgba(50, 205, 50, 0.9)' : 
                type === 'error' ? 'rgba(220, 20, 60, 0.9)' : 'rgba(255, 215, 0, 0.9)';
            notification.style.color = 'white';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
        
        // Конфетти
        function createConfetti() {
            const colors = [
                "var('--casino-red')",
                "var('--casino-gold')",
                "var('--casino-green')",
                "var('--casino-blue')",
                "var('--casino-purple')"
            ];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-20px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                
                document.body.appendChild(confetti);
                
                // Анимация падения
                const animation = confetti.animate([
                    { top: '-20px', opacity: 1, transform: `rotate(0deg)` },
                    { top: '100vh', opacity: 0, transform: `rotate(${Math.random() * 720}deg)` }
                ], {
                    duration: 2000 + Math.random() * 2000,
                    easing: 'cubic-bezier(0.215, 0.61, 0.355, 1)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }
        
        // Джекпот свет
        function jackpotLight() {
            const light = document.getElementById('jackpotLight');
            light.style.opacity = '1';
            
            const animation = light.animate([
                { opacity: 1 },
                { opacity: 0.3 },
                { opacity: 1 }
            ], {
                duration: 500,
                iterations: 10
            });
            
            animation.onfinish = () => {
                light.style.opacity = '0';
            };
        }
        
        // Играть в рулетку
        function playRoulette() {
            if (currentBet === 0) {
                showNotification('Сначала сделайте ставку!', 'error');
                return;
            }
            
            document.getElementById('rouletteModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Инициализация игры в рулетку
            initRoulette();
        }
        
        // Играть в слоты
        function playSlots() {
            if (currentBet === 0) {
                showNotification('Сначала сделайте ставку!', 'error');
                return;
            }
            
            document.getElementById('slotsModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Инициализация игры в слоты
            initSlots();
        }
        
        // Играть в блэкджек
        function playBlackjack() {
            if (currentBet === 0) {
                showNotification('Сначала сделайте ставку!', 'error');
                return;
            }
            
            // Простая имитация блэкджека
            const playerScore = Math.floor(Math.random() * 10) + 12;
            const dealerScore = Math.floor(Math.random() * 10) + 12;
            
            let result, winAmount;
            
            if (playerScore > 21) {
                result = "Перебор! Вы проиграли.";
                winAmount = 0;
                totalLosses++;
            } else if (dealerScore > 21 || playerScore > dealerScore) {
                result = `Победа! ${playerScore} против ${dealerScore}`;
                winAmount = currentBet * 2;
                playerBalance += winAmount;
                totalWins++;
                createConfetti();
            } else if (playerScore === dealerScore) {
                result = `Ничья! ${playerScore} против ${dealerScore}`;
                winAmount = currentBet;
                playerBalance += winAmount;
            } else {
                result = `Проигрыш! ${playerScore} против ${dealerScore}`;
                winAmount = 0;
                totalLosses++;
            }
            
            showNotification(`${result} Выигрыш: ${winAmount}₽`, winAmount > 0 ? 'success' : 'error');
            updateBalance();
            currentBet = 0;
            
            localStorage.setItem('totalWins', totalWins);
            localStorage.setItem('totalLosses', totalLosses);
        }
        
        // Играть в покер
        function playPoker() {
            showNotification('Покер временно недоступен. Выберите другую игру!', 'info');
        }
        
        // Закрыть игру
        function closeGame() {
            document.getElementById('rouletteModal').style.display = 'none';
            document.getElementById('slotsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Инициализация рулетки
        function initRoulette() {
            const rouletteGame = document.getElementById('rouletteGame');
            rouletteGame.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 1.2rem; color: var(--casino-chip); margin-bottom: 20px;">
                        Ставка: <span style="color: var(--casino-gold); font-weight: bold;">${currentBet}₽</span>
                    </div>
                    
                    <div style="position: relative; width: 300px; height: 300px; margin: 0 auto;">
                        <div id="rouletteWheel" style="width: 100%; height: 100%; border-radius: 50%; 
                             background: conic-gradient(
                                 #008000 0deg 180deg,
                                 #ff0000 180deg 360deg
                             ); border: 10px solid var(--casino-gold); position: relative;">
                             <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                                  width: 50px; height: 50px; background: black; border-radius: 50%; 
                                  border: 3px solid var(--casino-gold); z-index: 2;"></div>
                        </div>
                        <div id="rouletteBall" style="position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
                             width: 20px; height: 20px; background: white; border-radius: 50%; 
                             border: 2px solid black; z-index: 3;"></div>
                        <div id="roulettePointer" style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
                             width: 0; height: 0; border-left: 15px solid transparent; border-right: 15px solid transparent;
                             border-top: 30px solid var(--casino-red); z-index: 4;"></div>
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <button onclick="spinRoulette()" class="casino-btn btn-roulette" style="font-size: 1.3rem;">
                            <i class="fas fa-play"></i> Крутить рулетку!
                        </button>
                    </div>
                    
                    <div id="rouletteResult" style="font-size: 1.5rem; color: var(--casino-gold); 
                         min-height: 60px; font-family: 'Orbitron', sans-serif;">
                        Сделайте ставку и крутите!
                    </div>
                    
                    <div style="margin-top: 30px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <button class="btn" onclick="betOnColor('red')" style="background: #ff0000; color: white;">Красное</button>
                        <button class="btn" onclick="betOnColor('black')" style="background: #000000; color: white;">Черное</button>
                        <button class="btn" onclick="betOnColor('green')" style="background: #008000; color: white;">Зеро</button>
                    </div>
                </div>
            `;
        }
        
        // Спин рулетки
        function spinRoulette() {
            const wheel = document.getElementById('rouletteWheel');
            const ball = document.getElementById('rouletteBall');
            const resultDiv = document.getElementById('rouletteResult');
            
            // Вращение колеса
            const spins = 5 + Math.floor(Math.random() * 5);
            const wheelDegrees = spins * 360 + Math.floor(Math.random() * 360);
            
            // Вращение шарика
            const ballSpins = spins * 2;
            const ballDegrees = ballSpins * 360 + Math.floor(Math.random() * 360);
            
            // Анимация
            wheel.style.transition = 'transform 5s cubic-bezier(0.1, 0.7, 0.1, 1)';
            wheel.style.transform = `rotate(${wheelDegrees}deg)`;
            
            ball.style.transition = 'transform 5s cubic-bezier(0.1, 0.7, 0.1, 1)';
            ball.style.transform = `translateX(-50%) rotate(${ballDegrees}deg)`;
            
            // Результат
            setTimeout(() => {
                const finalPosition = (ballDegrees % 360);
                let winningNumber, winningColor;
                
                // Определяем выигрышный номер и цвет
                if (finalPosition < 9.73) { // 0
                    winningNumber = 0;
                    winningColor = 'green';
                } else {
                    const adjustedPosition = finalPosition - 9.73;
                    const sector = Math.floor(adjustedPosition / 9.73);
                    winningNumber = sector + 1;
                    winningColor = (winningNumber % 2 === 0) ? 'black' : 'red';
                }
                
                // Выигрыш
                let winMultiplier = 1;
                if (winningColor === 'green') winMultiplier = 35;
                else if (currentBetColor === winningColor) winMultiplier = 2;
                
                const winAmount = currentBet * winMultiplier;
                playerBalance += winAmount;
                updateBalance();
                
                // Отображение результата
                resultDiv.innerHTML = `
                    <div style="font-size: 2rem; color: ${winningColor === 'red' ? '#ff0000' : winningColor === 'black' ? '#000000' : '#008000'};">
                        ${winningNumber} ${winningColor === 'green' ? 'ЗЕРО!' : winningColor === 'red' ? 'КРАСНОЕ' : 'ЧЕРНОЕ'}
                    </div>
                    <div style="margin-top: 10px; color: var(--casino-chip);">
                        Выигрыш: <span style="color: var(--casino-gold); font-weight: bold;">${winAmount}₽</span>
                    </div>
                `;
                
                if (winAmount > 0) {
                    createConfetti();
                    totalWins++;
                } else {
                    totalLosses++;
                }
                
                currentBet = 0;
                currentBetColor = null;
                
                localStorage.setItem('totalWins', totalWins);
                localStorage.setItem('totalLosses', totalLosses);
                
            }, 5000);
        }
        
        // Ставка на цвет
        let currentBetColor = null;
        function betOnColor(color) {
            if (currentBet === 0) {
                showNotification('Сначала выберите фишку для ставки!', 'error');
                return;
            }
            
            currentBetColor = color;
            showNotification(`Ставка ${currentBet}₽ на ${color === 'red' ? 'красное' : color === 'black' ? 'черное' : 'зеро'} принята!`, 'success');
        }
        
        // Инициализация слотов
        function initSlots() {
            const slotsGame = document.getElementById('slotsGame');
            slotsGame.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 1.2rem; color: var(--casino-chip); margin-bottom: 20px;">
                        Ставка: <span style="color: var(--casino-gold); font-weight: bold;">${currentBet}₽</span>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.7); padding: 30px; border-radius: 15px; border: 3px solid var(--casino-gold);
                         max-width: 600px; margin: 0 auto;">
                        <div id="slotReels" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 30px;">
                            <div class="slot-reel" style="width: 100px; height: 150px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
                                 border: 3px solid var(--casino-red); border-radius: 10px; overflow: hidden; position: relative;">
                                <div class="reel-strip" style="position: absolute; top: 0; left: 0; width: 100%;"></div>
                            </div>
                            <div class="slot-reel" style="width: 100px; height: 150px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
                                 border: 3px solid var(--casino-gold); border-radius: 10px; overflow: hidden; position: relative;">
                                <div class="reel-strip" style="position: absolute; top: 0; left: 0; width: 100%;"></div>
                            </div>
                            <div class="slot-reel" style="width: 100px; height: 150px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
                                 border: 3px solid var(--casino-green); border-radius: 10px; overflow: hidden; position: relative;">
                                <div class="reel-strip" style="position: absolute; top: 0; left: 0; width: 100%;"></div>
                            </div>
                            <div class="slot-reel" style="width: 100px; height: 150px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
                                 border: 3px solid var(--casino-blue); border-radius: 10px; overflow: hidden; position: relative;">
                                <div class="reel-strip" style="position: absolute; top: 0; left: 0; width: 100%;"></div>
                            </div>
                            <div class="slot-reel" style="width: 100px; height: 150px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
                                 border: 3px solid var(--casino-purple); border-radius: 10px; overflow: hidden; position: relative;">
                                <div class="reel-strip" style="position: absolute; top: 0; left: 0; width: 100%;"></div>
                            </div>
                        </div>
                        
                        <div style="margin: 30px 0;">
                            <button onclick="spinSlots()" class="casino-btn btn-slots" style="font-size: 1.3rem; padding: 20px 50px;">
                                <i class="fas fa-play"></i> Крутить слоты!
                            </button>
                        </div>
                        
                        <div id="slotsResult" style="font-size: 1.5rem; color: var(--casino-gold); 
                             min-height: 60px; font-family: 'Orbitron', sans-serif; margin-bottom: 20px;">
                            Нажмите "Крутить слоты!"
                        </div>
                        
                        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                            <div style="text-align: center;">
                                <div style="color: var(--casino-chip);">Выигрышная линия</div>
                                <div style="height: 5px; background: var(--casino-gold); width: 100px; margin: 5px auto;"></div>
                            </div>
                            <div style="text-align: center;">
                                <div style="color: var(--casino-chip);">x2 за 2 символа</div>
                                <div style="color: var(--casino-gold);">x5 за 3 символа</div>
                                <div style="color: var(--casino-red);">x20 за 4 символа</div>
                                <div style="color: var(--casino-green);">x100 за 5 символов</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Заполняем символы слотов
            const symbols = ['7', 'BAR', '🍒', '⭐', '🔔', '🎯', '⚙️', '🛡️'];
            document.querySelectorAll('.reel-strip').forEach(reel => {
                let html = '';
                for (let i = 0; i < 20; i++) {
                    const symbol = symbols[Math.floor(Math.random() * symbols.length)];
                    html += `<div style="height: 150px; display: flex; align-items: center; justify-content: center;
                            font-size: 3rem; color: var(--casino-gold); border-bottom: 1px solid rgba(255,215,0,0.1);">
                            ${symbol}</div>`;
                }
                reel.innerHTML = html;
            });
        }
        
        // Вращение слотов
        function spinSlots() {
            const reels = document.querySelectorAll('.reel-strip');
            const resultDiv = document.getElementById('slotsResult');
            
            // Отключаем кнопку
            const spinBtn = document.querySelector('.casino-btn.btn-slots');
            spinBtn.disabled = true;
            spinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вращается...';
            
            // Вращаем каждый барабан
            const results = [];
            const durations = [3000, 3200, 3400, 3600, 3800];
            
            reels.forEach((reel, index) => {
                const duration = durations[index];
                const randomOffset = Math.floor(Math.random() * 1500) + 500;
                
                reel.style.transition = `transform ${duration}ms cubic-bezier(0.1, 0.7, 0.1, 1)`;
                reel.style.transform = `translateY(-${randomOffset}px)`;
                
                // Определяем результат для этого барабана
                setTimeout(() => {
                    const symbolIndex = Math.floor((randomOffset % 1500) / 150);
                    const symbols = ['7', 'BAR', '🍒', '⭐', '🔔', '🎯', '⚙️', '🛡️'];
                    results[index] = symbols[symbolIndex % symbols.length];
                    
                    // Когда все барабаны остановились
                    if (results.length === 5 && results.every(r => r !== undefined)) {
                        calculateSlotsResult(results);
                        spinBtn.disabled = false;
                        spinBtn.innerHTML = '<i class="fas fa-play"></i> Крутить слоты!';
                    }
                }, duration);
            });
        }
        
        // Расчет результата слотов
        function calculateSlotsResult(results) {
            const resultDiv = document.getElementById('slotsResult');
            
            // Проверяем выигрышные комбинации
            let winMultiplier = 0;
            let winMessage = '';
            
            // Проверяем 5 одинаковых символов
            if (results.every(symbol => symbol === results[0])) {
                winMultiplier = 100;
                winMessage = `ДЖЕКПОТ! 5x ${results[0]}`;
                jackpotLight();
            }
            // Проверяем 4 одинаковых символа
            else if (results.slice(0, 4).every(symbol => symbol === results[0]) || 
                     results.slice(1, 5).every(symbol => symbol === results[1])) {
                winMultiplier = 20;
                winMessage = `Отлично! 4x ${results[1]}`;
            }
            // Проверяем 3 одинаковых символа
            else if ((results[0] === results[1] && results[1] === results[2]) ||
                     (results[1] === results[2] && results[2] === results[3]) ||
                     (results[2] === results[3] && results[3] === results[4])) {
                winMultiplier = 5;
                winMessage = `Хорошо! 3x ${results[2]}`;
            }
            // Проверяем 2 одинаковых символа подряд
            else if ((results[0] === results[1]) || (results[1] === results[2]) || 
                     (results[2] === results[3]) || (results[3] === results[4])) {
                winMultiplier = 2;
                winMessage = `Не плохо! 2x одинаковых символа`;
            }
            
            const winAmount = currentBet * winMultiplier;
            
            if (winMultiplier > 0) {
                playerBalance += winAmount;
                resultDiv.innerHTML = `
                    <div style="color: var(--casino-green); font-size: 2rem;">
                        ${winMessage}
                    </div>
                    <div style="margin-top: 10px; color: var(--casino-chip);">
                        Выигрыш: <span style="color: var(--casino-gold); font-weight: bold;">${winAmount}₽</span>
                    </div>
                    <div style="margin-top: 10px; color: var(--casino-gold);">
                        Комбинация: ${results.join(' | ')}
                    </div>
                `;
                createConfetti();
                totalWins++;
            } else {
                resultDiv.innerHTML = `
                    <div style="color: var(--casino-red); font-size: 1.5rem;">
                        Нет выигрышной комбинации
                    </div>
                    <div style="margin-top: 10px; color: var(--casino-chip);">
                        Комбинация: ${results.join(' | ')}
                    </div>
                `;
                totalLosses++;
            }
            
            updateBalance();
            currentBet = 0;
            
            localStorage.setItem('totalWins', totalWins);
            localStorage.setItem('totalLosses', totalLosses);
        }
        
        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            updateBalance();
            
            // Обновляем количество онлайн игроков
            setInterval(() => {
                const onlineCount = document.getElementById('onlineCount');
                const current = parseInt(onlineCount.textContent);
                const change = Math.floor(Math.random() * 21) - 10; // -10 to +10
                const newCount = Math.max(100, current + change);
                onlineCount.textContent = newCount;
                
                // Анимация
                onlineCount.style.color = change > 0 ? 'var(--casino-green)' : 'var(--casino-red)';
                setTimeout(() => {
                    onlineCount.style.color = 'var(--casino-red)';
                }, 1000);
            }, 5000);
            
            // Включить сканирующую линию
            document.querySelector('.scanline').style.display = 'block';
            
            // Анимация прогресс-бара
            const progressBar = document.querySelector('.casino-progress-bar');
            let width = 0;
            const interval = setInterval(() => {
                if (width >= 85) {
                    clearInterval(interval);
                    return;
                }
                width++;
                progressBar.style.width = width + '%';
            }, 20);
            
            // Загружаем статистику
            totalWins = parseInt(localStorage.getItem('totalWins')) || 0;
            totalLosses = parseInt(localStorage.getItem('totalLosses')) || 0;
        });
        
        // Закрытие модальных окон при клике вне их
        window.onclick = function(event) {
            const rouletteModal = document.getElementById('rouletteModal');
            const slotsModal = document.getElementById('slotsModal');
            
            if (event.target === rouletteModal) {
                closeGame();
            }
            if (event.target === slotsModal) {
                closeGame();
            }
        };
        
        // Закрытие по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeGame();
            }
        });
    </script>
</body>
</html>