<?php
require_once 'config.php';
require_once 'config_delivery.php';
require_once 'config_warranty.php';

// Пример миграции данных о заказах для отчетов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['migrate'])) {
    try {
        // Получаем заказы из основной БД
        $orders = $pdo->query("SELECT * FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchAll();
        
        $pdo_delivery->beginTransaction();
        
        foreach ($orders as $order) {
            // Сохраняем статистику доставки
            $stmt = $pdo_delivery->prepare("
                INSERT INTO delivery_statistics 
                (order_id, zone_id, delivery_cost, delivery_date) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE delivery_cost = VALUES(delivery_cost)
            ");
            $stmt->execute([
                $order['order_id'],
                $order['zone_id'],
                $order['delivery_cost'],
                $order['order_date']
            ]);
        }
        
        $pdo_delivery->commit();
        echo "<p style='color: green;'>✅ Данные о доставке мигрированы!</p>";
        
    } catch (Exception $e) {
        $pdo_delivery->rollBack();
        echo "<p style='color: red;'>❌ Ошибка миграции: {$e->getMessage()}</p>";
    }
}
?>

<h1>Миграция данных между БД</h1>
<form method="POST">
    <p>Эта операция синхронизирует данные о доставке между основной БД и БД доставки.</p>
    <button type="submit" name="migrate" class="btn btn-success">🔄 Запустить миграцию</button>
</form>
<p><a href="index.php">Вернуться на главную</a></p>