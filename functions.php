<?php
require_once 'config.php';
require_once 'config_delivery.php';
require_once 'config_warranty.php';

function safeDelete($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
    return $stmt->execute([$id]);
}

// Получение зон доставки - используем $pdo_delivery
function getDeliveryZones() {
    global $pdo_delivery;
    try {
        return $pdo_delivery->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY min_distance")->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting delivery zones: " . $e->getMessage());
        return [];
    }
}

// Получение гарантий - используем $pdo_warranty
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

// Расчет стоимости доставки - используем $pdo_delivery
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

// Расчет стоимости гарантии - используем $pdo_warranty
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

// Остальные функции остаются без изменений...
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
        $type_class = $flash['type'] == 'error' ? 'alert-error' : ($flash['type'] == 'warning' ? 'alert-warning' : 'alert-success');
        echo "<div class='alert $type_class'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}
?>