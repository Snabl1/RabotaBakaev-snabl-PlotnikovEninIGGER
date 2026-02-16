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
    
    // Валидация
    if (empty($username) || empty($password) || empty($email) || empty($last_name) || empty($first_name) || empty($address)) {
        $error = 'Заполните все обязательные поля!';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают!';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email!';
    } else {
        // Проверка уникальности
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = 'Пользователь с таким именем или email уже существует!';
        } else {
            // Хешируем пароль
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Вставляем клиента
            $stmt = $pdo->prepare("
                INSERT INTO clients (username, password, email, last_name, first_name, middle_name, phone, address, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')
            ");
            
            if ($stmt->execute([$username, $hashed_password, $email, $last_name, $first_name, $middle_name, $phone, $address])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
                $_POST = []; // Очищаем форму
            } else {
                $error = 'Ошибка при регистрации. Попробуйте позже.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Танковая База</title>
    <link rel="stylesheet" href="style.css">
    <!-- Тот же стиль, что и раньше -->
</head>
<body>
    <div class="scanline"></div>
    
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php" class="icon-tank">Главная</a>
            <a href="login.php" class="icon-military">Вход</a>
            <a href="register.php" class="active icon-ammo">Регистрация</a>
            <a href="orders.php" class="icon-tank">Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="register-container">
            <div class="tank-icon">🛡️</div>
            
            <div class="register-header">
                <h2>РЕГИСТРАЦИЯ</h2>
                <p>Вступи в ряды Танковой Базы!</p>
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
                <!-- Имя пользователя и email -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Имя пользователя</label>
                        <input type="text" name="username" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                               placeholder="Позывной" required 
                               pattern="[A-Za-z0-9_]{3,20}" 
                               title="Только латинские буквы, цифры и _, от 3 до 20 символов">
                        <small style="color: var(--camo-tan);">От 3 до 20 символов (A-Z, a-z, 0-9, _)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required-field">Email</label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="tankist@example.com" required>
                    </div>
                </div>
                
                <!-- Пароли -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Пароль</label>
                        <input type="password" name="password" id="password" 
                               placeholder="Минимум 6 символов" required minlength="6">
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required-field">Подтверждение пароля</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               placeholder="Повторите пароль" required>
                        <div id="passwordMatch" style="font-size: 0.8rem; margin-top: 5px;"></div>
                    </div>
                </div>
                
                <!-- ФИО -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required-field">Фамилия</label>
                        <input type="text" name="last_name" 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" 
                               placeholder="Иванов" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required-field">Имя</label>
                        <input type="text" name="first_name" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" 
                               placeholder="Иван" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" name="middle_name" 
                               value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>" 
                               placeholder="Иванович">
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                               placeholder="+7 (999) 123-45-67">
                    </div>
                </div>
                
                <!-- Адрес -->
                <div class="form-group">
                    <label class="required-field">Адрес доставки</label>
                    <textarea name="address" rows="3" placeholder="Ваш адрес" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                
                <!-- Согласие -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="agreement" required>
                        <span style="color: var(--camo-tan);">Я согласен с условиями обслуживания и политикой конфиденциальности</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-success register-btn" id="submitBtn">
                    🚀 ЗАРЕГИСТРИРОВАТЬСЯ
                </button>
            </form>
            
            <div class="login-link">
                <p>Уже есть аккаунт? <a href="login.php">Войти в систему</a></p>
            </div>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Регистрация новых бойцов
    </div>
    
    <script>
        document.querySelector('.scanline').style.display = 'block';
        
        // Проверка сложности пароля
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
                strengthBar.style.width = '0';
                return;
            }
            
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
                passwordMatch.style.color = 'var(--digital-green)';
                confirmPassword.classList.remove('error');
            } else {
                passwordMatch.innerHTML = '❌ Пароли не совпадают';
                passwordMatch.style.color = 'var(--danger-red)';
                confirmPassword.classList.add('error');
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
                alert('Пароли не совпадают!');
            }
        });
        
        document.querySelector('input[name="username"]').focus();
    </script>
</body>
</html>