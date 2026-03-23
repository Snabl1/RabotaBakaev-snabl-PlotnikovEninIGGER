<?php
require_once 'functions.php';

$news_id = $_GET['id'] ?? null;

if (!$news_id) {
    header("Location: index.php");
    exit;
}

// Создаем таблицу если нет
$pdo->exec("
    CREATE TABLE IF NOT EXISTS site_news (
        news_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        full_content TEXT,
        icon VARCHAR(20) DEFAULT '🌸',
        image VARCHAR(500),
        is_active TINYINT(1) DEFAULT 1,
        views INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Получаем новость
$stmt = $pdo->prepare("SELECT * FROM site_news WHERE news_id = ? AND is_active = 1");
$stmt->execute([$news_id]);
$news = $stmt->fetch();

if (!$news) {
    header("Location: index.php");
    exit;
}

// Увеличиваем счётчик просмотров
$pdo->prepare("UPDATE site_news SET views = views + 1 WHERE news_id = ?")->execute([$news_id]);

// Получаем другие новости
$other_news = $pdo->query("SELECT * FROM site_news WHERE is_active = 1 AND news_id != $news_id ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title><?= htmlspecialchars($news['title']) ?> - ANIME WORLD</title>
    <style>
        .news-full-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .news-full-header {
            background: var(--gradient-main);
            border-radius: 25px 25px 0 0;
            padding: 40px;
            text-align: center;
            color: white;
        }
        .news-full-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .news-full-title {
            font-family: 'Fredoka', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        .news-full-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            opacity: 0.9;
        }
        .news-full-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 0;
        }
        .news-full-content {
            background: rgba(255,255,255,0.9);
            padding: 40px;
            font-family: 'Comic Neue', cursive;
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--anime-dark);
        }
        .news-full-content p {
            margin-bottom: 20px;
        }
        .news-sidebar {
            background: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,245,228,0.95));
            border-radius: 20px;
            border: 3px solid var(--anime-pink);
            padding: 20px;
            margin-top: 30px;
        }
        .news-item-mini {
            display: flex;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
            background: rgba(255,255,255,0.7);
            border-radius: 15px;
            border: 2px solid var(--anime-orange);
            cursor: pointer;
            transition: all 0.3s;
        }
        .news-item-mini:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        .news-item-mini:last-child {
            margin-bottom: 0;
        }
        .news-icon-mini {
            font-size: 2.5rem;
        }
        .news-info-mini {
            flex: 1;
        }
        .news-title-mini {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: var(--anime-dark);
            margin-bottom: 5px;
        }
        .news-date-mini {
            font-size: 0.85rem;
            color: var(--anime-purple);
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = isset($_SESSION['client_id']); $clientName = $_SESSION['client_name'] ?? ''; $clientRole = $_SESSION['client_role'] ?? ''; include 'navbar.php'; ?>
    
    <div class="main-container">
        <main class="main-content" style="grid-column: 1 / -1;">
            <div class="news-full-container">
                <div class="content-section" style="padding: 0; overflow: hidden;">
                    <div class="news-full-header">
                        <div class="news-full-icon"><?= $news['icon'] ?></div>
                        <h1 class="news-full-title"><?= htmlspecialchars($news['title']) ?></h1>
                        <div class="news-full-meta">
                            <span>📅 <?= date('d.m.Y', strtotime($news['created_at'])) ?></span>
                            <span>👁️ <?= $news['views'] ?> просмотров</span>
                        </div>
                    </div>
                    
                    <?php if ($news['image']): ?>
                    <img src="<?= $news['image'] ?>" alt="<?= htmlspecialchars($news['title']) ?>" class="news-full-image">
                    <?php endif; ?>
                    
                    <div class="news-full-content">
                        <?= nl2br(htmlspecialchars($news['full_content'] ?: $news['content'])) ?>
                        
                        <div style="margin-top: 30px; padding-top: 20px; border-top: 3px dashed var(--anime-pink); text-align: center;">
                            <a href="index.php" class="btn btn-edit">← На главную</a>
                            <a href="news.php" class="btn btn-success">📰 Все новости</a>
                        </div>
                    </div>
                </div>
                
                <!-- ДРУГИЕ НОВОСТИ -->
                <?php if (count($other_news) > 0): ?>
                <div class="news-sidebar">
                    <h3 style="color: var(--anime-red); margin-bottom: 20px; text-align: center;">📰 Другие новости</h3>
                    <?php foreach ($other_news as $item): ?>
                    <div class="news-item-mini" onclick="window.location.href='news_view.php?id=<?= $item['news_id'] ?>'">
                        <div class="news-icon-mini"><?= $item['icon'] ?></div>
                        <div class="news-info-mini">
                            <div class="news-title-mini"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="news-date-mini">📅 <?= date('d.m.Y', strtotime($item['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Новости
    </div>
    
    <script src="anime-effects.js"></script>
</body>
</html>
