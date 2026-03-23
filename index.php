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

// Свежие товары
$fresh_items = $pdo->query("SELECT * FROM components ORDER BY component_id DESC LIMIT 4")->fetchAll();

// Динамический контент из БД
$popular_items = $pdo->query("SELECT * FROM site_popular_items WHERE is_active = 1 ORDER BY rank_position ASC LIMIT 5")->fetchAll();
$top_clients_db = $pdo->query("SELECT * FROM site_top_clients WHERE is_active = 1 ORDER BY rank_position ASC LIMIT 3")->fetchAll();
$news_items = $pdo->query("SELECT * FROM site_news WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
$forum_messages = $pdo->query("SELECT * FROM site_forum WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title>🌸 ANIME WORLD - КАВАЙНЫЙ МАГАЗИН 🌸</title>
</head>
<body>
    <div class="scanline"></div>
    
    <?php include 'navbar.php'; ?>
    
    <!-- ЯПОНСКИЕ ФОНАРИКИ -->
    <div class="lanterns">
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
        <div class="lantern"></div>
    </div>

    <!-- ГЛАВНЫЙ КОНТЕЙНЕР В СТИЛЕ ANIME WORLD -->
    <div class="main-container">
        <!-- ЦЕНТРАЛЬНЫЙ КОНТЕНТ -->
        <main class="main-content" style="grid-column: 1 / -1;">
            <!-- ГЛАВНЫЙ БАННЕР -->
            <div class="hero-banner">
                <div class="hero-character">🦸</div>
                <div class="hero-text">
                    <h2 class="hero-title">🌸 ANIME WORLD 🌸<br>ЛУЧШИЕ КОМПЛЕКТУЮЩИЕ!</h2>
                    <div class="hero-buttons">
                        <a href="components.php" class="hero-btn hero-btn-primary">
                            🛒 КАТАЛОГ
                        </a>
                        <a href="register.php" class="hero-btn hero-btn-secondary">
                            💖 ВСТУПИТЬ
                        </a>
                    </div>
                </div>
                <div class="hero-mascot">🐱</div>
            </div>

            <!-- СТАТИСТИКА -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-icons">⭐</span>
                    <h2 class="section-title">Статистика Магазина</h2>
                    <span class="section-icons">⭐</span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">💕 Клиенты</div>
                        <div class="stat-number"><?= $stats['clients'] ?></div>
                        <div class="stat-label">брата в базе</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">🎀 Товары</div>
                        <div class="stat-number"><?= $stats['components'] ?></div>
                        <div class="stat-label">кавайных вещей</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">📦 Заказы</div>
                        <div class="stat-number"><?= $stats['orders'] ?></div>
                        <div class="stat-label">отправлено</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">💰 Выручка</div>
                        <div class="stat-number"><?= number_format($revenue, 2) ?> ₽</div>
                        <div class="stat-label">заработано</div>
                    </div>
                </div>
            </div>

            <!-- СВЕЖИЕ ВЫПУСКИ / ТОВАРЫ -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-icons">🆕</span>
                    <h2 class="section-title">Свежие Товары</h2>
                    <span class="section-icons">🆕</span>
                </div>
                
                <div class="cards-grid">
                    <?php foreach($fresh_items as $comp): ?>
                    <div class="anime-card">
                        <div class="card-image">
                            <span class="card-badge">NEW</span>
                            👙
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($comp['component_name']) ?></h3>
                            <div class="card-meta">
                                <span>💰 <?= number_format($comp['price'], 0) ?>₽</span>
                                <span>📦 <?= $comp['in_stock'] ?> шт</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ПОСЛЕДНИЕ ЗАКАЗЫ -->
            <div class="content-section">
                <div class="section-header">
                    <span class="section-icons">📋</span>
                    <h2 class="section-title">Последние Заказы</h2>
                    <span class="section-icons">📋</span>
                </div>
                
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Дата</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                    </tr>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><span class="price">#<?= $order['order_id'] ?></span></td>
                        <td><strong><?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name']) ?></strong></td>
                        <td><?= $order['order_date'] ?></td>
                        <td><span class="price"><?= number_format($order['total_cost'], 0) ?> ₽</span></td>
                        <td>
                            <span class="status <?= $order['delivery_needed'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $order['delivery_needed'] ? '🚚 Доставка' : '🏪 Самовывоз' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </main>

        <!-- ПРАВЫЙ САЙДБАР -->
        <aside class="right-sidebar">
            <!-- НОВОСТИ -->
            <div class="news-section">
                <div class="sidebar-header">📰 Новости</div>
                <div class="news-list">
                    <?php if (count($news_items) > 0): ?>
                        <?php foreach ($news_items as $news): ?>
                        <div class="news-item" onclick="window.location.href='news_view.php?id=<?= $news['news_id'] ?>'" style="cursor: pointer;">
                            <span class="news-icon"><?= $news['icon'] ?></span>
                            <span class="news-text"><?= htmlspecialchars($news['title']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="news-item">
                            <span class="news-icon">🌸</span>
                            <span class="news-text">Новый завоз комплектующих! Успей купить!</span>
                        </div>
                        <div class="news-item">
                            <span class="news-icon">💕</span>
                            <span class="news-text">Скидки для постоянных клиентов!</span>
                        </div>
                        <div class="news-item">
                            <span class="news-icon">🎀</span>
                            <span class="news-text">Бесплатная доставка от 5000₽!</span>
                        </div>
                        <div class="news-item">
                            <span class="news-icon">⭐</span>
                            <span class="news-text">Топ-10 лучших товаров месяца!</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ФОРУМ ЧАТ -->
            <div class="forum-chat">
                <div class="sidebar-header">💬 Форум Чат</div>
                <div class="chat-messages">
                    <?php if (count($forum_messages) > 0): ?>
                        <?php foreach ($forum_messages as $msg): ?>
                        <div class="chat-message">
                            <div class="chat-avatar"><?= $msg['avatar'] ?></div>
                            <div class="chat-content">
                                <div class="chat-name"><?= htmlspecialchars($msg['username']) ?></div>
                                <div class="chat-text"><?= htmlspecialchars($msg['message']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-message">
                            <div class="chat-avatar">👤</div>
                            <div class="chat-content">
                                <div class="chat-name">Anime_Fan</div>
                                <div class="chat-text">Привет всем! 💕</div>
                            </div>
                        </div>
                        <div class="chat-message">
                            <div class="chat-avatar">👤</div>
                            <div class="chat-content">
                                <div class="chat-name">Kira</div>
                                <div class="chat-text">Кто что смотрит сейчас?</div>
                            </div>
                        </div>
                        <div class="chat-message">
                            <div class="chat-avatar">👤</div>
                            <div class="chat-content">
                                <div class="chat-name">NarutoFan</div>
                                <div class="chat-text">Заказал комплектующие! Жду! 🎀</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ПОДПИСКА -->
            <div class="subscribe-section">
                <h3 class="subscribe-title">📧 ПОДПИШИСЬ НА НОВОСТИ!</h3>
                <form class="subscribe-form">
                    <input type="email" class="subscribe-input" placeholder="Твой Email...">
                    <button type="submit" class="subscribe-btn">ПОДПИСАТЬСЯ 💕</button>
                </form>
                <div class="social-links">
                    <div class="social-btn">📘</div>
                    <div class="social-btn">📸</div>
                    <div class="social-btn">🐦</div>
                    <div class="social-btn">📺</div>
                </div>
            </div>
        </aside>
    </div>

    <!-- ФУТЕР -->
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Лучший магазин для братов! 🎀
    </div>

    <!-- VIDEO PLAYER -->
    <link rel="stylesheet" href="video-player.css">
    <script src="video-player.js"></script>
    
    <!-- ANIME EFFECTS SCRIPT -->
    <script src="anime-effects.js"></script>
</body>
</html>
