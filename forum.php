<?php
require_once 'functions.php';
requireAuth();

$error = '';
$success = '';
$active_forum_id = $_GET['forum'] ?? null;

// Создаем таблицы если их нет
$pdo->exec("
    CREATE TABLE IF NOT EXISTS forum_categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        icon VARCHAR(20) DEFAULT '💬',
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Добавляем дефолтные категории если их нет
$catCount = $pdo->query("SELECT COUNT(*) FROM forum_categories")->fetchColumn();
if ($catCount == 0) {
    $pdo->exec("
        INSERT INTO forum_categories (name, description, icon, sort_order) VALUES
        ('🌸 Общение', 'Общие темы для всех братов', '💬', 1),
        ('🎮 Игры', 'Обсуждение игр и гейминга', '🎮', 2),
        ('💕 Любовь и отношения', 'Личные темы', '💖', 3),
        ('📦 Заказы и доставки', 'Вопросы по заказам', '📦', 4),
        ('🔧 Технический раздел', 'Помощь и поддержка', '🔧', 5),
        ('🎌 Аниме и манга', 'Обсуждение аниме', '📺', 6)
    ");
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS forum_topics (
        topic_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        is_locked TINYINT(1) DEFAULT 0,
        is_pinned TINYINT(1) DEFAULT 0,
        views INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES forum_categories(category_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES clients(client_id) ON DELETE CASCADE
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS forum_posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        topic_id INT,
        user_id INT,
        content TEXT NOT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (topic_id) REFERENCES forum_topics(topic_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES clients(client_id) ON DELETE CASCADE
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS user_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNIQUE,
        nickname VARCHAR(100),
        avatar VARCHAR(500),
        bio TEXT,
        signature TEXT,
        birthdate DATE,
        location VARCHAR(255),
        website VARCHAR(255),
        social_vk VARCHAR(255),
        social_tg VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
    )
");

// ===== ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $client_id = $_SESSION['client_id'];
    $nickname = trim($_POST['nickname'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $signature = trim($_POST['signature'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $social_vk = trim($_POST['social_vk'] ?? '');
    $social_tg = trim($_POST['social_tg'] ?? '');
    
    // Загрузка аватарки
    $avatar = '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $avatar = $upload_dir . uniqid() . '.' . $file_ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar);
        }
    }
    
    // Проверяем есть ли профиль
    $profile = $pdo->prepare("SELECT profile_id FROM user_profiles WHERE client_id = ?");
    $profile->execute([$client_id]);
    $exists = $profile->fetch();
    
    if ($exists) {
        if ($avatar) {
            $stmt = $pdo->prepare("UPDATE user_profiles SET nickname = ?, bio = ?, signature = ?, birthdate = ?, location = ?, website = ?, social_vk = ?, social_tg = ?, avatar = ?, updated_at = NOW() WHERE client_id = ?");
            $stmt->execute([$nickname, $bio, $signature, $birthdate, $location, $website, $social_vk, $social_tg, $avatar, $client_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE user_profiles SET nickname = ?, bio = ?, signature = ?, birthdate = ?, location = ?, website = ?, social_vk = ?, social_tg = ?, updated_at = NOW() WHERE client_id = ?");
            $stmt->execute([$nickname, $bio, $signature, $birthdate, $location, $website, $social_vk, $social_tg, $client_id]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (client_id, nickname, avatar, bio, signature, birthdate, location, website, social_vk, social_tg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $nickname, $avatar, $bio, $signature, $birthdate, $location, $website, $social_vk, $social_tg]);
    }
    
    $success = 'Профиль обновлён! 💕';
}

// ===== НОВАЯ ТЕМКА =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_topic'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    
    if (!empty($title) && !empty($content) && $category_id) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO forum_topics (category_id, user_id, title) VALUES (?, ?, ?)");
            $stmt->execute([$category_id, $_SESSION['client_id'], $title]);
            $topic_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$topic_id, $_SESSION['client_id'], $content]);
            
            $pdo->commit();
            header("Location: ?forum=" . $topic_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// ===== НОВЫЙ ПОСТ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    $topic_id = $_POST['topic_id'] ?? null;
    $content = trim($_POST['content'] ?? '');
    
    if (!empty($content) && $topic_id) {
        $stmt = $pdo->prepare("INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$topic_id, $_SESSION['client_id'], $content]);
        
        $stmt = $pdo->prepare("UPDATE forum_topics SET updated_at = NOW() WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        
        header("Location: ?forum=" . $topic_id);
        exit;
    }
}

// ===== УДАЛЕНИЕ ПОСТА (АДМИН) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $_SESSION['client_role'] === 'admin') {
    $post_id = $_POST['post_id'];
    $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $success = 'Пост удалён! 💕';
}

// ===== БАН ПОЛЬЗОВАТЕЛЯ (АДМИН) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_user']) && $_SESSION['client_role'] === 'admin') {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE clients SET is_active = 0 WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $success = 'Пользователь забанен! 💕';
}

// Получаем данные
$forum_categories = $pdo->query("SELECT * FROM forum_categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
$recent_topics = $pdo->query("
    SELECT ft.*, c.last_name, c.first_name, 
           (SELECT COUNT(*) FROM forum_posts WHERE topic_id = ft.topic_id) as post_count
    FROM forum_topics ft
    LEFT JOIN clients c ON ft.user_id = c.client_id
    ORDER BY ft.updated_at DESC LIMIT 10
")->fetchAll();

// Профиль пользователя
$user_profile = null;
if (isset($_SESSION['client_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE client_id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    $user_profile = $stmt->fetch();
}

// Просмотр темы
$topic = null;
$posts = [];
if ($active_forum_id) {
    $stmt = $pdo->prepare("
        SELECT ft.*, fc.name as category_name, c.last_name, c.first_name, c.username
        FROM forum_topics ft
        LEFT JOIN forum_categories fc ON ft.category_id = fc.category_id
        LEFT JOIN clients c ON ft.user_id = c.client_id
        WHERE ft.topic_id = ?
    ");
    $stmt->execute([$active_forum_id]);
    $topic = $stmt->fetch();
    
    if ($topic) {
        $pdo->prepare("UPDATE forum_topics SET views = views + 1 WHERE topic_id = ?")->execute([$active_forum_id]);
        
        $posts = $pdo->query("
            SELECT fp.*, c.last_name, c.first_name, c.username, c.role,
                   up.nickname, up.avatar, up.signature, up.bio
            FROM forum_posts fp
            LEFT JOIN clients c ON fp.user_id = c.client_id
            LEFT JOIN user_profiles up ON fp.user_id = up.client_id
            WHERE fp.topic_id = $active_forum_id AND fp.is_deleted = 0
            ORDER BY fp.created_at ASC
        ")->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title>💬 Форум - ANIME WORLD</title>
    <style>
        .forum-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .forum-category {
            background: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,245,228,0.95));
            border-radius: 20px;
            border: 3px solid var(--anime-orange);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .forum-category-header {
            background: var(--gradient-sidebar);
            padding: 15px 20px;
            color: white;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .forum-topic {
            padding: 15px 20px;
            border-bottom: 2px solid var(--anime-pink);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .forum-topic:hover {
            background: rgba(255, 183, 197, 0.2);
        }
        .forum-topic:last-child {
            border-bottom: none;
        }
        .topic-icon {
            font-size: 2rem;
        }
        .topic-info {
            flex: 1;
        }
        .topic-title {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            color: var(--anime-dark);
            font-size: 1.1rem;
        }
        .topic-meta {
            font-size: 0.85rem;
            color: var(--anime-purple);
            margin-top: 5px;
        }
        .post-container {
            background: rgba(255,255,255,0.8);
            border-radius: 15px;
            border: 2px solid var(--anime-pink);
            padding: 20px;
            margin-bottom: 20px;
        }
        .post-author {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed var(--anime-pink);
            margin-bottom: 15px;
        }
        .post-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--anime-orange);
            object-fit: cover;
        }
        .author-info {
            flex: 1;
        }
        .author-name {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--anime-red);
        }
        .author-role {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 5px;
        }
        .role-admin {
            background: linear-gradient(145deg, #ff6b35, #ff4757);
            color: white;
        }
        .role-user {
            background: linear-gradient(145deg, #a55eea, #8854d0);
            color: white;
        }
        .post-content {
            font-family: 'Comic Neue', cursive;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--anime-dark);
            white-space: pre-wrap;
        }
        .post-signature {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed var(--anime-pink);
            font-size: 0.9rem;
            color: var(--anime-purple);
            font-style: italic;
        }
        .profile-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,245,228,0.95));
            border-radius: 25px;
            border: 3px solid var(--anime-pink);
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid var(--anime-orange);
            object-fit: cover;
            margin-bottom: 15px;
        }
        .profile-nickname {
            font-family: 'Fredoka', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--anime-red);
            margin-bottom: 10px;
        }
        .profile-bio {
            font-family: 'Comic Neue', cursive;
            font-size: 1rem;
            color: var(--anime-dark);
            margin-bottom: 20px;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-box {
            background: rgba(255,255,255,0.7);
            padding: 15px;
            border-radius: 15px;
            border: 2px solid var(--anime-orange);
        }
        .stat-number {
            font-family: 'Fredoka', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--anime-red);
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--anime-purple);
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = true; $clientName = $_SESSION['client_name'] ?? ''; $clientRole = $_SESSION['client_role'] ?? ''; include 'navbar.php'; ?>
    
    <div class="main-container">
        <main class="main-content" style="grid-column: 1 / -1;">
            <div class="content-section">
                <div class="section-header">
                    <span class="section-icons">💬</span>
                    <h2 class="section-title">ФОРУМ</h2>
                    <span class="section-icons">💬</span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if ($active_forum_id && $topic): ?>
                    <!-- ПРОСМОТР ТЕМЫ -->
                    <div style="margin-bottom: 20px;">
                        <a href="forum.php" class="btn btn-edit">← Назад к форуму</a>
                    </div>
                    
                    <h3 style="color: var(--anime-red); margin-bottom: 20px;"><?= htmlspecialchars($topic['title']) ?></h3>
                    
                    <?php foreach ($posts as $post): ?>
                    <div class="post-container">
                        <div class="post-author">
                            <img src="<?= $post['avatar'] ?: 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👤</text></svg>' ?>" 
                                 alt="Avatar" class="post-avatar">
                            <div class="author-info">
                                <div class="author-name"><?= htmlspecialchars($post['nickname'] ?: $post['last_name'] . ' ' . $post['first_name']) ?></div>
                                <div class="author-role <?= $post['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                    <?= $post['role'] === 'admin' ? '👑 АДМИН' : '👤 Пользователь' ?>
                                </div>
                                <?php if ($_SESSION['client_role'] === 'admin' && $post['user_id'] != $_SESSION['client_id']): ?>
                                <form method="POST" style="display: inline; margin-top: 5px;">
                                    <input type="hidden" name="user_id" value="<?= $post['user_id'] ?>">
                                    <button type="submit" name="ban_user" class="btn btn-delete" style="padding: 3px 8px; font-size: 0.7rem;">🚫 Бан</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--anime-purple);">
                                <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>
                        
                        <?php if ($post['signature']): ?>
                        <div class="post-signature"><?= htmlspecialchars($post['signature']) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['client_role'] === 'admin'): ?>
                        <form method="POST" style="margin-top: 15px;" onsubmit="return confirm('Удалить пост?');">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <button type="submit" name="delete_post" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">🗑️ Удалить</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- ДОБАВИТЬ ОТВЕТ -->
                    <?php if (!$topic['is_locked']): ?>
                    <div class="content-section" style="margin-top: 30px;">
                        <h4 style="color: var(--anime-red); margin-bottom: 15px;">💬 Ответить</h4>
                        <form method="POST">
                            <input type="hidden" name="topic_id" value="<?= $topic['topic_id'] ?>">
                            <textarea name="content" rows="5" placeholder="Ваш ответ..." required style="width: 100%;"></textarea>
                            <button type="submit" name="add_post" class="btn btn-success" style="margin-top: 10px;">💖 Отправить</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">🔒 Тема закрыта для ответов</div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- СПИСОК ФОРУМА -->
                    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
                        <div>
                            <!-- КАТЕГОРИИ -->
                            <?php if (count($forum_categories) > 0): ?>
                                <?php foreach ($forum_categories as $cat): ?>
                                <?php
                                // Получаем количество тем в категории
                                $topic_count = $pdo->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = ?");
                                $topic_count->execute([$cat['category_id']]);
                                $count = $topic_count->fetchColumn();
                                
                                // Получаем последнюю тему
                                $last_topic = $pdo->prepare("SELECT title, created_at FROM forum_topics WHERE category_id = ? ORDER BY created_at DESC LIMIT 1");
                                $last_topic->execute([$cat['category_id']]);
                                $last = $last_topic->fetch();
                                ?>
                                <div class="forum-category" onclick="document.getElementById('cat-<?= $cat['category_id'] ?>').scrollIntoView({behavior: 'smooth'})">
                                    <div class="forum-category-header">
                                        <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                                        <span style="float: right; opacity: 0.8; font-size: 0.9rem;">📋 Тем: <?= $count ?></span>
                                    </div>
                                    <div style="padding: 15px 20px; background: rgba(255,255,255,0.7);">
                                        <p style="color: var(--anime-dark); margin-bottom: 10px;"><?= htmlspecialchars($cat['description']) ?></p>
                                        <?php if ($last): ?>
                                        <div style="font-size: 0.85rem; color: var(--anime-purple);">
                                            📝 Последняя: <strong><?= htmlspecialchars($last['title']) ?></strong>
                                            <span style="margin-left: 15px;">🕐 <?= date('d.m.Y H:i', strtotime($last['created_at'])) ?></span>
                                        </div>
                                        <?php else: ?>
                                        <div style="font-size: 0.85rem; color: var(--anime-purple); font-style: italic;">
                                            Тем пока нет
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">⚠️ Категорий пока нет</div>
                            <?php endif; ?>
                            
                            <!-- ПОСЛЕДНИЕ ТЕМЫ -->
                            <div class="content-section" style="margin-top: 20px;">
                                <h3 style="color: var(--anime-red); margin-bottom: 15px;">🔥 Последние темы</h3>
                                <?php if (count($recent_topics) > 0): ?>
                                    <?php foreach ($recent_topics as $topic_item): ?>
                                    <div class="forum-topic" onclick="window.location.href='?forum=<?= $topic_item['topic_id'] ?>'">
                                        <div class="topic-icon">💬</div>
                                        <div class="topic-info">
                                            <div class="topic-title"><?= htmlspecialchars($topic_item['title']) ?></div>
                                            <div class="topic-meta">
                                                Автор: <?= htmlspecialchars($topic_item['last_name'] . ' ' . $topic_item['first_name']) ?> | 
                                                Ответов: <?= $topic_item['post_count'] ?> | 
                                                <?= date('d.m.Y H:i', strtotime($topic_item['updated_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="padding: 20px; text-align: center; color: var(--anime-purple);">
                                        Тем пока нет. Создайте первую! 💕
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- СОЗДАТЬ ТЕМУ -->
                            <div class="content-section" style="margin-top: 20px;">
                                <h3 style="color: var(--anime-red); margin-bottom: 15px;">📝 Создать тему</h3>
                                <form method="POST">
                                    <input type="text" name="title" placeholder="Заголовок темы" required style="width: 100%; margin-bottom: 10px;">
                                    <select name="category_id" required style="width: 100%; margin-bottom: 10px;">
                                        <option value="">-- Выберите категорию --</option>
                                        <?php foreach ($forum_categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="content" placeholder="Содержимое первого сообщения" rows="5" required style="width: 100%;"></textarea>
                                    <button type="submit" name="create_topic" class="btn btn-success" style="margin-top: 10px;">💖 Создать тему</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- ПРАВЫЙ САЙДБАР -->
                        <div>
                            <!-- ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ -->
                            <div class="profile-card">
                                <h3 style="color: var(--anime-red); margin-bottom: 15px;">👤 Мой Профиль</h3>
                                <?php if ($user_profile): ?>
                                    <img src="<?= $user_profile['avatar'] ?: 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👤</text></svg>' ?>" 
                                         alt="Avatar" class="profile-avatar">
                                    <div class="profile-nickname"><?= htmlspecialchars($user_profile['nickname'] ?: $clientName) ?></div>
                                    <div class="profile-bio"><?= htmlspecialchars($user_profile['bio'] ?: 'Биография не указана') ?></div>
                                    <a href="profile.php" class="btn btn-edit">✎ Редактировать</a>
                                <?php else: ?>
                                    <p style="color: var(--anime-purple);">Профиль не заполнен</p>
                                    <a href="profile.php" class="btn btn-success">💖 Создать профиль</a>
                                <?php endif; ?>
                                
                                <div class="profile-stats">
                                    <div class="stat-box">
                                        <div class="stat-number"><?= count($recent_topics) ?></div>
                                        <div class="stat-label">Тем</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Постов</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Репутация</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Форум
    </div>
    
    <script src="anime-effects.js"></script>
</body>
</html>
