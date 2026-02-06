<?php
require 'config.php';

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO component_categories (category_name) VALUES (?)");
    $stmt->execute([$_POST['category_name']]);
    header("Location: categories.php");
    exit;
}

// Удаление
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM component_categories WHERE category_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: categories.php");
    exit;
}

// Получение всех категорий
$categories = $pdo->query("SELECT * FROM component_categories ORDER BY category_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Категории</title>
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
        <h1>Категории комплектующих</h1>
        
        <h2>Добавить категорию</h2>
        <form method="POST">
            <input type="text" name="category_name" placeholder="Название категории" required>
            <button type="submit" name="add" class="btn">Добавить</button>
        </form>
        
        <h2>Список категорий</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['category_id'] ?></td>
                <td><?= htmlspecialchars($cat['category_name']) ?></td>
                <td>
                    <a href="?delete=<?= $cat['category_id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить категорию?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>