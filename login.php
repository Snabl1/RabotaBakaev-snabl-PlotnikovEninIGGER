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
        $error = 'Введите логин и пароль! 💕';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $client = $stmt->fetch();
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $client = $stmt->fetch();
        }

        if ($client && password_verify($password, $client['password'])) {
            $_SESSION['client_id'] = $client['client_id'];
            $_SESSION['client_username'] = $client['username'];
            $_SESSION['client_role'] = $client['role'] ?? 'user';
            $_SESSION['client_name'] = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));

            try {
                $stmt = $pdo->prepare("UPDATE clients SET last_login = NOW() WHERE client_id = ?");
                $stmt->execute([$client['client_id']]);
            } catch (PDOException $e) { }

            header("Location: index.php");
            exit;
        } else {
            $error = 'Неверный логин или пароль! 💕';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 ВХОД - ANIME WORLD 🌸</title>
    <link rel="stylesheet" href="style-anime-world.css">
    <style>
        .login-page {
            background: linear-gradient(180deg, #ffeaa7 0%, #fab1a0 50%, #fd79a8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }
        .login-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(255,245,228,0.95));
            backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 4px solid var(--anime-orange);
            box-shadow: 
                0 20px 60px rgba(255, 107, 53, 0.4),
                0 0 40px rgba(255, 183, 197, 0.3);
            padding: 40px;
            text-align: center;
        }
        .login-header {
            margin-bottom: 30px;
        }
        .login-icon {
            font-size: 5rem;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .login-title {
            font-family: 'Fredoka', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        .login-subtitle {
            color: var(--anime-purple);
            font-family: 'Comic Neue', cursive;
            font-size: 1rem;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--anime-red);
            font-weight: 700;
            font-family: 'Fredoka', sans-serif;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--anime-pink);
            border-radius: 15px;
            color: var(--anime-dark);
            font-family: 'Comic Neue', cursive;
            font-size: 1rem;
            transition: all 0.3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--anime-yellow);
            box-shadow: 0 0 20px rgba(255, 211, 42, 0.4);
            transform: translateY(-2px);
        }
        .submit-btn {
            width: 100%;
            padding: 15px 30px;
            background: var(--gradient-main);
            color: white;
            border: none;
            border-radius: 25px;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 30px rgba(255, 71, 87, 0.4);
            margin-top: 10px;
        }
        .submit-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(255, 71, 87, 0.6);
        }
        .register-link {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 3px dashed var(--anime-pink);
        }
        .register-link a {
            color: var(--anime-red);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
        }
        .register-link a:hover {
            color: var(--anime-orange);
            text-decoration: underline;
        }
        .test-credentials {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(145deg, rgba(255,183,197,0.2), rgba(255,105,180,0.2));
            border-radius: 15px;
            border: 2px dashed var(--anime-pink);
        }
        .test-credentials p {
            color: var(--anime-dark);
            font-weight: 600;
            margin-bottom: 10px;
        }
        .test-credentials strong {
            color: var(--anime-red);
            background: rgba(255,255,255,0.7);
            padding: 3px 8px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="login-page">
    <div class="scanline"></div>

    <?php $isLoggedIn = false; $clientName = ''; $clientRole = ''; include 'navbar.php'; ?>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">🔐</div>
                <h1 class="login-title">ВХОД В СИСТЕМУ</h1>
                <p class="login-subtitle">С возвращением, брат! 💕</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>📧 Логин или Email</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Введите логин или email" required autofocus>
                </div>

                <div class="form-group">
                    <label>🔐 Пароль</label>
                    <input type="password" name="password" placeholder="Введите пароль" required>
                </div>

                <button type="submit" class="submit-btn">
                    🚀 ВОЙТИ
                </button>
            </form>

            <div class="register-link">
                <p style="color: var(--anime-purple);">
                    Ещё нет аккаунта? <a href="register.php">💖 Зарегистрироваться</a>
                </p>
            </div>

            <div class="test-credentials">
                <p>🎮 Тестовый доступ:</p>
                <p>Логин: <strong>admin</strong></p>
                <p>Пароль: <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Вход для своих
    </div>

    <script src="anime-effects.js"></script>
</body>
</html>
