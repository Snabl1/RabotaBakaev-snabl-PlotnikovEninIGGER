<?php
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Проверяем существование таблицы
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_videos (
            video_id INT AUTO_INCREMENT PRIMARY KEY,
            video_url VARCHAR(500) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Получаем активное видео
    $stmt = $pdo->prepare("SELECT * FROM admin_videos WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $video = $stmt->fetch();
    
    if ($video) {
        echo json_encode([
            'success' => true,
            'video' => $video
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'video' => null
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
