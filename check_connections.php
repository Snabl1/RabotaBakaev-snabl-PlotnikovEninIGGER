<?php
require_once 'config.php';
require_once 'config_delivery.php';
require_once 'config_warranty.php';

echo "<h1>Проверка подключений к БД</h1>";

// Проверка основной БД
try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $data = $result->fetch();
    echo "<p style='color: green;'>✅ Основная БД: Успешно! Клиентов: {$data['count']}</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Основная БД: {$e->getMessage()}</p>";
}

// Проверка БД доставки
try {
    $result = $pdo_delivery->query("SELECT COUNT(*) as count FROM delivery_zones");
    $data = $result->fetch();
    echo "<p style='color: green;'>✅ БД доставки: Успешно! Зон: {$data['count']}</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ БД доставки: {$e->getMessage()}</p>";
}

// Проверка БД гарантий
try {
    $result = $pdo_warranty->query("SELECT COUNT(*) as count FROM warranties");
    $data = $result->fetch();
    echo "<p style='color: green;'>✅ БД гарантий: Успешно! Гарантий: {$data['count']}</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ БД гарантий: {$e->getMessage()}</p>";
}

echo "<hr>";
echo "<h2>Сводная информация:</h2>";

echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;'>";
echo "<div style='background: #2a2a2a; padding: 20px; border-radius: 8px;'>";
echo "<h3>Основная БД</h3>";
echo "<p>Хост: $host</p>";
echo "<p>База: $db</p>";
echo "</div>";

echo "<div style='background: #2a2a2a; padding: 20px; border-radius: 8px;'>";
echo "<h3>БД доставки</h3>";
echo "<p>Хост: $host_delivery</p>";
echo "<p>База: $db_delivery</p>";
echo "</div>";

echo "<div style='background: #2a2a2a; padding: 20px; border-radius: 8px;'>";
echo "<h3>БД гарантий</h3>";
echo "<p>Хост: $host_warranty</p>";
echo "<p>База: $db_warranty</p>";
echo "</div>";
echo "</div>";

echo "<p><a href='index.php'>Вернуться на главную</a></p>";
?>