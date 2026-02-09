<?php
require_once 'config.php'; // Используем require_once

// Функция для безопасного удаления
function safeDelete($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
    return $stmt->execute([$id]);
}

// Функция для получения записи
function getRecord($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $id_field = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Функция безопасного удаления с проверкой зависимостей
function safeDeleteWithCheck($pdo, $table, $id_field, $id, $dependencies = []) {
    $pdo->beginTransaction();
    try {
        // Проверяем зависимости
        foreach ($dependencies as $dep_table => $dep_field) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $dep_table WHERE $dep_field = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("Нельзя удалить запись, так как она используется в таблице $dep_table");
            }
        }
        
        // Удаляем
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Функция для редиректа с сообщением
function redirectWithMessage($url, $type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
}

// Старт сессии если не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Генерация CSRF токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Обработка флеш-сообщений
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo "<div class='alert alert-{$flash['type']}'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}
?>