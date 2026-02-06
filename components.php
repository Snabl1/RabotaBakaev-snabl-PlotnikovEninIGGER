<?php
require 'config.php';

// Получение категорий
$categories = $pdo->query("SELECT * FROM component_categories")->fetchAll();

// Добавление комплектующего
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO components (category_id, component_name, price, description, in_stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['category_id'],
        $_POST['component_name'],
        $_POST['price'],
        $_POST['description'],
        isset($_POST['in_stock']) ? 1 : 0
    ]);
    header("Location: components.php");
    exit;
}

// Удаление
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM components WHERE component_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: components.php");
    exit;
}

// Получение всех комплектующих
$components = $pdo->query("
    SELECT c.*, cc.category_name 
    FROM components c 
    LEFT JOIN component_categories cc ON c.category_id = cc.category_id 
    ORDER BY c.component_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Комплектующие</title>
    <link rel="stylesheet" href="style.css">
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
        <h1>Комплектующие</h1>
        
        <h2>Добавить комплектующее</h2>
        <form method="POST">
            <select name="category_id" required>
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="component_name" placeholder="Название" required>
            <input type="number" step="0.01" name="price" placeholder="Цена" required>
            <textarea name="description" placeholder="Описание"></textarea>
            <label><input type="checkbox" name="in_stock" checked> В наличии</label>
            <button type="submit" name="add" class="btn">Добавить</button>
        </form>
        
        <h2>Список комплектующих</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Категория</th>
                <th>Название</th>
                <th>Цена</th>
                <th>Описание</th>
                <th>В наличии</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($components as $comp): ?>
            <tr>
                <td><?= $comp['component_id'] ?></td>
                <td><?= htmlspecialchars($comp['category_name']) ?></td>
                <td><?= htmlspecialchars($comp['component_name']) ?></td>
                <td><?= number_format($comp['price'], 2) ?> ₽</td>
                <td><?= htmlspecialchars($comp['description']) ?></td>
                <td><?= $comp['in_stock'] ? 'Да' : 'Нет' ?></td>
                <td>
                    <a href="?delete=<?= $comp['component_id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>