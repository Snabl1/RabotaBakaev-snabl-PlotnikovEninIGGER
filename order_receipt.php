<?php
require_once 'functions.php';
requireAuth();

if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int) $_GET['order_id'];

// Заказ и клиент — в основной БД (project_Bakaev). Зоны доставки и гарантии — в других БД, подгружаем отдельно
$stmt = $pdo->prepare("
    SELECT o.*, c.last_name, c.first_name, c.middle_name, c.address as client_address, c.phone
    FROM orders o 
    LEFT JOIN clients c ON o.client_id = c.client_id 
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Заказ не найден!");
}

// Подгрузка зоны доставки из БД доставки (если есть zone_id)
$order['zone_name'] = '';
$order['delivery_time_hours'] = '';
if (!empty($order['zone_id'])) {
    try {
        $dz = $pdo_delivery->prepare("SELECT zone_name, delivery_time_hours FROM delivery_zones WHERE zone_id = ?");
        $dz->execute([$order['zone_id']]);
        $dz_row = $dz->fetch();
        if ($dz_row) {
            $order['zone_name'] = $dz_row['zone_name'];
            $order['delivery_time_hours'] = $dz_row['delivery_time_hours'];
        }
    } catch (PDOException $e) {
        $order['zone_name'] = 'Зона #' . $order['zone_id'];
    }
}

// Подгрузка гарантии из БД гарантий (если есть warranty_id)
$order['warranty_type'] = '';
$order['warranty_period_months'] = '';
$order['warranty_desc'] = '';
if (!empty($order['warranty_id'])) {
    try {
        $w = $pdo_warranty->prepare("SELECT warranty_type, warranty_period_months, description FROM warranties WHERE warranty_id = ?");
        $w->execute([$order['warranty_id']]);
        $w_row = $w->fetch();
        if ($w_row) {
            $order['warranty_type'] = $w_row['warranty_type'];
            $order['warranty_period_months'] = $w_row['warranty_period_months'];
            $order['warranty_desc'] = $w_row['description'] ?? '';
        }
    } catch (PDOException $e) {
        $order['warranty_type'] = 'Гарантия #' . $order['warranty_id'];
    }
}

// Обычный пользователь может смотреть только свою квитанцию
if (!isAdmin() && (int) $order['client_id'] !== (int) $_SESSION['client_id']) {
    header("Location: orders.php");
    exit;
}

// Получаем товары заказа
$stmt_items = $pdo->prepare("
    SELECT oi.*, c.component_name 
    FROM order_items oi 
    LEFT JOIN components c ON oi.component_id = c.component_id 
    WHERE oi.order_id = ?
");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll();

// Генерация PDF квитанции (упрощенный вариант HTML)
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Квитанция #<?= $order['order_id'] ?> - Танковая База</title>
    <link rel="stylesheet" href="style-anime.css">
    <style>
        .receipt-container { max-width: 800px; margin: 20px auto; background: white; color: black; padding: 40px; border: 3px solid var(--danger-red); border-radius: 10px; font-family: 'Courier New', monospace; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid black;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .receipt-title {
            font-size: 2.5rem;
            color: var(--danger-red);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .receipt-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-top: 10px;
        }
        
        .receipt-section {
            margin: 25px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .receipt-label {
            font-weight: bold;
            color: #333;
        }
        
        .receipt-value {
            color: #000;
            text-align: right;
        }
        
        .receipt-total {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--danger-red);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 3px solid black;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(0,0,0,0.1);
            font-weight: bold;
            white-space: nowrap;
            pointer-events: none;
            z-index: 1;
        }
        
        .stamp {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 150px;
            height: 150px;
            border: 3px solid red;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(15deg);
            opacity: 0.8;
        }
        
        .stamp-text {
            color: red;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            font-size: 1.2rem;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        @media print {
            .print-btn {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .scanline, .menu, .tank-footer {
                display: none;
            }
            
            .receipt-container {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
        }
        
        .tank-stamp {
            background: conic-gradient(
                red 0deg 30deg,
                transparent 30deg 60deg,
                red 60deg 90deg,
                transparent 90deg 120deg,
                red 120deg 150deg,
                transparent 150deg 180deg,
                red 180deg 210deg,
                transparent 210deg 240deg,
                red 240deg 270deg,
                transparent 270deg 300deg,
                red 300deg 330deg,
                transparent 330deg 360deg
            );
        }
    </style>
    </head>
    <body>
        <div class="scanline"></div>
    <?php
    $isLoggedIn = isset($_SESSION['client_id']);
    $clientName = $_SESSION['client_name'] ?? '';
    $clientRole = $_SESSION['client_role'] ?? '';
    include 'navbar.php';
    ?>

<button onclick="window.print()" class="btn btn-success print-btn">
    🖨️ Печать квитанции
</button>

<div class="receipt-container">
    <!-- Водяной знак -->
    <div class="watermark">
        ТАНКОВАЯ БАЗА
    </div>
    
    <!-- Штамп -->
    <div class="stamp tank-stamp">
        <div class="stamp-text">
            ОПЛАЧЕНО<br>
            #<?= $order['order_id'] ?><br>
            <?= date('d.m.Y') ?>
        </div>
    </div>
    
    <!-- Заголовок -->
    <div class="receipt-header">
        <div class="receipt-title">КВИТАНЦИЯ ОБ ОПЛАТЕ</div>
        <div class="receipt-subtitle">Танковая База Комплектующих</div>
        <div style="margin-top: 10px; color: #666;">
            Дата: <?= $order['order_date'] ?><br>
            Номер заказа: <strong>#<?= $order['order_id'] ?></strong>
        </div>
    </div>
    
    <!-- Информация о клиенте -->
    <div class="receipt-section">
        <h3 style="color: var(--danger-red); margin-bottom: 15px;">👤 ДАННЫЕ КЛИЕНТА</h3>
        <div class="receipt-row">
            <div class="receipt-label">ФИО:</div>
            <div class="receipt-value">
                <?= htmlspecialchars($order['last_name'] . ' ' . $order['first_name'] . ' ' . ($order['middle_name'] ?? '')) ?>
            </div>
        </div>
        <div class="receipt-row">
            <div class="receipt-label">Адрес:</div>
            <div class="receipt-value">
                <?= htmlspecialchars($order['client_address']) ?>
            </div>
        </div>
        <?php if ($order['phone']): ?>
        <div class="receipt-row">
            <div class="receipt-label">Телефон:</div>
            <div class="receipt-value"><?= htmlspecialchars($order['phone']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Состав заказа -->
    <div class="receipt-section">
        <h3 style="color: var(--danger-red); margin-bottom: 15px;">⚙️ СОСТАВ СБОРКИ</h3>
        <?php 
        $components_total = 0;
        foreach ($order_items as $item): 
            $item_total = $item['quantity'] * $item['price_at_order'];
            $components_total += $item_total;
        ?>
        <div class="receipt-row">
            <div>
                <strong><?= htmlspecialchars($item['component_name']) ?></strong><br>
                <small>Кол-во: <?= $item['quantity'] ?> шт.</small>
            </div>
            <div class="receipt-value">
                <?= number_format($item['price_at_order'], 2) ?> ₽ × <?= $item['quantity'] ?> = 
                <strong><?= number_format($item_total, 2) ?> ₽</strong>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="receipt-row" style="border-top: 2px solid #333; margin-top: 10px;">
            <div class="receipt-label">ИТОГО за комплектующие:</div>
            <div class="receipt-value"><?= number_format($components_total, 2) ?> ₽</div>
        </div>
    </div>
    
    <!-- Дополнительные услуги -->
    <div class="receipt-section">
        <h3 style="color: var(--danger-red); margin-bottom: 15px;">🛠️ ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ</h3>
        
        <!-- Сборка -->
        <?php if ($order['assembly_fee'] > 0): ?>
        <div class="receipt-row">
            <div class="receipt-label">Профессиональная сборка:</div>
            <div class="receipt-value">+<?= number_format($order['assembly_fee'], 2) ?> ₽</div>
        </div>
        <?php endif; ?>
        
        <!-- Доставка -->
        <?php if ($order['delivery_needed']): ?>
        <div class="receipt-row">
            <div class="receipt-label">
                Доставка (<?= htmlspecialchars($order['zone_name'] ?? 'стандартная') ?>):
                <?php if ($order['delivery_address']): ?>
                <br><small><?= htmlspecialchars($order['delivery_address']) ?></small>
                <?php endif; ?>
            </div>
            <div class="receipt-value">
                <?= number_format($order['delivery_cost'], 2) ?> ₽
                <?php if ($order['delivery_time_hours']): ?>
                <br><small>Срок: <?= $order['delivery_time_hours'] ?> ч.</small>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Гарантия -->
        <?php if ($order['warranty_id']): ?>
        <div class="receipt-row">
            <div class="receipt-label">
                Гарантия <?= htmlspecialchars($order['warranty_type']) ?> 
                (<?= $order['warranty_period_months'] ?> мес.):
                <?php if ($order['warranty_desc']): ?>
                <br><small><?= htmlspecialchars($order['warranty_desc']) ?></small>
                <?php endif; ?>
            </div>
            <div class="receipt-value">+<?= number_format($order['warranty_cost'], 2) ?> ₽</div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Итоговая сумма -->
    <div class="receipt-section" style="background: #f8f8f8; border: 3px solid var(--danger-red);">
        <div class="receipt-row">
            <div class="receipt-label" style="font-size: 1.5rem;">ВСЕГО К ОПЛАТЕ:</div>
            <div class="receipt-value receipt-total">
                <?= number_format($order['total_cost'], 2) ?> ₽
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; padding: 15px; background: #ffebee; border-radius: 5px;">
            <strong style="color: var(--danger-red);">💳 СПОСОБЫ ОПЛАТЫ:</strong><br>
            Банковская карта • PayPal • Наличные • Криптовалюта
        </div>
    </div>
    
    <!-- Информация о магазине -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
        <strong>Танковая База Комплектующих</strong><br>
        ИНН 1234567890, ОГРН 1234567890123<br>
        Адрес: г. Москва, ул. Танковая, д. 1<br>
        Телефон: +7 (800) 123-45-67<br>
        Email: info@tankbase.ru<br>
        Сайт: https://tankbase.ru
        <div style="margin-top: 15px; font-size: 0.9rem;">
            Квитанция сформирована автоматически.<br>
            Сохраните её для предъявления в сервисном центре.
        </div>
    </div>
    
    <!-- QR-код для оплаты -->
    <div style="text-align: center; margin-top: 30px;">
        <div style="display: inline-block; padding: 15px; background: white; border: 2px dashed #333;">
            <div style="width: 150px; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                <div style="text-align: center;">
                    <div style="font-size: 3rem;">💰</div>
                    <div style="font-size: 0.8rem;">QR-код оплаты</div>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 0.9rem;">
                Отсканируйте для быстрой оплаты
            </div>
        </div>
    </div>
</div>

<!-- Кнопки действий -->
<div style="max-width: 800px; margin: 30px auto; text-align: center;">
    <button onclick="window.print()" class="btn btn-success" style="font-size: 1.2rem; padding: 15px 30px;">
        🖨️ Распечатать квитанцию
    </button>
    <a href="orders.php" class="btn" style="font-size: 1.2rem; padding: 15px 30px;">
        ↩️ Вернуться к заказам
    </a>
    <button onclick="downloadReceipt()" class="btn btn-edit" style="font-size: 1.2rem; padding: 15px 30px;">
        📥 Скачать PDF
    </button>
</div>

<div class="tank-footer">
    Танковая база данных © 2024 | Квитанция #<?= $order['order_id'] ?> | 
    Дата: <?= date('d.m.Y H:i:s') ?>
</div>

<script>
    // Включить сканирующую линию
    document.querySelector('.scanline').style.display = 'block';
    
    // Функция для "скачивания" PDF (имитация)
    function downloadReceipt() {
        alert('Функция экспорта в PDF будет реализована в следующем обновлении!');
        // Здесь можно интегрировать библиотеку для генерации PDF
        // Например: window.jsPDF или отправить запрос на сервер
    }
    
    // Автоматический скролл для печати
    window.addEventListener('load', function() {
        if (window.location.hash === '#print') {
            window.print();
        }
    });
    
    // Добавляем номер страницы при печати
    window.addEventListener('beforeprint', function() {
        document.body.innerHTML += `
            <style>
                @media print {
                    @page {
                        margin: 20mm;
                        @bottom-right {
                            content: "Страница " counter(page) " из " counter(pages);
                            font-size: 10pt;
                            color: #666;
                        }
                    }
                    .receipt-container {
                        page-break-inside: avoid;
                    }
                }
            </style>
        `;
    });
</script>
<!-- CYBERPUNK EFFECTS SCRIPT -->
    <script src="anime-effects.js"></script>
</body>
</html>