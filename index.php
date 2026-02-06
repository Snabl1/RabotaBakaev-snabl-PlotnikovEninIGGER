<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин компьютерных комплектующих</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .menu { background: #333; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .menu a { color: white; text-decoration: none; margin-right: 15px; padding: 8px 15px; border-radius: 3px; }
        .menu a:hover { background: #555; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; }
        .btn { padding: 8px 12px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .btn-delete { background: #f44336; }
        .btn-edit { background: #ff9800; }
        .form-group { margin-bottom: 15px; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="menu">
        <a href="index.php">Главная</a>
        <a href="clients.php">Клиенты</a>
        <a href="categories.php">Категории</a>
        <a href="components.php">Комплектующие</a>
        <a href="orders.php">Заказы</a>
    </div>
    <div class="container">
        <h1>Магазин компьютерных комплектующих</h1>
        <p>Добро пожаловать в систему управления магазином компьютерных комплектующих.</p>
        <p>Используйте меню выше для навигации.</p>
        
        <h2>Статистика</h2>
        <?php
        $tables = ['clients', 'components', 'component_categories', 'orders', 'order_items', 'receipts'];
        foreach ($tables as $table) {
            $result = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $row = $result->fetch();
            echo "<p><strong>$table:</strong> " . $row['count'] . " записей</p>";
        }
        ?>
    </div>
</body>
</html>