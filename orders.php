<?php
require_once 'functions.php';
requireAuth();

$isLoggedIn = isset($_SESSION['client_id']);
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? '';

// Получение клиентов и комплектующих
$clients = $pdo->query("SELECT * FROM clients")->fetchAll();
$components = $pdo->query("SELECT * FROM components WHERE in_stock = 1")->fetchAll();

// Получение зон доставки и гарантий - используем правильные подключения!
$delivery_zones = getDeliveryZones(); // Эта функция уже использует $pdo_delivery
$warranties = getWarranties(); // Эта функция уже использует $pdo_warranty

// Создание заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $pdo->beginTransaction();
    
    try {
        // Расчет стоимости доставки
        $delivery_cost = 0;
        if (isset($_POST['delivery_needed'])) {
            $zone_id = $_POST['zone_id'] ?? null;
            $distance = $_POST['distance'] ?? null;
            
            // Используем правильную функцию для расчета доставки
            if ($zone_id) {
                $delivery_cost = calculateDeliveryCost($zone_id, $distance);
            }
        }
        
        // Расчет стоимости сборки
        $assembly_fee = isset($_POST['assembly_needed']) ? 2000.00 : 0;
        
        // Расчет стоимости гарантии
        $warranty_cost = 0;
        if (isset($_POST['warranty_id']) && $_POST['warranty_id']) {
            $total_components_price = 0;
            foreach ($_POST['components'] as $component_id => $qty) {
                if ($qty > 0) {
                    $comp = $pdo->query("SELECT price FROM components WHERE component_id = $component_id")->fetch();
                    $total_components_price += $comp['price'] * $qty;
                }
            }
            $warranty_cost = calculateWarrantyCost($_POST['warranty_id'], $total_components_price);
        }
        
        // Расчет общей стоимости
        $total_cost = 0;
        foreach ($_POST['components'] as $component_id => $qty) {
            if ($qty > 0) {
                $comp = $pdo->query("SELECT price FROM components WHERE component_id = $component_id")->fetch();
                $total_cost += $comp['price'] * $qty;
            }
        }
        
        $total_cost += $delivery_cost + $assembly_fee + $warranty_cost;
        
        // Создание заказа в основной БД
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (client_id, delivery_needed, zone_id, delivery_address, delivery_cost, 
             assembly_fee, warranty_id, warranty_cost, total_cost) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // Клиент: только свой аккаунт для обычного пользователя, админ может выбрать любого
        $order_client_id = ($clientRole === 'admin' && !empty($_POST['client_id']))
            ? (int) $_POST['client_id']
            : (int) $_SESSION['client_id'];
        
        $warranty_id_val = !empty($_POST['warranty_id']) ? (int) $_POST['warranty_id'] : null;
        $zone_id_val = !empty($_POST['zone_id']) ? (int) $_POST['zone_id'] : null;

        $stmt->execute([
            $order_client_id,
            isset($_POST['delivery_needed']) ? 1 : 0,
            $zone_id_val,
            $_POST['delivery_address'] ?? null,
            $delivery_cost,
            $assembly_fee,
            $warranty_id_val,
            $warranty_cost,
            $total_cost
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
        header("Location: order_receipt.php?order_id=" . $order_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Получение заказов: админ видит все, клиент — только свои
$orders_sql = "
    SELECT o.*, c.last_name, c.first_name,
           o.zone_id, o.warranty_id,
           o.delivery_cost, o.warranty_cost,
           o.total_cost, o.order_date
    FROM orders o 
    LEFT JOIN clients c ON o.client_id = c.client_id 
";
if ($clientRole !== 'admin') {
    $stmt_orders = $pdo->prepare($orders_sql . " WHERE o.client_id = ? ORDER BY o.order_id DESC");
    $stmt_orders->execute([$_SESSION['client_id']]);
    $orders = $stmt_orders->fetchAll();
} else {
    $orders = $pdo->query($orders_sql . " ORDER BY o.order_id DESC")->fetchAll();
}

// Теперь нужно получить названия зон и гарантий отдельно
// Для этого можно либо сделать отдельные запросы, либо создать вспомогательную функцию
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы - Танковая База</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .order-section {
            background: rgba(42, 42, 42, 0.8);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid var(--camo-green);
            margin: 20px 0;
        }
        
        .zone-card {
            background: linear-gradient(145deg, rgba(74, 93, 35, 0.3), rgba(42, 42, 42, 0.8));
            padding: 15px;
            border-radius: 6px;
            border: 1px solid var(--camo-tan);
            margin: 10px 0;
            transition: all 0.3s;
        }
        
        .zone-card:hover {
            border-color: var(--ammo-gold);
            transform: translateY(-2px);
        }
        
        .warranty-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin: 2px;
        }
        
        .warranty-standard { background: linear-gradient(145deg, #2d5a27, #4a5d23); }
        .warranty-extended { background: linear-gradient(145deg, #8b5a00, #daa520); }
        .warranty-premium { background: linear-gradient(145deg, #8b0000, #cc0000); }
        
        .distance-input {
            width: 150px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="tank-logo">
            <h1>🚀 СБОРКА БОЕВОЙ СИСТЕМЫ</h1>
            <div class="subtitle">РАСЧЕТ СТОИМОСТИ ПК С ДОСТАВКОЙ И ГАРАНТИЕЙ</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ⚠️ Ошибка: <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="order-section">
            <h2><span class="icon-military">Создать новый заказ</span></h2>
            
            <form method="POST" id="orderForm">
                <?php if ($clientRole === 'admin'): ?>
                <!-- Выбор клиента — только для админа -->
                <div class="form-group">
                    <label>Клиент:</label>
                    <select name="client_id" required class="tank-select">
                        <option value="">Выберите клиента</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['client_id'] ?>">
                            <?= htmlspecialchars($client['last_name'] . ' ' . $client['first_name']) ?> 
                            - <?= htmlspecialchars($client['address']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="client_id" value="<?= (int) $_SESSION['client_id'] ?>">
                <?php endif; ?>
                
                <!-- Выбор комплектующих -->
                <h3><span class="icon-ammo">Комплектующие для сборки</span></h3>
                <table>
                    <tr>
                        <th>Компонент</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                    </tr>
                    <?php foreach ($components as $comp): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($comp['component_name']) ?></strong>
                            <?php if ($comp['description']): ?>
                            <br><small style="color: var(--camo-tan);"><?= htmlspecialchars($comp['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="price"><?= number_format($comp['price'], 2) ?> ₽</span></td>
                        <td>
                            <input type="number" name="components[<?= $comp['component_id'] ?>]" 
                                   value="0" min="0" max="10" class="qty-input component-qty" 
                                   data-price="<?= $comp['price'] ?>"
                                   onchange="calculateTotals()">
                        </td>
                        <td><span class="price component-total" data-id="<?= $comp['component_id'] ?>">0 ₽</span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <!-- Сборка -->
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="assembly_needed" id="assemblyCheck" onchange="calculateTotals()">
                        🛠️ Профессиональная сборка системы (+2,000 ₽)
                    </label>
                    <small style="color: var(--camo-tan); display: block; margin-left: 25px;">
                        Оптимизация, тестирование, кабель-менеджмент
                    </small>
                </div>
                
                <!-- Доставка -->
                <h3><span class="icon-military">⚡ Доставка</span></h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="delivery_needed" id="deliveryCheck" onchange="toggleDelivery()">
                        🚚 Требуется доставка
                    </label>
                </div>
                
                <div id="deliveryFields" style="display: none;">
                    <div class="form-group">
                        <label>Зона доставки:</label>
                        <div id="zonesContainer">
                            <?php if (count($delivery_zones) > 0): ?>
                                <?php foreach ($delivery_zones as $zone): ?>
                                <div class="zone-card">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="radio" name="zone_id" value="<?= $zone['zone_id'] ?>" 
                                               data-base="<?= $zone['base_price'] ?>" data-per-km="<?= $zone['price_per_km'] ?>"
                                               data-min="<?= $zone['min_distance'] ?>" onchange="updateDeliveryCost()">
                                        <div style="margin-left: 10px;">
                                            <strong style="color: var(--digital-green);"><?= htmlspecialchars($zone['zone_name']) ?></strong>
                                            <div style="color: var(--camo-tan); font-size: 0.9rem;">
                                                Базовая стоимость: <span class="price"><?= number_format($zone['base_price'], 2) ?> ₽</span><br>
                                                + <span class="price"><?= number_format($zone['price_per_km'], 2) ?> ₽/км</span> 
                                                (от <?= $zone['min_distance'] ?> км)
                                                <?php if ($zone['max_distance']): ?>
                                                до <?= $zone['max_distance'] ?> км
                                                <?php endif; ?>
                                                <br>
                                                Срок: <?= $zone['delivery_time_hours'] ?> часов
                                            </div>
                                        </div>
                                    </label>
                                    
                                    <div style="margin-top: 10px; display: none;" class="distance-input-container">
                                        <label style="color: var(--camo-tan);">Расстояние (км):</label>
                                        <input type="number" min="<?= $zone['min_distance'] ?>" 
                                               <?php if ($zone['max_distance']): ?>max="<?= $zone['max_distance'] ?>"<?php endif; ?>
                                               step="1" class="distance-input" placeholder="Введите расстояние"
                                               oninput="updateDeliveryCost()">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    ⚠️ Нет доступных зон доставки. Добавьте зоны доставки в соответствующем разделе.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Адрес доставки:</label>
                        <input type="text" name="delivery_address" placeholder="Полный адрес доставки">
                    </div>
                </div>
                
                <!-- Гарантия -->
                <h3><span class="icon-tank">🛡️ Гарантия</span></h3>
                <div class="form-group">
                    <label>Выберите гарантию:</label>
                    <select name="warranty_id" id="warrantySelect" onchange="calculateTotals()" class="tank-select">
                        <option value="">Без гарантии</option>
                        <?php if (count($warranties) > 0): ?>
                            <?php foreach ($warranties as $warranty): 
                                $type_class = 'warranty-' . $warranty['warranty_type'];
                            ?>
                            <option value="<?= $warranty['warranty_id'] ?>" data-multiplier="<?= $warranty['price_multiplier'] ?>">
                                <?= htmlspecialchars(ucfirst($warranty['warranty_type'])) ?> гарантия 
                                (<?= $warranty['warranty_period_months'] ?> мес.) 
                                <span class="warranty-badge <?= $type_class ?>">
                                    +<?= number_format(($warranty['price_multiplier'] - 1) * 100, 0) ?>%
                                </span>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (count($warranties) === 0): ?>
                    <div class="alert alert-warning" style="margin-top: 10px;">
                        ⚠️ Нет доступных гарантий. Добавьте гарантии в соответствующем разделе.
                    </div>
                    <?php endif; ?>
                    <div id="warrantyDetails" style="margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 4px; display: none;">
                        <!-- Детали гарантии будут загружаться динамически -->
                    </div>
                </div>
                
                <!-- Итоговая стоимость -->
                <div class="order-section" style="background: linear-gradient(145deg, rgba(74, 93, 35, 0.5), rgba(42, 42, 42, 0.9));">
                    <h3 style="color: var(--digital-green);">💰 ИТОГОВАЯ СТОИМОСТЬ</h3>
                    <table>
                        <tr>
                            <td>Комплектующие:</td>
                            <td><span class="price" id="componentsTotal">0 ₽</span></td>
                        </tr>
                        <tr>
                            <td>Сборка:</td>
                            <td><span class="price" id="assemblyTotal">0 ₽</span></td>
                        </tr>
                        <tr>
                            <td>Доставка:</td>
                            <td><span class="price" id="deliveryTotal">0 ₽</span></td>
                        </tr>
                        <tr>
                            <td>Гарантия:</td>
                            <td><span class="price" id="warrantyTotal">0 ₽</span></td>
                        </tr>
                        <tr style="border-top: 2px solid var(--ammo-gold);">
                            <td><strong>ВСЕГО:</strong></td>
                            <td><span class="price" style="font-size: 1.5rem;" id="grandTotal">0 ₽</span></td>
                        </tr>
                    </table>
                    <input type="hidden" name="total_cost" id="totalCostInput">
                </div>
                
                <button type="submit" name="create_order" class="btn btn-success" style="font-size: 1.2rem; padding: 15px 40px; width: 100%;">
                    🚀 СОЗДАТЬ ЗАКАЗ И ПЕРЕЙТИ К КВИТАНЦИИ
                </button>
            </form>
        </div>
        
        <!-- Список заказов -->
        <div class="order-section">
            <h2><span class="icon-military">История заказов</span> <span class="tank-badge"><?= count($orders) ?></span></h2>
            
            <div class="tank-search">
                <input type="text" id="orderSearch" placeholder="Поиск по клиентам...">
            </div>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Дата</th>
                    <th>Доставка</th>
                    <th>Гарантия</th>
                    <th>Итого</th>
                    <th>Квитанция</th>
                </tr>
                <?php foreach ($orders as $order): 
                    // Получаем информацию о доставке
                    $delivery_text = '';
                    if ($order['delivery_needed'] && $order['zone_id']) {
                        try {
                            $zone_stmt = $pdo_delivery->prepare("SELECT zone_name FROM delivery_zones WHERE zone_id = ?");
                            $zone_stmt->execute([$order['zone_id']]);
                            $zone = $zone_stmt->fetch();
                            if ($zone) {
                                $delivery_text = $zone['zone_name'];
                            }
                        } catch (Exception $e) {
                            $delivery_text = 'Доставка (ID: ' . $order['zone_id'] . ')';
                        }
                    }
                    
                    // Получаем информацию о гарантии
                    $warranty_text = '';
                    if ($order['warranty_id']) {
                        try {
                            $warranty_stmt = $pdo_warranty->prepare("SELECT warranty_type, warranty_period_months FROM warranties WHERE warranty_id = ?");
                            $warranty_stmt->execute([$order['warranty_id']]);
                            $warranty = $warranty_stmt->fetch();
                            if ($warranty) {
                                $warranty_text = ucfirst($warranty['warranty_type']) . ' (' . $warranty['warranty_period_months'] . ' мес.)';
                            }
                        } catch (Exception $e) {
                            $warranty_text = 'Гарантия (ID: ' . $order['warranty_id'] . ')';
                        }
                    }
                ?>
                <tr>
                    <td><span class="price">#<?= $order['order_id'] ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name']) ?></strong>
                    </td>
                    <td><?= $order['order_date'] ?></td>
                    <td>
                        <?php if ($order['delivery_needed']): ?>
                        <span class="status status-active">
                            <?= $delivery_text ?: 'Доставка' ?>
                            <?php if ($order['delivery_cost'] > 0): ?>
                            <br><small><?= number_format($order['delivery_cost'], 2) ?> ₽</small>
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="status status-inactive">Самовывоз</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($warranty_text): ?>
                        <span class="warranty-badge warranty-<?= $warranty['warranty_type'] ?? 'standard' ?>">
                            <?= $warranty_text ?>
                            <?php if ($order['warranty_cost'] > 0): ?>
                            <br><small>+<?= number_format($order['warranty_cost'], 2) ?> ₽</small>
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span style="color: var(--camo-tan);">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="price"><?= number_format($order['total_cost'], 2) ?> ₽</span></td>
                    <td>
                        <a href="order_receipt.php?order_id=<?= $order['order_id'] ?>" class="btn btn-edit" target="_blank">
                            🧾 Квитанция
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    
    <div class="tank-footer">
        Танковая база данных © 2024 | Система сборки ПК | 
        <span style="color: var(--digital-green);">● Многосерверная архитектура</span>
    </div>
    
    <script>
        // Переменные для расчетов
        let componentsPrice = 0;
        let assemblyPrice = 0;
        let deliveryPrice = 0;
        let warrantyPrice = 0;
        
        // Включить сканирующую линию
        document.querySelector('.scanline').style.display = 'block';
        
        // Переключение доставки
        function toggleDelivery() {
            const deliveryFields = document.getElementById('deliveryFields');
            const deliveryCheck = document.getElementById('deliveryCheck');
            
            deliveryFields.style.display = deliveryCheck.checked ? 'block' : 'none';
            if (!deliveryCheck.checked) {
                deliveryPrice = 0;
                calculateTotals();
            }
        }
        
        // Обновление стоимости доставки
        function updateDeliveryCost() {
            const selectedZone = document.querySelector('input[name="zone_id"]:checked');
            if (!selectedZone) {
                deliveryPrice = 0;
                calculateTotals();
                return;
            }
            
            const basePrice = parseFloat(selectedZone.dataset.base);
            const perKm = parseFloat(selectedZone.dataset.perKm);
            const minDistance = parseInt(selectedZone.dataset.min);
            
            // Показываем поле для расстояния
            document.querySelectorAll('.distance-input-container').forEach(container => {
                container.style.display = 'none';
            });
            
            const zoneCard = selectedZone.closest('.zone-card');
            const distanceContainer = zoneCard.querySelector('.distance-input-container');
            if (distanceContainer) {
                distanceContainer.style.display = 'block';
                const distanceInput = distanceContainer.querySelector('.distance-input');
                if (distanceInput.value) {
                    const distance = parseInt(distanceInput.value);
                    const extraDistance = Math.max(0, distance - minDistance);
                    deliveryPrice = basePrice + (extraDistance * perKm);
                } else {
                    deliveryPrice = basePrice;
                }
            } else {
                deliveryPrice = basePrice;
            }
            
            calculateTotals();
        }
        
        // Расчет всех итогов
        function calculateTotals() {
            // Комплектующие
            componentsPrice = 0;
            document.querySelectorAll('.component-qty').forEach(input => {
                const qty = parseInt(input.value) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                const total = qty * price;
                componentsPrice += total;
                
                // Обновляем сумму для каждого компонента
                const totalSpan = document.querySelector(`.component-total[data-id="${input.name.match(/\d+/)[0]}"]`);
                if (totalSpan) {
                    totalSpan.textContent = total.toFixed(2) + ' ₽';
                }
            });
            
            // Сборка
            assemblyPrice = document.getElementById('assemblyCheck').checked ? 2000 : 0;
            
            // Гарантия
            const warrantySelect = document.getElementById('warrantySelect');
            const selectedOption = warrantySelect.options[warrantySelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const multiplier = parseFloat(selectedOption.dataset.multiplier) || 1;
                warrantyPrice = componentsPrice * (multiplier - 1);
            } else {
                warrantyPrice = 0;
            }
            
            // Обновление отображения
            document.getElementById('componentsTotal').textContent = componentsPrice.toFixed(2) + ' ₽';
            document.getElementById('assemblyTotal').textContent = assemblyPrice.toFixed(2) + ' ₽';
            document.getElementById('deliveryTotal').textContent = deliveryPrice.toFixed(2) + ' ₽';
            document.getElementById('warrantyTotal').textContent = warrantyPrice.toFixed(2) + ' ₽';
            
            // Итого
            const grandTotal = componentsPrice + assemblyPrice + deliveryPrice + warrantyPrice;
            document.getElementById('grandTotal').textContent = grandTotal.toFixed(2) + ' ₽';
            document.getElementById('totalCostInput').value = grandTotal.toFixed(2);
            
            // Обновление деталей гарантии
            updateWarrantyDetails();
        }
        
        // Обновление деталей гарантии
        function updateWarrantyDetails() {
            const warrantySelect = document.getElementById('warrantySelect');
            const detailsDiv = document.getElementById('warrantyDetails');
            
            if (warrantySelect.value) {
                detailsDiv.style.display = 'block';
                detailsDiv.innerHTML = `
                    <div style="color: var(--camo-tan);">
                        <strong>Детали гарантии:</strong><br>
                        Дополнительная стоимость: <span class="price">${warrantyPrice.toFixed(2)} ₽</span><br>
                        <small>Дополнительная информация будет загружена...</small>
                    </div>
                `;
            } else {
                detailsDiv.style.display = 'none';
            }
        }
        
        // Поиск заказов
        document.getElementById('orderSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('table tr');
            
            rows.forEach((row, index) => {
                if (index === 0) return; // Пропускаем заголовок
                
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();
            
            // Анимация карточек зон доставки
            document.querySelectorAll('.zone-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.3)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
            
            // Стилизация селектов
            document.querySelectorAll('.tank-select').forEach(select => {
                select.style.background = 'rgba(20, 20, 20, 0.9)';
                select.style.border = '2px solid var(--camo-green)';
                select.style.color = 'var(--digital-green)';
                select.style.padding = '10px';
                select.style.borderRadius = '4px';
                select.style.fontFamily = "'Rajdhani', sans-serif";
            });
        });
    </script>
</body>
</html>