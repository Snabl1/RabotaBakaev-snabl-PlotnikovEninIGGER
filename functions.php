<?php
require_once 'config.php';
require_once 'config_delivery.php';
require_once 'config_warranty.php';

// Старт сессии если не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['client_id']);
}

// Получение текущего клиента
function getCurrentClient() {
    global $pdo;
    if (!isset($_SESSION['client_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    return $stmt->fetch();
}

// Проверка прав администратора
function isAdmin() {
    return isset($_SESSION['client_role']) && $_SESSION['client_role'] === 'admin';
}

// Требование авторизации
function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Требование прав администратора
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        die("Доступ запрещен! Требуются права администратора.");
    }
}

// Остальные функции...
function safeDelete($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
    return $stmt->execute([$id]);
}

// Получение зон доставки
function getDeliveryZones() {
    global $pdo_delivery;
    try {
        return $pdo_delivery->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY min_distance")->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting delivery zones: " . $e->getMessage());
        return [];
    }
}

// Получение гарантий
function getWarranties($component_id = null) {
    global $pdo_warranty;
    
    try {
        if ($component_id) {
            $stmt = $pdo_warranty->prepare("
                SELECT * FROM warranties 
                WHERE component_id = ? OR component_id IS NULL 
                ORDER BY warranty_period_months DESC
            ");
            $stmt->execute([$component_id]);
            return $stmt->fetchAll();
        } else {
            return $pdo_warranty->query("
                SELECT * FROM warranties 
                ORDER BY warranty_period_months DESC
            ")->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Error getting warranties: " . $e->getMessage());
        return [];
    }
}

// Расчет стоимости доставки
function calculateDeliveryCost($zone_id, $distance = null) {
    global $pdo_delivery;
    
    try {
        $stmt = $pdo_delivery->prepare("SELECT * FROM delivery_zones WHERE zone_id = ?");
        $stmt->execute([$zone_id]);
        $zone = $stmt->fetch();
        
        if (!$zone) return 0;
        
        if ($distance === null) {
            return $zone['base_price'];
        }
        
        $extra_distance = max(0, $distance - $zone['min_distance']);
        return $zone['base_price'] + ($extra_distance * $zone['price_per_km']);
    } catch (Exception $e) {
        error_log("Error calculating delivery cost: " . $e->getMessage());
        return 0;
    }
}

// Расчет стоимости гарантии
function calculateWarrantyCost($warranty_id, $component_price) {
    global $pdo_warranty;
    
    try {
        $stmt = $pdo_warranty->prepare("SELECT price_multiplier FROM warranties WHERE warranty_id = ?");
        $stmt->execute([$warranty_id]);
        $warranty = $stmt->fetch();
        
        if (!$warranty) return 0;
        
        return $component_price * ($warranty['price_multiplier'] - 1);
    } catch (Exception $e) {
        error_log("Error calculating warranty cost: " . $e->getMessage());
        return 0;
    }
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
        foreach ($dependencies as $dep_table => $dep_field) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $dep_table WHERE $dep_field = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("Нельзя удалить запись, так как она используется в таблице $dep_table");
            }
        }
        
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
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
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
        $type_class = $flash['type'] == 'error' ? 'alert-error' : ($flash['type'] == 'warning' ? 'alert-warning' : 'alert-success');
        echo "<div class='alert $type_class'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}
?>