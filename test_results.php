<?php
require_once 'functions.php';
requireAuth();

// Только для админов
if (!isset($_SESSION['client_id']) || $_SESSION['client_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Создаем таблицу для результатов тестов
$pdo->exec("
    CREATE TABLE IF NOT EXISTS test_results (
        test_id INT AUTO_INCREMENT PRIMARY KEY,
        test_name VARCHAR(255) NOT NULL,
        test_type VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL,
        duration FLOAT,
        error_message TEXT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Получаем последние результаты
$recent_tests = $pdo->query("SELECT * FROM test_results ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Статистика
$total_tests = $pdo->query("SELECT COUNT(*) FROM test_results")->fetchColumn();
$passed_tests = $pdo->query("SELECT COUNT(*) FROM test_results WHERE status = 'passed'")->fetchColumn();
$failed_tests = $pdo->query("SELECT COUNT(*) FROM test_results WHERE status = 'failed'")->fetchColumn();
$avg_duration = $pdo->query("SELECT AVG(duration) FROM test_results WHERE duration IS NOT NULL")->fetchColumn() ?? 0;

// Проверка подключения к БД
$db_status = '✅ OK';
$db_latency = 0;
try {
    $start = microtime(true);
    $pdo->query("SELECT 1");
    $db_latency = round((microtime(true) - $start) * 1000, 2);
} catch (Exception $e) {
    $db_status = '❌ ERROR';
}

// Проверка подключения к БД доставки
$delivery_db_status = '✅ OK';
try {
    $pdo_delivery->query("SELECT 1");
} catch (Exception $e) {
    $delivery_db_status = '❌ ERROR';
}

// Проверка подключения к БД гарантий
$warranty_db_status = '✅ OK';
try {
    $pdo_warranty->query("SELECT 1");
} catch (Exception $e) {
    $warranty_db_status = '❌ ERROR';
}

// Статистика по таблицам
$tables_stats = [
    'clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'components' => $pdo->query("SELECT COUNT(*) FROM components")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'order_items' => $pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-anime-world.css">
    <title>🧪 Результаты Тестов - ANIME WORLD</title>
    <style>
        .test-dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card-test {
            background: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,245,228,0.95));
            border-radius: 20px;
            border: 3px solid var(--anime-orange);
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }
        .stat-number-test {
            font-family: 'Fredoka', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .stat-number-test.passed {
            background: linear-gradient(135deg, #26de81, #20bf6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-number-test.failed {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label-test {
            color: var(--anime-purple);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .db-status-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,245,228,0.95));
            border-radius: 20px;
            border: 3px solid var(--anime-pink);
            padding: 20px;
            margin-bottom: 20px;
        }
        .db-status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed var(--anime-pink);
        }
        .db-status-title {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            color: var(--anime-red);
            font-size: 1.2rem;
        }
        .db-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .db-item {
            background: rgba(255,255,255,0.7);
            padding: 15px;
            border-radius: 12px;
            border: 2px solid var(--anime-orange);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .db-name {
            font-weight: 700;
            color: var(--anime-dark);
        }
        .db-value {
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            color: var(--anime-green);
        }
        .test-results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        .test-results-table th {
            background: var(--gradient-sidebar);
            color: white;
            padding: 15px;
            text-align: left;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
        }
        .test-results-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 126, 185, 0.3);
        }
        .test-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .test-status.passed {
            background: rgba(38, 222, 129, 0.2);
            color: #26de81;
            border: 2px solid #26de81;
        }
        .test-status.failed {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
        }
        .test-type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            background: linear-gradient(145deg, var(--anime-purple), var(--anime-pink));
            color: white;
        }
        .run-tests-btn {
            padding: 15px 30px;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php $isLoggedIn = true; $clientName = $_SESSION['client_name'] ?? ''; $clientRole = 'admin'; include 'navbar.php'; ?>
    
    <div class="main-container">
        <main class="main-content" style="grid-column: 1 / -1;">
            <div class="test-dashboard">
                <div class="content-section">
                    <div class="section-header">
                        <span class="section-icons">🧪</span>
                        <h2 class="section-title">РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ</h2>
                        <span class="section-icons">🧪</span>
                    </div>
                    
                    <!-- Кнопка запуска тестов -->
                    <div style="text-align: center; margin-bottom: 30px;">
                        <a href="run_tests.php" class="btn btn-success run-tests-btn">
                            🚀 ЗАПУСТИТЬ ВСЕ ТЕСТЫ
                        </a>
                        <a href="check_connections.php" class="btn btn-edit run-tests-btn">
                            🔍 ПРОВЕРИТЬ ПОДКЛЮЧЕНИЯ
                        </a>
                    </div>
                    
                    <!-- СТАТИСТИКА -->
                    <div class="stats-row">
                        <div class="stat-card-test">
                            <div class="stat-label-test">📊 Всего тестов</div>
                            <div class="stat-number-test"><?= $total_tests ?></div>
                        </div>
                        <div class="stat-card-test">
                            <div class="stat-label-test">✅ Успешно</div>
                            <div class="stat-number-test passed"><?= $passed_tests ?></div>
                        </div>
                        <div class="stat-card-test">
                            <div class="stat-label-test">❌ Провалено</div>
                            <div class="stat-number-test failed"><?= $failed_tests ?></div>
                        </div>
                        <div class="stat-card-test">
                            <div class="stat-label-test">⏱️ Среднее время</div>
                            <div class="stat-number-test"><?= number_format($avg_duration, 2) ?> сек</div>
                        </div>
                    </div>
                    
                    <!-- СТАТУС БАЗ ДАННЫХ -->
                    <div class="db-status-card">
                        <div class="db-status-header">
                            <span class="db-status-title">🗄️ СТАТУС БАЗ ДАННЫХ</span>
                            <span style="color: var(--anime-purple); font-size: 0.9rem;">
                                Проверка: <?= date('d.m.Y H:i:s') ?>
                            </span>
                        </div>
                        <div class="db-list">
                            <div class="db-item">
                                <span class="db-name">📦 Основная БД</span>
                                <span class="db-value"><?= $db_status ?> (<?= $db_latency ?> мс)</span>
                            </div>
                            <div class="db-item">
                                <span class="db-name">🚚 БД Доставки</span>
                                <span class="db-value"><?= $delivery_db_status ?></span>
                            </div>
                            <div class="db-item">
                                <span class="db-name">💝 БД Гарантий</span>
                                <span class="db-value"><?= $warranty_db_status ?></span>
                            </div>
                            <div class="db-item">
                                <span class="db-name">👥 Клиенты</span>
                                <span class="db-value"><?= $tables_stats['clients'] ?> записей</span>
                            </div>
                            <div class="db-item">
                                <span class="db-name">🛒 Комплектующие</span>
                                <span class="db-value"><?= $tables_stats['components'] ?> записей</span>
                            </div>
                            <div class="db-item">
                                <span class="db-name">📦 Заказы</span>
                                <span class="db-value"><?= $tables_stats['orders'] ?> записей</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ПОСЛЕДНИЕ ТЕСТЫ -->
                    <h3 style="color: var(--anime-red); margin: 30px 0 15px;">📋 Последние тесты</h3>
                    
                    <table class="test-results-table">
                        <thead>
                            <tr>
                                <th>Название теста</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th>Время</th>
                                <th>Дата</th>
                                <th>Ошибка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_tests) > 0): ?>
                                <?php foreach ($recent_tests as $test): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--anime-dark);"><?= htmlspecialchars($test['test_name']) ?></strong>
                                        <?php if ($test['details']): ?>
                                        <br><small style="color: var(--anime-purple);"><?= htmlspecialchars(substr($test['details'], 0, 100)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="test-type-badge"><?= htmlspecialchars($test['test_type']) ?></span></td>
                                    <td>
                                        <span class="test-status <?= $test['status'] ?>">
                                            <?= $test['status'] === 'passed' ? '✅ PASSED' : '❌ FAILED' ?>
                                        </span>
                                    </td>
                                    <td><?= $test['duration'] ? number_format($test['duration'], 2) . ' сек' : '-' ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($test['created_at'])) ?></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                        <?= $test['error_message'] ? htmlspecialchars(substr($test['error_message'], 0, 50)) . '...' : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--anime-purple);">
                                        Тесты ещё не запускались. Нажмите "Запустить все тесты" 💕
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <div class="anime-footer">
        🌸 ANIME WORLD 2026 💕 | Результаты тестирования
    </div>
    
    <script src="anime-effects.js"></script>
</body>
</html>
