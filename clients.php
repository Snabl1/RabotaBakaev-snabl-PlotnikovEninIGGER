<?php
require 'config.php';

// Добавление клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO clients (last_name, first_name, middle_name, address, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['last_name'],
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['address'],
        $_POST['phone']
    ]);
    header("Location: clients.php");
    exit;
}

// Удаление клиента
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE client_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: clients.php");
    exit;
}

// Получение всех клиентов
$clients = $pdo->query("SELECT * FROM clients ORDER BY client_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты</title>
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
        <h1>Клиенты</h1>
        
        <h2>Добавить нового клиента</h2>
        <form method="POST">
            <input type="text" name="last_name" placeholder="Фамилия" required>
            <input type="text" name="first_name" placeholder="Имя" required>
            <input type="text" name="middle_name" placeholder="Отчество">
            <input type="text" name="address" placeholder="Адрес" required>
            <input type="text" name="phone" placeholder="Телефон">
            <button type="submit" name="add" class="btn">Добавить</button>
        </form>
        
        <h2>Список клиентов</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Фамилия</th>
                <th>Имя</th>
                <th>Отчество</th>
                <th>Адрес</th>
                <th>Телефон</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= $client['client_id'] ?></td>
                <td><?= htmlspecialchars($client['last_name']) ?></td>
                <td><?= htmlspecialchars($client['first_name']) ?></td>
                <td><?= htmlspecialchars($client['middle_name']) ?></td>
                <td><?= htmlspecialchars($client['address']) ?></td>
                <td><?= htmlspecialchars($client['phone']) ?></td>
                <td>
                    <a href="?delete=<?= $client['client_id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>