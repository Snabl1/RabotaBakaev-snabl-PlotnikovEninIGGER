<?php
require_once 'functions.php';
requireAdmin();

$reportsDir = __DIR__ . '/selen/reports';
$runFiles = [];
if (is_dir($reportsDir)) {
    foreach (glob($reportsDir . '/result_*.json') ?: [] as $f) {
        $runFiles[] = basename($f);
    }
    rsort($runFiles); // новее первые
}
$runOptions = [];
if (file_exists($reportsDir . DIRECTORY_SEPARATOR . 'latest_result.json')) {
    $runOptions['latest_result.json'] = 'Последний (актуальный)';
}
$num = count($runFiles);
foreach ($runFiles as $name) {
    if (preg_match('/result_(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})\.json/', $name, $m)) {
        $runOptions[$name] = 'Прогон №' . $num . ' — ' . $m[3] . '.' . $m[2] . '.' . $m[1] . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6];
        $num--;
    } else {
        $runOptions[$name] = $name;
    }
}
$selectedRun = $_GET['run'] ?? 'latest_result.json';
if (!isset($runOptions[$selectedRun])) {
    $selectedRun = array_key_first($runOptions) ?: 'latest_result.json';
}
$reportPath = $reportsDir . DIRECTORY_SEPARATOR . $selectedRun;
$report = null;
if (file_exists($reportPath)) {
    $raw = file_get_contents($reportPath);
    $report = $raw ? json_decode($raw, true) : null;
}

$pageTitle = 'Отчёт тестирования';
$isLoggedIn = true;
$clientName = $_SESSION['client_name'] ?? '';
$clientRole = $_SESSION['client_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        .report-page { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .report-nav { margin-bottom: 24px; }
        .report-nav a { margin-right: 12px; }
        .report-section { background: #1a1a2e; border-radius: 12px; padding: 20px; margin-bottom: 24px; border: 1px solid #2d2d44; }
        .report-section h2 { color: var(--digital-green, #00ff88); margin-top: 0; border-bottom: 1px solid #2d2d44; padding-bottom: 10px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #2d2d44; }
        .report-table th { color: #888; }
        .report-table .ok { color: #4CAF50; }
        .report-table .err { color: #f44336; }
        .report-screenshots { display: flex; flex-wrap: wrap; gap: 16px; }
        .report-screenshot { flex: 0 0 auto; }
        .report-screenshot img { max-width: 320px; height: auto; border: 1px solid #2d2d44; border-radius: 8px; display: block; }
        .report-screenshot figcaption { font-size: 12px; color: #888; margin-top: 4px; }
        .report-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
        .report-stat { background: #0f0f1a; padding: 12px; border-radius: 8px; text-align: center; }
        .report-stat .value { font-size: 1.4rem; color: var(--digital-green, #00ff88); }
        .report-stat .label { font-size: 0.85rem; color: #888; }
        .report-no-data { color: #888; padding: 40px; text-align: center; }
        .report-graph { max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #2d2d44; margin-top: 12px; }
        .report-tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .report-tabs a { padding: 8px 16px; background: #2d2d44; color: #ccc; border-radius: 8px; text-decoration: none; }
        .report-tabs a:hover, .report-tabs a.active { background: var(--digital-green, #00ff88); color: #000; }
        .report-select-run { margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .report-select-run label { color: #888; }
        .report-select-run select { padding: 8px 12px; border-radius: 8px; background: #2d2d44; color: #fff; border: 1px solid #444; min-width: 280px; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="report-page">
    <div class="report-nav">
        <a href="index.php" class="btn">← Главная</a>
    </div>

    <h1 style="color: var(--digital-green);">📊 Отчёт тестирования (Selenium)</h1>

    <div class="report-section" style="margin-top: 12px;">
        <h2>▶ Запуск тестирования</h2>
        <form method="post" action="run_tests.php" style="display: grid; gap: 12px;">
            <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                <label style="display:flex; gap:8px; align-items:center;">
                    Набор:
                    <select name="test_suite" style="padding: 6px 10px; border-radius: 8px; background: #2d2d44; color: #fff; border: 1px solid #444;">
                        <option value="smoke">smoke (быстро)</option>
                        <option value="regression">regression (средне)</option>
                        <option value="full" selected>full (всё)</option>
                    </select>
                </label>
                <label style="display:flex; gap:8px; align-items:center;">
                    Ретраи Selenium:
                    <select name="selenium_retries" style="padding: 6px 10px; border-radius: 8px; background: #2d2d44; color: #fff; border: 1px solid #444;">
                        <option value="0">0</option>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </label>
                <span style="color:#888;">smoke пропускает нагрузку/сеть и часть сценариев</span>
            </div>
            <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                <strong style="color:#888;">Критерии:</strong>
                <label><input type="checkbox" name="criteria[]" value="validity" checked> Валидность</label>
                <label><input type="checkbox" name="criteria[]" value="verification" checked> Верификация</label>
                <label><input type="checkbox" name="criteria[]" value="custom" checked> Свой (безопасность)</label>
            </div>
            <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                <label><input type="checkbox" name="do_backup" value="1" checked> Делать бэкап (backup/)</label>
                <label><input type="checkbox" name="auto_fix_psr" value="1"> Авто-фикс PSR-2 (phpcbf)</label>
                <label><input type="checkbox" name="auto_git" value="1" checked> Автосейв в git</label>
                <label style="display:flex; gap:8px; align-items:center;">
                    Схема git:
                    <select name="git_scheme" style="padding: 6px 10px; border-radius: 8px; background: #2d2d44; color: #fff; border: 1px solid #444;">
                        <option value="per_run" selected>1 коммит за прогон</option>
                        <option value="before_after_fix">до/после авто-фикса</option>
                    </select>
                </label>
            </div>
            <div>
                <button type="submit" class="btn btn-success">🚀 Запустить тесты сейчас</button>
                <span style="color:#888; margin-left: 10px;">После завершения обновите страницу — появится новый прогон.</span>
            </div>
        </form>
    </div>

    <?php if (!empty($_SESSION['test_error'])): ?>
        <div class="report-section" style="border-color: #f44336;">
            <p style="color: #f44336;"><?= htmlspecialchars($_SESSION['test_error']) ?></p>
        </div>
        <?php unset($_SESSION['test_error']); ?>
    <?php endif; ?>

    <?php if (count($runOptions) > 0): ?>
    <div class="report-select-run">
        <label for="runSelect">Прогон тестирования:</label>
        <select id="runSelect" onchange="window.location.href='report.php?run='+encodeURIComponent(this.value)">
            <?php foreach ($runOptions as $file => $label): ?>
                <option value="<?= htmlspecialchars($file) ?>" <?= $selectedRun === $file ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($report): ?>
            <a href="report_print.php?run=<?= urlencode($selectedRun) ?>" target="_blank" class="btn" style="margin-left: 12px;">📄 Печатная версия / PDF</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!$report): ?>
        <div class="report-section">
            <p class="report-no-data">Отчёт пока отсутствует. Запустите тестирование кнопкой выше — после завершения здесь появятся результаты, скриншоты, графики и статистика.</p>
        </div>
    <?php else:
        $ts = $report['timestamp'] ?? '';
        $screenshotDir = $report['screenshot_dir'] ?? '';
        $results = $report['results'] ?? [];
        $total = (int)($report['total'] ?? 0);
        $okCount = (int)($report['ok_count'] ?? 0);
        $rate = (float)($report['rate'] ?? 0);
        $duration = (float)($report['duration_sec'] ?? 0);
        $loadGraph = $report['load_graph'] ?? '';
        $loadStats = $report['load_stats'] ?? null;
        $networkGraph = $report['network_graph'] ?? '';
        $networkStats = $report['network_stats'] ?? null;
        $codeQuality = $report['code_quality'] ?? null;
        $codeComplexity = $report['code_complexity'] ?? null;
        $backupInfo = $report['backup_info'] ?? null;
        $gitInfo = $report['git_info'] ?? null;
    ?>
        <p style="color: #888;">Дата прогона: <?= htmlspecialchars($ts) ?> · Успешность: <strong><?= (int)$rate ?>%</strong> (<?= $okCount ?>/<?= $total ?>) · Время: <?= (int)$duration ?> с</p>

        <div class="report-tabs">
            <a href="#results">Результаты</a>
            <a href="#screenshots">Скриншоты</a>
            <a href="#code">Код</a>
            <a href="#load">Нагрузка</a>
            <a href="#network">Сеть</a>
        </div>

        <section class="report-section" id="results">
            <h2>Результаты тестов</h2>
            <table class="report-table">
                <thead>
                    <tr><th>№</th><th>Критерий</th><th>Тест</th><th>Статус</th><th>Детали</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td style="color:#888;"><?= htmlspecialchars($r['criterion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['test'] ?? '') ?></td>
                            <td class="<?= !empty($r['success']) ? 'ok' : 'err' ?>"><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['details'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php if ($codeQuality || $codeComplexity || $gitInfo || $backupInfo): ?>
        <section class="report-section" id="code">
            <h2>Код: стандарты, сложность, бэкап, git</h2>
            <?php if ($codeQuality): ?>
                <h3 style="margin: 10px 0; color:#ccc;">PSR / синтаксис</h3>
                <div class="report-stats-grid">
                    <div class="report-stat">
                        <span class="value"><?= !empty($codeQuality['php_syntax_ok']) ? 'OK' : 'ERR' ?></span><br>
                        <span class="label">php -l</span>
                    </div>
                    <div class="report-stat">
                        <span class="value"><?= (int)($codeQuality['php_syntax_checked'] ?? 0) ?></span><br>
                        <span class="label">проверено файлов</span>
                    </div>
                    <div class="report-stat">
                        <span class="value"><?= (int)count($codeQuality['php_syntax_errors'] ?? []) ?></span><br>
                        <span class="label">ошибок синтаксиса</span>
                    </div>
                    <div class="report-stat">
                        <span class="value"><?= isset($codeQuality['psr_ok']) ? (!empty($codeQuality['psr_ok']) ? 'OK' : 'WARN') : 'N/A' ?></span><br>
                        <span class="label">PSR-1/PSR-2</span>
                    </div>
                </div>
                <?php if (!empty($codeQuality['psr_output_tail'])): ?>
                    <pre style="white-space: pre-wrap; background:#0f0f1a; padding:12px; border-radius:8px; border:1px solid #2d2d44; margin-top:12px; color:#bbb;"><?= htmlspecialchars($codeQuality['psr_output_tail']) ?></pre>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($codeComplexity && !empty($codeComplexity['totals'])): ?>
                <h3 style="margin: 18px 0 10px; color:#ccc;">Метрика сложности (счётчики)</h3>
                <?php $t = $codeComplexity['totals']; ?>
                <div class="report-stats-grid">
                    <div class="report-stat"><span class="value"><?= (int)($t['files'] ?? 0) ?></span><br><span class="label">файлов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['chars'] ?? 0) ?></span><br><span class="label">символов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['operators'] ?? 0) ?></span><br><span class="label">операторов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['operands'] ?? 0) ?></span><br><span class="label">операндов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['loops'] ?? 0) ?></span><br><span class="label">циклов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['conditions'] ?? 0) ?></span><br><span class="label">условий</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['spaces'] ?? 0) ?></span><br><span class="label">пробелов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($t['newlines'] ?? 0) ?></span><br><span class="label">энтеров</span></div>
                </div>
                <?php if (!empty($codeComplexity['top_files'])): ?>
                    <table class="report-table" style="margin-top: 16px;">
                        <thead><tr><th>Файл</th><th>Опер.</th><th>Опернд.</th><th>Циклы</th><th>Условия</th></tr></thead>
                        <tbody>
                        <?php foreach ($codeComplexity['top_files'] as $f): ?>
                            <tr>
                                <td style="font-family: monospace;"><?= htmlspecialchars($f['file'] ?? '') ?></td>
                                <td><?= (int)($f['operators'] ?? 0) ?></td>
                                <td><?= (int)($f['operands'] ?? 0) ?></td>
                                <td><?= (int)($f['loops'] ?? 0) ?></td>
                                <td><?= (int)($f['conditions'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($backupInfo): ?>
                <h3 style="margin: 18px 0 10px; color:#ccc;">Бэкапы</h3>
                <pre style="white-space: pre-wrap; background:#0f0f1a; padding:12px; border-radius:8px; border:1px solid #2d2d44; color:#bbb;"><?= htmlspecialchars(json_encode($backupInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>

            <?php if ($gitInfo): ?>
                <h3 style="margin: 18px 0 10px; color:#ccc;">git автосейв</h3>
                <pre style="white-space: pre-wrap; background:#0f0f1a; padding:12px; border-radius:8px; border:1px solid #2d2d44; color:#bbb;"><?= htmlspecialchars(json_encode($gitInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($screenshotDir && count(array_filter($results, function ($r) { return !empty($r['screenshot']); })) > 0): ?>
        <section class="report-section" id="screenshots">
            <h2>Скриншоты тестирования</h2>
            <div class="report-screenshots">
                <?php foreach ($results as $r):
                    if (empty($r['screenshot'])) continue;
                    $imgSrc = htmlspecialchars($screenshotDir . '/' . $r['screenshot']);
                ?>
                    <figure class="report-screenshot">
                        <a href="<?= $imgSrc ?>" target="_blank"><img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($r['test']) ?>"></a>
                        <figcaption><?= htmlspecialchars($r['test']) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($loadGraph || $loadStats): ?>
        <section class="report-section" id="load">
            <h2>Загрузочное тестирование (п.6)</h2>
            <?php if ($loadGraph && file_exists(__DIR__ . '/' . $loadGraph)): ?>
                <img src="<?= htmlspecialchars($loadGraph) ?>" alt="График нагрузки" class="report-graph">
            <?php endif; ?>
            <?php if (!empty($loadStats)): ?>
                <table class="report-table" style="margin-top: 16px;">
                    <thead><tr><th>Пользователей</th><th>Среднее время (мс)</th><th>Успешность (%)</th></tr></thead>
                    <tbody>
                        <?php foreach ($loadStats as $s): ?>
                            <tr>
                                <td><?= (int)($s['concurrent'] ?? 0) ?></td>
                                <td><?= (int)($s['avg_ms'] ?? 0) ?></td>
                                <td><?= number_format((float)($s['success_rate'] ?? 0), 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($networkGraph || $networkStats): ?>
        <section class="report-section" id="network">
            <h2>Тестирование сети (п.7)</h2>
            <?php if ($networkStats): ?>
                <div class="report-stats-grid">
                    <div class="report-stat"><span class="value"><?= (int)($networkStats['requests'] ?? 0) ?></span><br><span class="label">Запросов</span></div>
                    <div class="report-stat"><span class="value"><?= number_format((float)($networkStats['success_rate'] ?? 0), 1) ?>%</span><br><span class="label">Успешность</span></div>
                    <div class="report-stat"><span class="value"><?= number_format((float)($networkStats['packet_loss'] ?? 0), 1) ?>%</span><br><span class="label">Потеря пакетов</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($networkStats['latency_min'] ?? 0) ?></span><br><span class="label">Задержка мин (мс)</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($networkStats['latency_max'] ?? 0) ?></span><br><span class="label">Задержка макс (мс)</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($networkStats['latency_avg'] ?? 0) ?></span><br><span class="label">Задержка средн (мс)</span></div>
                    <div class="report-stat"><span class="value"><?= (int)($networkStats['latency_p95'] ?? 0) ?></span><br><span class="label">p95 (мс)</span></div>
                    <div class="report-stat"><span class="value"><?= number_format((float)($networkStats['rps'] ?? 0), 1) ?></span><br><span class="label">Запросов/с</span></div>
                    <div class="report-stat"><span class="value"><?= number_format((float)($networkStats['kbps'] ?? 0), 1) ?></span><br><span class="label">Кбит/с</span></div>
                </div>
            <?php endif; ?>
            <?php if ($networkGraph && file_exists(__DIR__ . '/' . $networkGraph)): ?>
                <img src="<?= htmlspecialchars($networkGraph) ?>" alt="График задержек" class="report-graph">
            <?php endif; ?>
        </section>
        <?php endif; ?>

    <?php endif; ?>
</div>
</body>
</html>
