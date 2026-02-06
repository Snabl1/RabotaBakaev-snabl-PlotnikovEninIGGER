<?php
require 'config.php';

// Получение клиентов и комплектующих
$clients = $pdo->query("SELECT * FROM clients")->fetchAll();
$components = $pdo->query("SELECT * FROM components WHERE in_stock = 1")->fetchAll();

// Создание заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $pdo->beginTransaction();
    
    try {
        // Создание заказа
        $stmt = $pdo->prepare("INSERT INTO orders (client_id, delivery_needed, delivery_address, delivery_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['client_id'],
            isset($_POST['delivery_needed']) ? 1 : 0,
            $_POST['delivery_address'] ?? null,
            $_POST['delivery_cost'] ?? 0,
            $_POST['total_cost']
        ]);
        $order_id = $pdo->lastInsertId();
        
        // Добавление товаров в заказ
        foreach ($_POST['components'] as $component_id => $qty) {
            if ($qty > 0) {
                $comp = $pdo->query("SELECT price FROM components WHERE component_id = $component_id")->fetch();
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, component_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $component_id, $qty, $comp['price']]);
            }
        }
        
        $pdo->commit();
        header("Location: orders.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Получение всех заказов
$orders = $pdo->query("
    SELECT o.*, c.last_name, c.first_name 
    FROM orders o 
    LEFT JOIN clients c ON o.client_id = c.client_id 
    ORDER BY o.order_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы</title>
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
        <h1>Заказы</h1>
        
        <?php if (isset($error)): ?>
            <div style="color: red;">Ошибка: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="color: green;">Заказ успешно создан!</div>
        <?php endif; ?>
        
        <h2>Создать новый заказ</h2>
        <form method="POST" id="orderForm">
            <select name="client_id" required>
                <option value="">Выберите клиента</option>
                <?php foreach ($clients as $client): ?>
                <option value="<?= $client['client_id'] ?>">
                    <?= htmlspecialchars($client['last_name'] . ' ' . $client['first_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <h3>Товары</h3>
            <table>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Количество</th>
                </tr>
                <?php foreach ($components as $comp): ?>
                <tr>
                    <td><?= htmlspecialchars($comp['component_name']) ?></td>
                    <td><?= number_format($comp['price'], 2) ?> ₽</td>
                    <td>
                        <input type="number" name="components[<?= $comp['component_id'] ?>]" 
                               value="0" min="0" class="qty-input" data-price="<?= $comp['price'] ?>">
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <label>
                <input type="checkbox" name="delivery_needed" id="deliveryCheck"> Требуется доставка
            </label>
            
            <div id="deliveryFields" style="display: none;">
                <input type="text" name="delivery_address" placeholder="Адрес доставки">
                <input type="number" step="0.01" name="delivery_cost" placeholder="Стоимость доставки" value="0">
            </div>
            
            <h3>Итого: <span id="totalCost">0</span> ₽</h3>
            <input type="hidden" name="total_cost" id="totalCostInput">
            
            <button type="submit" name="create_order" class="btn">Создать заказ</button>
        </form>
        
        <h2>Список заказов</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Дата</th>
                <th>Доставка</th>
                <th>Итого</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name']) ?></td>
                <td><?= $order['order_date'] ?></td>
                <td><?= $order['delivery_needed'] ? 'Да' : 'Нет' ?></td>
                <td><?= number_format($order['total_cost'], 2) ?> ₽</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
        // Показать/скрыть поля доставки
        document.getElementById('deliveryCheck').addEventListener('change', function() {
            document.getElementById('deliveryFields').style.display = this.checked ? 'block' : 'none';
        });
        
        // Расчет итоговой суммы
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.qty-input').forEach(input => {
                const qty = parseInt(input.value) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                total += qty * price;
            });
            
            const deliveryCost = parseFloat(document.querySelector('[name="delivery_cost"]')?.value) || 0;
            total += deliveryCost;
            
            document.getElementById('totalCost').textContent = total.toFixed(2);
            document.getElementById('totalCostInput').value = total.toFixed(2);
        }
        
        // Слушатели событий
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        
        document.querySelector('[name="delivery_cost"]')?.addEventListener('input', calculateTotal);
        
        // Инициализация
        calculateTotal();
    </script>
</body>
</html>