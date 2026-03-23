<?php
require_once 'functions.php';
requireAdmin();

$reportsDir = __DIR__ . '/selen/reports';
$selectedRun = $_GET['run'] ?? 'latest_result.json';
$reportPath = $reportsDir . DIRECTORY_SEPARATOR . $selectedRun;
$report = null;
if (file_exists($reportPath)) {
    $raw = file_get_contents($reportPath);
    $report = $raw ? json_decode($raw, true) : null;
}

if (!$report) {
    http_response_code(404);
    echo "Отчёт не найден.";
    exit;
}

$ts = $report['timestamp'] ?? '';
$results = $report['results'] ?? [];
$total = (int)($report['total'] ?? 0);
$okCount = (int)($report['ok_count'] ?? 0);
$rate = (float)($report['rate'] ?? 0);
$duration = (float)($report['duration_sec'] ?? 0);
$codeQuality = $report['code_quality'] ?? null;
$codeComplexity = $report['code_complexity'] ?? null;
$loadStats = $report['load_stats'] ?? null;
$networkStats = $report['network_stats'] ?? null;
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печатный отчёт тестирования</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #fff; color: #000; }
        .print-wrap { max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #000; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .ok { color: #008000; }
        .err { color: #cc0000; }
        @media print {
            a { color: inherit; text-decoration: none; }
            .no-print { display: none !important; }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</head>
<body>
<div class="print-wrap">
    <div class="no-print" style="margin-bottom: 10px;">
        <a href="report.php?run=<?= htmlspecialchars($selectedRun) ?>" class="btn">← Назад к интерфейсу</a>
    </div>

    <h1>Отчёт тестирования</h1>
    <p>Дата прогона: <strong><?= htmlspecialchars($ts) ?></strong></p>
    <p>Успешность: <strong><?= (int)$rate ?>%</strong> (<?= $okCount ?>/<?= $total ?>), время: <?= (int)$duration ?> с</p>

    <h2>Результаты тестов</h2>
    <table>
        <thead>
        <tr>
            <th>№</th>
            <th>Критерий</th>
            <th>Тест</th>
            <th>Статус</th>
            <th>Детали</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['criterion'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['test'] ?? '') ?></td>
                <td class="<?= !empty($r['success']) ? 'ok' : 'err' ?>"><?= htmlspecialchars($r['status'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['details'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($codeQuality || $codeComplexity): ?>
        <h2>Код и сложность</h2>
        <?php if ($codeQuality): ?>
            <h3>Стандарты (php -l, PSR)</h3>
            <p>php -l: <?= !empty($codeQuality['php_syntax_ok']) ? 'OK' : 'Ошибки' ?>,
                файлов: <?= (int)($codeQuality['php_syntax_checked'] ?? 0) ?>,
                ошибок: <?= (int)count($codeQuality['php_syntax_errors'] ?? []) ?></p>
            <?php if (isset($codeQuality['psr_ok'])): ?>
                <p>PSR-1/PSR-2: <?= !empty($codeQuality['psr_ok']) ? 'OK' : 'есть замечания' ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($codeComplexity && !empty($codeComplexity['totals'])): ?>
            <?php $t = $codeComplexity['totals']; ?>
            <h3>Сводка сложности</h3>
            <ul>
                <li>Файлов: <?= (int)($t['files'] ?? 0) ?></li>
                <li>Символов: <?= (int)($t['chars'] ?? 0) ?></li>
                <li>Операторов: <?= (int)($t['operators'] ?? 0) ?>, операндов: <?= (int)($t['operands'] ?? 0) ?></li>
                <li>Циклов: <?= (int)($t['loops'] ?? 0) ?>, условий: <?= (int)($t['conditions'] ?? 0) ?></li>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($loadStats): ?>
        <h2>Нагрузочное тестирование</h2>
        <table>
            <thead><tr><th>Пользователей</th><th>Среднее время (мс)</th><th>Успешность (%)</th></tr></thead>
            <tbody>
            <?php foreach ($loadStats as $s): ?>
                <tr>
                    <td><?= (int)($s['concurrent'] ?? 0) ?></td>
                    <td><?= (int)($s['avg_ms'] ?? 0) ?></td>
                    <td><?= number_format((float)($s['success_rate'] ?? 0), 1) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($networkStats): ?>
        <h2>Сеть</h2>
        <ul>
            <li>Запросов: <?= (int)($networkStats['requests'] ?? 0) ?></li>
            <li>Успешность: <?= number_format((float)($networkStats['success_rate'] ?? 0), 1) ?>%, потеря пакетов: <?= number_format((float)($networkStats['packet_loss'] ?? 0), 1) ?>%</li>
            <li>Задержка (мс): мин <?= (int)($networkStats['latency_min'] ?? 0) ?>, макс <?= (int)($networkStats['latency_max'] ?? 0) ?>, ср <?= (int)($networkStats['latency_avg'] ?? 0) ?>, p95 <?= (int)($networkStats['latency_p95'] ?? 0) ?></li>
            <li>Скорость: <?= number_format((float)($networkStats['rps'] ?? 0), 1) ?> запрос/с, ~<?= number_format((float)($networkStats['kbps'] ?? 0), 1) ?> Кбит/с</li>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>

