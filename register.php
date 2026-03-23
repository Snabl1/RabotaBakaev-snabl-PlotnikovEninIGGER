<?php
require_once 'functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $agreement = !empty($_POST['agreement']);
    if (empty($username) || empty($password) || empty($email) || empty($last_name) || empty($first_name) || empty($address)) {
        $error = 'Заполните все обязательные поля! 💕';
    } elseif (!$agreement) {
        $error = 'Необходимо согласие с условиями обслуживания! 💕';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают! 💕';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов! 💕';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email! 💕';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'Пользователь с таким именем или email уже существует! 💕';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $ok = false;
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO clients (username, password, email, last_name, first_name, middle_name, phone, address, role, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 1)
                ");
                $ok = $stmt->execute([$username, $hashed_password, $email, $last_name, $first_name, $middle_name, $phone, $address]);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'is_active') !== false) {
                    $stmt = $pdo->prepare("
                        INSERT INTO clients (username, password, email, last_name, first_name, middle_name, phone, address, role)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')
                    ");
                    $ok = $stmt->execute([$username, $hashed_password, $email, $last_name, $first_name, $middle_name, $phone, $address]);
                } else {
                    throw $e;
                }
            }

            if ($ok) {
                $success = 'Регистрация успешна! Теперь вы можете войти. 💕';
                $_POST = [];
            } else {
                $error = 'Ошибка при регистрации. Попробуйте позже. 💕';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 РЕГИСТРАЦИЯ - ANIME WORLD 🌸</title>
    <link rel="stylesheet" href="style-anime-world.css">
    <style>
        .register-page {
            background: linear-gradient(180deg, #ffeaa7 0%, #fab1a0 50%, #fd79a8 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .register-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(255,245,228,0.95));
            backdrop-filter: blur(20px);
            border-radius: 30px;
            border: 4px solid var(--anime-orange);
            box-shadow: 
                0 20px 60px rgba(255, 107, 53, 0.4),
                0 0 40px rgba(255, 183, 197, 0.3);
            padding: 40px;
            margin: 20px 0;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-title {
            font-family: 'Fredoka', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        .register-subtitle {
            color: var(--anime-purple);
            font-family: 'Comic Neue', cursive;
            font-size: 1.1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-row.single {
            grid-template-columns: 1fr;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--anime-red);
            font-weight: 700;
            font-family: 'Fredoka', sans-serif;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--anime-pink);
            border-radius: 15px;
            color: var(--anime-dark);
            font-family: 'Comic Neue', cursive;
            font-size: 1rem;
            transition: all 0.3s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--anime-yellow);
            box-shadow: 0 0 20px rgba(255, 211, 42, 0.4);
            transform: translateY(-2px);
        }
        .password-strength {
            height: 8px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .strength-bar.weak {
            background: linear-gradient(90deg, #ff6b6b, #ee5a5a);
        }
        .strength-bar.medium {
            background: linear-gradient(90deg, #ffd32a, #ffa502);
        }
        .strength-bar.strong {
            background: linear-gradient(90deg, #26de81, #20bf6b);
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
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 30px rgba(255, 71, 87, 0.4);
        }
        .submit-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(255, 71, 87, 0.6);
        }
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 3px dashed var(--anime-pink);
        }
        .login-link a {
            color: var(--anime-red);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
        }
        .login-link a:hover {
            color: var(--anime-orange);
            text-decoration: underline;
        }
        .agreement-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            padding: 15px;
            background: rgba(255, 183, 197, 0.1);
            border-radius: 15px;
            border: 2px solid var(--anime-pink);
            transition: all 0.3s;
        }
        .agreement-label:hover {
            background: rgba(255, 183, 197, 0.2);
            border-color: var(--anime-red);
        }
        .agreement-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-top: 2px;
        }
        .agreement-label span {
            color: var(--anime-purple);
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .register-card {
                padding: 25px;
            }
            .register-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="register-page">
    <div class="scanline"></div>

    <?php $isLoggedIn = false; $clientName = ''; $clientRole = ''; include 'navbar.php'; ?>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1 class="register-title">🌸 РЕГИСТРАЦИЯ 🌸</h1>
                <p class="register-subtitle">Вступай в наши ряды, брат! 💕</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <script>
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>👤 Имя пользователя *</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               placeholder="Позывной" required
                               pattern="[A-Za-z0-9_]{3,20}"
                               title="Только латинские буквы, цифры и _, от 3 до 20 символов">
                    </div>

                    <div class="form-group">
                        <label>📧 Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="tvoi@email.com" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>🔐 Пароль *</label>
                        <input type="password" name="password" id="password"
                               placeholder="Минимум 6 символов" required minlength="6">
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>🔐 Подтверждение пароля *</label>
                        <input type="password" name="confirm_password" id="confirm_password"
                               placeholder="Повторите пароль" required>
                        <div id="passwordMatch" style="font-size: 0.85rem; margin-top: 8px; font-weight: 600;"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>📛 Фамилия *</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                               placeholder="Иванов" required>
                    </div>

                    <div class="form-group">
                        <label>📛 Имя *</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                               placeholder="Иван" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>📛 Отчество</label>
                        <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>"
                               placeholder="Иванович">
                    </div>

                    <div class="form-group">
                        <label>📱 Телефон</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               placeholder="+7 (999) 123-45-67">
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label>🏠 Адрес доставки *</label>
                        <textarea name="address" rows="3" placeholder="Куда доставлять заказ?" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="agreement-label">
                        <input type="checkbox" name="agreement" required>
                        <span>Я согласен с условиями обслуживания и политикой конфиденциальности 💕</span>
                    </label>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    🚀 ЗАРЕГИСТРИРОВАТЬСЯ
                </button>
            </form>

            <div class="login-link">
                <p style="color: var(--anime-purple); font-size: 1rem;">
                    Уже есть аккаунт? <a href="login.php">💖 Войти в систему</a>
                </p>
            </div>
        </div>
    </div>

    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Регистрация новых бойцов
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const passwordMatch = document.getElementById('passwordMatch');

        function checkPasswordStrength(pwd) {
            let strength = 0;
            if (pwd.length >= 6) strength++;
            if (pwd.match(/[a-z]+/)) strength++;
            if (pwd.match(/[A-Z]+/)) strength++;
            if (pwd.match(/[0-9]+/)) strength++;
            if (pwd.match(/[$@#&!]+/)) strength++;

            strengthBar.className = 'strength-bar';
            if (pwd.length === 0) {
                strengthBar.style.width = '0%';
                return;
            }
            const width = Math.min(100, (strength / 5) * 100);
            strengthBar.style.width = width + '%';

            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        }

        function checkPasswordMatch() {
            if (confirmPassword.value.length === 0) {
                passwordMatch.innerHTML = '';
                return;
            }
            if (password.value === confirmPassword.value) {
                passwordMatch.innerHTML = '✅ Пароли совпадают';
                passwordMatch.style.color = 'var(--anime-green)';
                confirmPassword.style.borderColor = 'var(--anime-green)';
            } else {
                passwordMatch.innerHTML = '❌ Пароли не совпадают';
                passwordMatch.style.color = 'var(--anime-red)';
                confirmPassword.style.borderColor = 'var(--anime-red)';
            }
        }

        password.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        confirmPassword.addEventListener('input', checkPasswordMatch);

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Пароли не совпадают! 💕');
            }
        });
    </script>

    <script src="anime-effects.js"></script>
</body>
</html>
