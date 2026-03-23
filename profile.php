<?php
require_once 'functions.php';
requireAuth();

$error = '';
$success = '';
$client_id = $_SESSION['client_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// Получаем или создаем профиль
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE client_id = ?");
$stmt->execute([$client_id]);
$profile = $stmt->fetch();

if (!$profile) {
    $pdo->prepare("INSERT INTO user_profiles (client_id) VALUES (?)")->execute([$client_id]);
    $stmt->execute([$client_id]);
    $profile = $stmt->fetch();
}

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nickname = trim($_POST['nickname'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $signature = trim($_POST['signature'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $social_vk = trim($_POST['social_vk'] ?? '');
    $social_tg = trim($_POST['social_tg'] ?? '');
    
    $avatar = $profile['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Удаляем старую аватарку
        if ($profile['avatar'] && file_exists($profile['avatar'])) {
            unlink($profile['avatar']);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $avatar = $upload_dir . uniqid() . '.' . $file_ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar);
        }
    }
    
    $stmt = $pdo->prepare("
        UPDATE user_profiles SET 
            nickname = ?, bio = ?, signature = ?, birthdate = ?, 
            location = ?, website = ?, social_vk = ?, social_tg = ?, 
            avatar = ?, updated_at = NOW() 
        WHERE client_id = ?
    ");
    $stmt->execute([
        $nickname, $bio, $signature, $birthdate, 
        $location, $website, $social_vk, $social_tg, 
        $avatar, $client_id
    ]);
    
    $success = 'Профиль обновлён! 💕';
    
    // Обновляем данные
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $profile = $stmt->fetch();
}

// Смена пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($current_password, $client['password'])) {
        $error = 'Неверный текущий пароль!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Пароли не совпадают!';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE clients SET password = ? WHERE client_id = ?")->execute([$hashed, $client_id]);
        $success = 'Пароль изменён! 💕';
    }
}

// Статистика пользователя
$topic_count = $pdo->query("SELECT COUNT(*) FROM forum_topics WHERE user_id = $client_id")->fetchColumn();
$post_count = $pdo->query("SELECT COUNT(*) FROM forum_posts WHERE user_id = $client_id")->fetchColumn();
$order_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE client_id = $client_id")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title>👤 Профиль - ANIME WORLD</title>
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .profile-header {
            background: var(--gradient-main);
            border-radius: 25px 25px 0 0;
            padding: 40px;
            text-align: center;
            color: white;
        }
        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            object-fit: cover;
            margin-bottom: 20px;
        }
        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .profile-tab {
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
        .profile-tab:hover, .profile-tab.active {
            background: var(--gradient-main);
            color: white;
        }
        .profile-section {
            display: none;
        }
        .profile-section.active {
            display: block;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .stat-card-mini {
            background: linear-gradient(145deg, rgba(255,255,255,0.8), rgba(255,245,228,0.9));
            padding: 20px;
            border-radius: 15px;
            border: 2px solid var(--anime-orange);
            text-align: center;
        }
        .stat-number-mini {
            font-family: 'Fredoka', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--anime-red);
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = true; $clientName = $_SESSION['client_name'] ?? ''; $clientRole = $_SESSION['client_role'] ?? ''; include 'navbar.php'; ?>
    
    <div class="main-container">
        <main class="main-content" style="grid-column: 1 / -1;">
            <div class="profile-container">
                <div class="content-section" style="padding: 0; overflow: hidden;">
                    <div class="profile-header">
                        <img src="<?= $profile['avatar'] ?: 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👤</text></svg>' ?>" 
                             alt="Avatar" class="profile-avatar-large">
                        <h2 style="font-family: 'Fredoka', sans-serif; font-size: 2rem; margin-bottom: 10px;">
                            <?= htmlspecialchars($profile['nickname'] ?: $clientName) ?>
                        </h2>
                        <p style="opacity: 0.9;"><?= htmlspecialchars($profile['bio'] ?: 'Пользователь ANIME WORLD') ?></p>
                    </div>
                    
                    <div style="padding: 30px;">
                        <?php if ($error): ?>
                            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <!-- ВКЛАДКИ -->
                        <div class="profile-tabs">
                            <a href="#" class="profile-tab active" onclick="showSection('edit'); return false;">✎ Редактировать</a>
                            <a href="#" class="profile-tab" onclick="showSection('password'); return false;">🔐 Сменить пароль</a>
                            <a href="#" class="profile-tab" onclick="showSection('stats'); return false;">📊 Статистика</a>
                            <a href="forum.php" class="profile-tab">💬 Форум</a>
                        </div>
                        
                        <!-- РЕДАКТИРОВАНИЕ -->
                        <div id="editSection" class="profile-section active">
                            <h3 style="color: var(--anime-red); margin-bottom: 20px;">👤 Редактирование профиля</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="info-grid">
                                    <div class="form-group">
                                        <label>📛 Никнейм</label>
                                        <input type="text" name="nickname" value="<?= htmlspecialchars($profile['nickname'] ?? '') ?>" placeholder="Ваш никнейм">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>📷 Аватарка</label>
                                        <input type="file" name="avatar" accept="image/*" style="padding: 10px;">
                                        <?php if ($profile['avatar']): ?>
                                            <img src="<?= $profile['avatar'] ?>" alt="Current avatar" style="width: 50px; height: 50px; border-radius: 50%; margin-top: 10px;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>📝 О себе</label>
                                    <textarea name="bio" rows="3" placeholder="Расскажите о себе..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>✎ Подпись на форуме</label>
                                    <textarea name="signature" rows="2" placeholder="Ваша подпись..."><?= htmlspecialchars($profile['signature'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="info-grid">
                                    <div class="form-group">
                                        <label>📅 Дата рождения</label>
                                        <input type="date" name="birthdate" value="<?= $profile['birthdate'] ?? '' ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>📍 Город</label>
                                        <input type="text" name="location" value="<?= htmlspecialchars($profile['location'] ?? '') ?>" placeholder="Ваш город">
                                    </div>
                                </div>
                                
                                <div class="info-grid">
                                    <div class="form-group">
                                        <label>🌐 Сайт</label>
                                        <input type="url" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" placeholder="https://...">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>📱 Telegram</label>
                                        <input type="text" name="social_tg" value="<?= htmlspecialchars($profile['social_tg'] ?? '') ?>" placeholder="@username">
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-success" style="width: 100%;">💖 Сохранить изменения</button>
                            </form>
                        </div>
                        
                        <!-- СМЕНА ПАРОЛЯ -->
                        <div id="passwordSection" class="profile-section">
                            <h3 style="color: var(--anime-red); margin-bottom: 20px;">🔐 Смена пароля</h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Текущий пароль</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                
                                <div class="info-grid">
                                    <div class="form-group">
                                        <label>Новый пароль</label>
                                        <input type="password" name="new_password" required minlength="6">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Подтверждение пароля</label>
                                        <input type="password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-edit" style="width: 100%;">🔐 Изменить пароль</button>
                            </form>
                        </div>
                        
                        <!-- СТАТИСТИКА -->
                        <div id="statsSection" class="profile-section">
                            <h3 style="color: var(--anime-red); margin-bottom: 20px;">📊 Моя статистика</h3>
                            <div class="info-grid">
                                <div class="stat-card-mini">
                                    <div class="stat-number-mini"><?= $topic_count ?></div>
                                    <div>Тем на форуме</div>
                                </div>
                                <div class="stat-card-mini">
                                    <div class="stat-number-mini"><?= $post_count ?></div>
                                    <div>Сообщений на форуме</div>
                                </div>
                                <div class="stat-card-mini">
                                    <div class="stat-number-mini"><?= $order_count ?></div>
                                    <div>Заказов</div>
                                </div>
                                <div class="stat-card-mini">
                                    <div class="stat-number-mini"><?= $client['role'] === 'admin' ? '👑' : '💖' ?></div>
                                    <div><?= $client['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?></div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <h4 style="color: var(--anime-purple); margin-bottom: 15px;">📋 Информация об аккаунте</h4>
                                <table style="width: 100%;">
                                    <tr>
                                        <td style="padding: 10px; font-weight: 700; color: var(--anime-red);">Email:</td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($client['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; font-weight: 700; color: var(--anime-red);">Имя:</td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($client['last_name'] . ' ' . $client['first_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px; font-weight: 700; color: var(--anime-red);">Дата регистрации:</td>
                                        <td style="padding: 10px;"><?= date('d.m.Y', strtotime($client['created_at'] ?? 'now')) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Профиль
    </div>
    
    <script>
        function showSection(section) {
            document.querySelectorAll('.profile-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
            
            if (section === 'edit') {
                document.getElementById('editSection').classList.add('active');
                event.target.classList.add('active');
            } else if (section === 'password') {
                document.getElementById('passwordSection').classList.add('active');
                event.target.classList.add('active');
            } else if (section === 'stats') {
                document.getElementById('statsSection').classList.add('active');
                event.target.classList.add('active');
            }
        }
    </script>
    
    <script src="anime-effects.js"></script>
</body>
</html>
