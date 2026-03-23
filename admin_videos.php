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

// Добавление видео
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    $video_url = trim($_POST['video_url'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($video_url)) {
        $error = 'Введите URL видео!';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_videos (video_url, is_active, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$video_url, $is_active]);
            $success = 'Видео добавлено! 💕';
        } catch (PDOException $e) {
            // Если таблицы нет - создаем её
            if (strpos($e->getMessage(), 'admin_videos') !== false) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS admin_videos (
                        video_id INT AUTO_INCREMENT PRIMARY KEY,
                        video_url VARCHAR(500) NOT NULL,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");
                $stmt = $pdo->prepare("
                    INSERT INTO admin_videos (video_url, is_active, created_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$video_url, $is_active]);
                $success = 'Таблица создана и видео добавлено! 💕';
            } else {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
}

// Обновление статуса видео
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_video'])) {
    $video_id = $_POST['video_id'] ?? 0;
    $is_active = $_POST['is_active'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE admin_videos SET is_active = ? WHERE video_id = ?");
        $stmt->execute([$is_active, $video_id]);
        $success = 'Статус обновлён! 💕';
    } catch (PDOException $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Удаление видео
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_video'])) {
    $video_id = $_POST['video_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM admin_videos WHERE video_id = ?");
        $stmt->execute([$video_id]);
        $success = 'Видео удалено! 💕';
    } catch (PDOException $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Получаем все видео
try {
    $videos = $pdo->query("SELECT * FROM admin_videos ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // Таблицы нет - создаем
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_videos (
            video_id INT AUTO_INCREMENT PRIMARY KEY,
            video_url VARCHAR(500) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $videos = [];
}

// Активное видео
$active_video = null;
foreach ($videos as $video) {
    if ($video['is_active']) {
        $active_video = $video;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime.css">
    <title>🎬 Управление Видео - ANIME SHOP</title>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = true; $clientName = $_SESSION['client_name'] ?? ''; $clientRole = 'admin'; include 'navbar.php'; ?>
    
    <div class="container">
        <h1>🎬 УПРАВЛЕНИЕ ВИДЕО 💕</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Форма добавления -->
        <form method="POST">
            <h2>➕ Добавить Видео</h2>
            
            <div class="form-group">
                <label>URL Видео (YouTube, Vimeo, или прямой MP4)</label>
                <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required>
                <small style="color: var(--hot-pink);">Поддерживаются: YouTube, Vimeo, прямые ссылки на MP4</small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_active" checked>
                    <span>Сделать активным (будет проигрываться)</span>
                </label>
            </div>
            
            <button type="submit" name="add_video" class="btn btn-success">
                💖 Добавить Видео
            </button>
        </form>
        
        <!-- Список видео -->
        <h2>📺 Список Видео</h2>
        
        <?php if ($active_video): ?>
            <div style="background: linear-gradient(145deg, rgba(255, 183, 197, 0.3), rgba(255, 105, 180, 0.2)); 
                        padding: 20px; border-radius: 20px; border: 3px solid var(--hot-pink); 
                        margin: 20px 0; box-shadow: 0 0 30px rgba(255, 105, 180, 0.4);">
                <h3 style="color: var(--deep-pink);">✅ Активное Видео:</h3>
                <p style="color: var(--hot-pink); font-family: 'Quicksand', sans-serif;">
                    🔗 <?= htmlspecialchars($active_video['video_url']) ?>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Нет активного видео! Добавьте видео для воспроизведения.
            </div>
        <?php endif; ?>
        
        <table>
            <tr>
                <th>ID</th>
                <th>URL</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            
            <?php foreach ($videos as $video): ?>
            <tr>
                <td><span class="price">#<?= $video['video_id'] ?></span></td>
                <td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($video['video_url']) ?>
                </td>
                <td>
                    <span class="status <?= $video['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $video['is_active'] ? '💖 Активно' : '❌ Неактивно' ?>
                    </span>
                </td>
                <td><?= $video['created_at'] ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="video_id" value="<?= $video['video_id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $video['is_active'] ? 0 : 1 ?>">
                        <button type="submit" name="toggle_video" class="btn btn-edit" style="padding: 5px 10px; font-size: 0.8rem;">
                            <?= $video['is_active'] ? '🚫 Отключить' : '✅ Включить' ?>
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить видео?');">
                        <input type="hidden" name="video_id" value="<?= $video['video_id'] ?>">
                        <button type="submit" name="delete_video" class="btn btn-delete" style="padding: 5px 10px; font-size: 0.8rem;">
                            🗑️ Удалить
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (count($videos) === 0): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 30px; color: var(--hot-pink);">
                    🌸 Видео пока нет. Добавьте первое видео! 💕
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <!-- Инструкция -->
        <div style="background: rgba(255, 255, 255, 0.6); padding: 20px; border-radius: 20px; 
                    border: 2px solid var(--sakura-pink); margin-top: 30px;">
            <h3 style="color: var(--deep-pink);">📖 Инструкция:</h3>
            <ul style="color: #5a4a5a; margin-left: 20px; line-height: 1.8;">
                <li>Добавьте URL видео (YouTube, Vimeo или прямая ссылка на MP4)</li>
                <li>Только одно видео может быть активным</li>
                <li>Видео будет проигрываться в углу у всех посетителей</li>
                <li>Видео воспроизводится автоматически со звуком</li>
                <li>Пользователи могут закрыть плеер</li>
            </ul>
        </div>
    </div>
    
    <div class="tank-footer">
        🌸 ANIME SHOP 2026 💕 | Управление Видео
    </div>
    
    <script src="anime-effects.js"></script>
</body>
</html>
