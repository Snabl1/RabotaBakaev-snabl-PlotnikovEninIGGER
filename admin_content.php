<?php
require_once 'functions.php';
requireAuth();

// Только для админов
if (!isset($_SESSION['client_id']) || $_SESSION['client_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$active_tab = $_GET['tab'] ?? 'news';

// Создаем таблицы если их нет
$pdo->exec("
    CREATE TABLE IF NOT EXISTS site_news (
        news_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        icon VARCHAR(20) DEFAULT '🌸',
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS site_forum (
        forum_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        avatar VARCHAR(10) DEFAULT '👤',
        message TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS site_top_clients (
        top_id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        custom_name VARCHAR(100),
        avatar VARCHAR(10) DEFAULT '🎀',
        score INT DEFAULT 0,
        rank_position INT,
        is_active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS site_popular_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        avatar VARCHAR(10) DEFAULT '👧',
        rank_position INT,
        is_active TINYINT(1) DEFAULT 1
    )
");

// ===== НОВОСТИ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'news') {
    if (isset($_POST['add_news'])) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $full_content = trim($_POST['full_content'] ?? '');
        $icon = trim($_POST['icon'] ?? '🌸');
        
        // Загрузка картинки
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/news/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $image = $upload_dir . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $image);
            }
        }
        
        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("INSERT INTO site_news (title, content, full_content, icon, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $content, $full_content ?: $content, $icon, $image]);
            $success = 'Новость добавлена! 💕';
        }
    }
    
    if (isset($_POST['edit_news'])) {
        $news_id = $_POST['news_id'];
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $full_content = trim($_POST['full_content'] ?? '');
        $icon = trim($_POST['icon'] ?? '🌸');
        
        $image_update = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/news/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Удаляем старое изображение
            $old = $pdo->prepare("SELECT image FROM site_news WHERE news_id = ?");
            $old->execute([$news_id]);
            $old_img = $old->fetchColumn();
            if ($old_img && file_exists($old_img)) {
                unlink($old_img);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $image = $upload_dir . uniqid() . '.' . $file_ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $image);
                $image_update = ", image = ?";
            }
        }
        
        if ($image_update) {
            $stmt = $pdo->prepare("UPDATE site_news SET title = ?, content = ?, full_content = ?, icon = ?, updated_at = NOW() $image_update WHERE news_id = ?");
            $stmt->execute([$title, $content, $full_content ?: $content, $icon, $image, $news_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE site_news SET title = ?, content = ?, full_content = ?, icon = ?, updated_at = NOW() WHERE news_id = ?");
            $stmt->execute([$title, $content, $full_content ?: $content, $icon, $news_id]);
        }
        $success = 'Новость обновлена! 💕';
    }
    
    if (isset($_POST['delete_news'])) {
        $news_id = $_POST['news_id'];
        // Удаляем изображение
        $old = $pdo->prepare("SELECT image FROM site_news WHERE news_id = ?");
        $old->execute([$news_id]);
        $old_img = $old->fetchColumn();
        if ($old_img && file_exists($old_img)) {
            unlink($old_img);
        }
        $stmt = $pdo->prepare("DELETE FROM site_news WHERE news_id = ?");
        $stmt->execute([$news_id]);
        $success = 'Новость удалена! 💕';
    }
    
    if (isset($_POST['toggle_news'])) {
        $news_id = $_POST['news_id'];
        $is_active = $_POST['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE site_news SET is_active = ? WHERE news_id = ?");
        $stmt->execute([$is_active, $news_id]);
        $success = 'Статус изменён! 💕';
    }
}

// ===== ФОРУМ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'forum') {
    if (isset($_POST['add_forum'])) {
        $username = trim($_POST['username'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '👤');
        $message = trim($_POST['message'] ?? '');
        
        if (!empty($username) && !empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO site_forum (username, avatar, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $avatar, $message]);
            $success = 'Сообщение добавлено! 💕';
        }
    }
    
    if (isset($_POST['delete_forum'])) {
        $forum_id = $_POST['forum_id'];
        $stmt = $pdo->prepare("DELETE FROM site_forum WHERE forum_id = ?");
        $stmt->execute([$forum_id]);
        $success = 'Сообщение удалено! 💕';
    }
}

// ===== КАТЕГОРИИ ФОРУМА =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'categories') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '💬');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO forum_categories (name, description, icon, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $icon, $sort_order]);
            $success = 'Категория добавлена! 💕';
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '💬');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE forum_categories SET name = ?, description = ?, icon = ?, sort_order = ? WHERE category_id = ?");
        $stmt->execute([$name, $description, $icon, $sort_order, $category_id]);
        $success = 'Категория обновлена! 💕';
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        $stmt = $pdo->prepare("DELETE FROM forum_categories WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $success = 'Категория удалена! 💕';
    }
    
    if (isset($_POST['toggle_category'])) {
        $category_id = $_POST['category_id'];
        $is_active = $_POST['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE forum_categories SET is_active = ? WHERE category_id = ?");
        $stmt->execute([$is_active, $category_id]);
        $success = 'Статус изменён! 💕';
    }
}

// ===== ТОП КЛИЕНТОВ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'top_clients') {
    if (isset($_POST['add_client'])) {
        $client_id = $_POST['client_id'] ?? null;
        $custom_name = trim($_POST['custom_name'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '🎀');
        $score = (int)($_POST['score'] ?? 0);
        $rank_position = (int)($_POST['rank_position'] ?? 0);
        
        $stmt = $pdo->prepare("INSERT INTO site_top_clients (client_id, custom_name, avatar, score, rank_position) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $custom_name, $avatar, $score, $rank_position]);
        $success = 'Клиент добавлен! 💕';
    }
    
    if (isset($_POST['edit_client'])) {
        $top_id = $_POST['top_id'];
        $custom_name = trim($_POST['custom_name'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '🎀');
        $score = (int)($_POST['score'] ?? 0);
        $rank_position = (int)($_POST['rank_position'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE site_top_clients SET custom_name = ?, avatar = ?, score = ?, rank_position = ? WHERE top_id = ?");
        $stmt->execute([$custom_name, $avatar, $score, $rank_position, $top_id]);
        $success = 'Клиент обновлён! 💕';
    }
    
    if (isset($_POST['delete_client'])) {
        $top_id = $_POST['top_id'];
        $stmt = $pdo->prepare("DELETE FROM site_top_clients WHERE top_id = ?");
        $stmt->execute([$top_id]);
        $success = 'Клиент удалён! 💕';
    }
}

// ===== ПОПУЛЯРНЫЕ ТОВАРЫ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'popular') {
    if (isset($_POST['add_item'])) {
        $title = trim($_POST['title'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '👧');
        $rank_position = (int)($_POST['rank_position'] ?? 0);
        
        $stmt = $pdo->prepare("INSERT INTO site_popular_items (title, avatar, rank_position) VALUES (?, ?, ?)");
        $stmt->execute([$title, $avatar, $rank_position]);
        $success = 'Товар добавлен! 💕';
    }
    
    if (isset($_POST['edit_item'])) {
        $item_id = $_POST['item_id'];
        $title = trim($_POST['title'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '👧');
        $rank_position = (int)($_POST['rank_position'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE site_popular_items SET title = ?, avatar = ?, rank_position = ? WHERE item_id = ?");
        $stmt->execute([$title, $avatar, $rank_position, $item_id]);
        $success = 'Товар обновлён! 💕';
    }
    
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['item_id'];
        $stmt = $pdo->prepare("DELETE FROM site_popular_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $success = 'Товар удалён! 💕';
    }
}

// Получаем данные
$news = $pdo->query("SELECT * FROM site_news ORDER BY created_at DESC")->fetchAll();
$forum = $pdo->query("SELECT * FROM site_forum ORDER BY created_at DESC LIMIT 10")->fetchAll();
$forum_categories = $pdo->query("SELECT * FROM forum_categories ORDER BY sort_order ASC")->fetchAll();
$top_clients = $pdo->query("SELECT * FROM site_top_clients ORDER BY rank_position ASC")->fetchAll();
$popular_items = $pdo->query("SELECT * FROM site_popular_items ORDER BY rank_position ASC")->fetchAll();
$all_clients = $pdo->query("SELECT client_id, CONCAT(last_name, ' ', first_name) as name FROM clients ORDER BY client_id DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title>🎨 Админ-панель - ANIME WORLD</title>
    <style>
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .admin-tab {
            padding: 12px 25px;
            background: linear-gradient(145deg, #fff5e4, #ffeaa7);
            border: 2px solid var(--anime-orange);
            border-radius: 15px;
            cursor: pointer;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            color: var(--anime-dark);
            text-decoration: none;
            transition: all 0.3s;
        }
        .admin-tab:hover, .admin-tab.active {
            background: var(--gradient-main);
            color: white;
            transform: translateY(-3px);
        }
        .edit-form {
            background: rgba(255, 255, 255, 0.5);
            padding: 15px;
            border-radius: 15px;
            margin: 10px 0;
        }
        .data-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: linear-gradient(145deg, #fff5e4, #ffeaa7);
            border-radius: 12px;
            margin: 10px 0;
        }
        .data-row img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = true; $clientName = $_SESSION['client_name'] ?? ''; $clientRole = 'admin'; include 'navbar.php'; ?>
    
    <div class="main-container">
        <main class="main-content" style="grid-column: 1 / -1;">
            <div class="content-section">
                <div class="section-header">
                    <span class="section-icons">🎨</span>
                    <h2 class="section-title">АДМИН-ПАНЕЛЬ - УПРАВЛЕНИЕ КОНТЕНТОМ</h2>
                    <span class="section-icons">🎨</span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <!-- ВКЛАДКИ -->
                <div class="admin-tabs">
                    <a href="?tab=news" class="admin-tab <?= $active_tab === 'news' ? 'active' : '' ?>">📰 Новости</a>
                    <a href="?tab=forum" class="admin-tab <?= $active_tab === 'forum' ? 'active' : '' ?>">💬 Форум</a>
                    <a href="?tab=categories" class="admin-tab <?= $active_tab === 'categories' ? 'active' : '' ?>">📁 Категории</a>
                    <a href="?tab=top_clients" class="admin-tab <?= $active_tab === 'top_clients' ? 'active' : '' ?>">⭐ Топ Клиентов</a>
                    <a href="?tab=popular" class="admin-tab <?= $active_tab === 'popular' ? 'active' : '' ?>">🔥 Популярное</a>
                </div>
                
                <!-- НОВОСТИ -->
                <?php if ($active_tab === 'news'): ?>
                <h3 style="color: var(--anime-red); margin-bottom: 15px;">📰 Управление Новостями</h3>

                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <h4>➕ Добавить Новость</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="text" name="title" placeholder="Заголовок новости" required style="width: 100%;">
                        <input type="text" name="icon" placeholder="🌸" style="width: 80px;">
                    </div>
                    <textarea name="content" placeholder="Краткое описание (для главной)" rows="2" style="margin-top: 10px; width: 100%;"></textarea>
                    <textarea name="full_content" placeholder="Полный текст новости" rows="5" style="margin-top: 10px; width: 100%;"></textarea>
                    <div style="margin-top: 15px;">
                        <label style="display: block; margin-bottom: 5px; color: var(--anime-red); font-weight: 700;">📷 Изображение:</label>
                        <input type="file" name="image" accept="image/*" style="width: 100%;">
                    </div>
                    <button type="submit" name="add_news" class="btn btn-success" style="margin-top: 10px;">💖 Добавить Новость</button>
                </form>
                
                <h4 style="margin-top: 20px;">📋 Список Новостей</h4>
                <?php foreach ($news as $item): ?>
                <div class="data-row" style="align-items: flex-start;">
                    <span style="font-size: 1.5rem;"><?= $item['icon'] ?></span>
                    <div style="flex: 1;">
                        <strong style="color: var(--anime-red); font-size: 1.1rem;"><?= htmlspecialchars($item['title']) ?></strong>
                        <p style="margin: 5px 0; font-size: 0.9rem;"><?= htmlspecialchars($item['content']) ?></p>
                        <?php if ($item['image']): ?>
                        <img src="<?= $item['image'] ?>" alt="News image" style="max-width: 150px; max-height: 100px; border-radius: 10px; margin-top: 5px; border: 2px solid var(--anime-orange);">
                        <?php endif; ?>
                        <small style="color: var(--anime-purple);"><?= $item['created_at'] ?> | 👁️ <?= $item['views'] ?></small>
                    </div>
                    <span class="status <?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $item['is_active'] ? '✅ Активна' : '❌ Скрыта' ?>
                    </span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="news_id" value="<?= $item['news_id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $item['is_active'] ?>">
                        <button type="submit" name="toggle_news" class="btn btn-edit" style="padding: 5px 10px; font-size: 0.8rem;">
                            <?= $item['is_active'] ? '🚫 Скрыть' : '✅ Показать' ?>
                        </button>
                    </form>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить новость?');">
                        <input type="hidden" name="news_id" value="<?= $item['news_id'] ?>">
                        <button type="submit" name="delete_news" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- ФОРУМ -->
                <?php if ($active_tab === 'forum'): ?>
                <h3 style="color: var(--anime-blue); margin-bottom: 15px;">💬 Управление Форумом</h3>
                
                <form method="POST" class="edit-form">
                    <h4>➕ Добавить Сообщение</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 15px;">
                        <input type="text" name="username" placeholder="Имя пользователя" required>
                        <input type="text" name="avatar" placeholder="👤" style="width: 80px;">
                        <input type="text" name="message" placeholder="Текст сообщения" required>
                    </div>
                    <button type="submit" name="add_forum" class="btn btn-success" style="margin-top: 10px;">💖 Добавить Сообщение</button>
                </form>
                
                <h4 style="margin-top: 20px;">📋 Сообщения Форума</h4>
                <?php foreach ($forum as $item): ?>
                <div class="data-row">
                    <span style="font-size: 2rem;"><?= $item['avatar'] ?></span>
                    <div style="flex: 1;">
                        <strong style="color: var(--anime-purple);"><?= htmlspecialchars($item['username']) ?></strong>
                        <p style="margin: 5px 0;"><?= htmlspecialchars($item['message']) ?></p>
                        <small style="color: var(--anime-blue);"><?= $item['created_at'] ?></small>
                    </div>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить сообщение?');">
                        <input type="hidden" name="forum_id" value="<?= $item['forum_id'] ?>">
                        <button type="submit" name="delete_forum" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- КАТЕГОРИИ ФОРУМА -->
                <?php if ($active_tab === 'categories'): ?>
                <h3 style="color: var(--anime-blue); margin-bottom: 15px;">📁 Управление Категориями Форума</h3>

                <form method="POST" class="edit-form">
                    <h4>➕ Добавить Категорию</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="text" name="name" placeholder="Название категории" required style="width: 100%;">
                        <input type="text" name="icon" placeholder="💬" style="width: 80px;">
                    </div>
                    <textarea name="description" placeholder="Описание категории" rows="2" style="margin-top: 10px; width: 100%;"></textarea>
                    <input type="number" name="sort_order" placeholder="Порядок отображения (0, 1, 2...)" value="0" style="margin-top: 10px; width: 100%;">
                    <button type="submit" name="add_category" class="btn btn-success" style="margin-top: 10px;">💖 Добавить Категорию</button>
                </form>

                <h4 style="margin-top: 20px;">📋 Список Категорий</h4>
                <?php foreach ($forum_categories as $item): ?>
                <div class="data-row">
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--anime-gold);">#<?= $item['sort_order'] ?></span>
                    <span style="font-size: 2rem;"><?= $item['icon'] ?></span>
                    <div style="flex: 1;">
                        <strong style="color: var(--anime-red); font-size: 1.1rem;"><?= htmlspecialchars($item['name']) ?></strong>
                        <p style="margin: 5px 0; font-size: 0.9rem;"><?= htmlspecialchars($item['description']) ?></p>
                    </div>
                    <span class="status <?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $item['is_active'] ? '✅ Активна' : '❌ Скрыта' ?>
                    </span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="category_id" value="<?= $item['category_id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $item['is_active'] ?>">
                        <button type="submit" name="toggle_category" class="btn btn-edit" style="padding: 5px 10px; font-size: 0.8rem;">
                            <?= $item['is_active'] ? '🚫 Скрыть' : '✅ Показать' ?>
                        </button>
                    </form>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить категорию?');">
                        <input type="hidden" name="category_id" value="<?= $item['category_id'] ?>">
                        <button type="submit" name="delete_category" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- ТОП КЛИЕНТОВ -->
                <?php if ($active_tab === 'top_clients'): ?>
                <h3 style="color: var(--anime-gold); margin-bottom: 15px;">⭐ Управление Топ Клиентов</h3>
                
                <form method="POST" class="edit-form">
                    <h4>➕ Добавить Клиента</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                        <select name="client_id">
                            <option value="">-- Выбрать клиента --</option>
                            <?php foreach ($all_clients as $c): ?>
                            <option value="<?= $c['client_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="custom_name" placeholder="Или своё имя">
                        <input type="text" name="avatar" placeholder="🎀" style="width: 80px;">
                        <input type="number" name="score" placeholder="Очки" value="0">
                    </div>
                    <input type="number" name="rank_position" placeholder="Позиция в рейтинге (1, 2, 3...)" style="margin-top: 10px; width: 100%;">
                    <button type="submit" name="add_client" class="btn btn-success" style="margin-top: 10px;">💖 Добавить Клиента</button>
                </form>
                
                <h4 style="margin-top: 20px;">📋 Топ Клиентов</h4>
                <?php foreach ($top_clients as $item): ?>
                <div class="data-row">
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--anime-gold);">#<?= $item['rank_position'] ?></span>
                    <span style="font-size: 2rem;"><?= $item['avatar'] ?></span>
                    <div style="flex: 1;">
                        <strong style="color: var(--anime-red);"><?= htmlspecialchars($item['custom_name']) ?></strong>
                        <p style="margin: 5px 0;">Очки: <strong style="color: var(--anime-gold);"><?= $item['score'] ?></strong></p>
                    </div>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить клиента?');">
                        <input type="hidden" name="top_id" value="<?= $item['top_id'] ?>">
                        <button type="submit" name="delete_client" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- ПОПУЛЯРНЫЕ ТОВАРЫ -->
                <?php if ($active_tab === 'popular'): ?>
                <h3 style="color: var(--anime-pink); margin-bottom: 15px;">🔥 Управление Популярными Товарами</h3>
                
                <form method="POST" class="edit-form">
                    <h4>➕ Добавить Товар</h4>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                        <input type="text" name="title" placeholder="Название товара" required>
                        <input type="text" name="avatar" placeholder="👧" style="width: 80px;">
                        <input type="number" name="rank_position" placeholder="Позиция" required>
                    </div>
                    <button type="submit" name="add_item" class="btn btn-success" style="margin-top: 10px;">💖 Добавить Товар</button>
                </form>
                
                <h4 style="margin-top: 20px;">📋 Популярные Товары</h4>
                <?php foreach ($popular_items as $item): ?>
                <div class="data-row">
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--anime-gold);">#<?= $item['rank_position'] ?></span>
                    <span style="font-size: 2rem;"><?= $item['avatar'] ?></span>
                    <div style="flex: 1;">
                        <strong style="color: var(--anime-red);"><?= htmlspecialchars($item['title']) ?></strong>
                    </div>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить товар?');">
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                        <button type="submit" name="delete_item" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Админ-панель
    </div>
    
    <script src="anime-effects.js"></script>
</body>
</html>
