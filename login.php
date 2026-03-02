<?php
require_once 'functions.php';

// Если уже залогинен, редирект
if (isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль!';
    } else {
        // Ищем по username или email
        try {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $client = $stmt->fetch();
        } catch (PDOException $e) {
            // Если колонки is_active нет — запрос без неё
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $client = $stmt->fetch();
        }
        
        if ($client && password_verify($password, $client['password'])) {
            // Успешный вход
            $_SESSION['client_id'] = $client['client_id'];
            $_SESSION['client_username'] = $client['username'];
            $_SESSION['client_role'] = $client['role'] ?? 'user';
            $_SESSION['client_name'] = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
            
            // Обновляем время последнего входа (если колонка есть)
            try {
                $stmt = $pdo->prepare("UPDATE clients SET last_login = NOW() WHERE client_id = ?");
                $stmt->execute([$client['client_id']]);
            } catch (PDOException $e) { /* колонка last_login может отсутствовать */ }
            
            header("Location: index.php");
            exit;
        } else {
            $error = 'Неверный логин или пароль!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Танковая База</title>
    <link rel="stylesheet" href="style.css">
    <!-- Тот же стиль, что и раньше -->
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = isset($_SESSION['client_id']); $clientName = $_SESSION['client_name'] ?? ''; $clientRole = $_SESSION['client_role'] ?? ''; include 'navbar.php'; ?>
    
    <div class="container">
        <div class="login-container">
            <div class="tank-icon">🎯</div>
            
            <div class="login-header">
                <h2>ВХОД В СИСТЕМУ</h2>
                <p>Для доступа к личному кабинету</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Логин или Email</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                           placeholder="Введите логин или email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" placeholder="Введите пароль" required>
                </div>
                
                <button type="submit" class="btn btn-success login-btn">
                    🚀 ВОЙТИ
                </button>
            </form>
            
            <div class="register-link">
                <p>Ещё нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            </div>
            
            <!-- Тестовые данные -->
            <div style="margin-top: 30px; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 5px;">
                <p style="color: var(--camo-tan); text-align: center; font-size: 0.9rem;">
                    Тестовый доступ:<br>
                    Логин: <strong style="color: var(--digital-green);">admin</strong><br>
                    Пароль: <strong style="color: var(--digital-green);">admin123</strong>
                </p>
            </div>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Авторизация
    </div>
    
    <script>
        document.querySelector('.scanline').style.display = 'block';
        document.querySelector('input[name="username"]').focus();
    </script>
</body>
</html>