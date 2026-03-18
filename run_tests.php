<?php
require_once 'functions.php';
requireAdmin();

set_time_limit(600);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;

putenv('BASE_URL=' . $baseUrl);
// UTF-8 для вывода Python (иначе на Windows cp1251 падает на эмодзи)
putenv('PYTHONIOENCODING=utf-8');

// Параметры интерфейса (report.php) — требования t2.txt
$criteria = $_POST['criteria'] ?? null; // массив или null
if (is_array($criteria) && count($criteria) > 0) {
    $criteriaClean = [];
    foreach ($criteria as $c) {
        $c = strtolower(trim((string)$c));
        if (in_array($c, ['validity', 'verification', 'custom'], true)) {
            $criteriaClean[] = $c;
        }
    }
    if (count($criteriaClean) > 0) {
        putenv('TEST_CRITERIA=' . implode(',', $criteriaClean));
    }
}

$doBackup = isset($_POST['do_backup']) && $_POST['do_backup'] === '1';
putenv('DO_BACKUP=' . ($doBackup ? '1' : '0'));

$autoFix = isset($_POST['auto_fix_psr']) && $_POST['auto_fix_psr'] === '1';
putenv('AUTO_FIX_PSR=' . ($autoFix ? '1' : '0'));

$autoGit = !isset($_POST['auto_git']) || $_POST['auto_git'] === '1'; // по умолчанию включено
putenv('AUTO_GIT=' . ($autoGit ? '1' : '0'));

$gitScheme = $_POST['git_scheme'] ?? 'per_run';
$gitScheme = in_array($gitScheme, ['per_run', 'before_after_fix'], true) ? $gitScheme : 'per_run';
putenv('GIT_SAVE_SCHEME=' . $gitScheme);

// Набор тестов: smoke/regression/full (влияет на то, что запускает Python-раннер)
$suite = strtolower(trim((string)($_POST['test_suite'] ?? 'full')));
if (!in_array($suite, ['smoke', 'regression', 'full'], true)) {
    $suite = 'full';
}
putenv('TEST_SUITE=' . $suite);

// Ретраи Selenium для нестабильных прогонов (0..3)
$retries = (int)($_POST['selenium_retries'] ?? 1);
if ($retries < 0) $retries = 0;
if ($retries > 3) $retries = 3;
putenv('SELENIUM_RETRIES=' . $retries);

$projectRoot = __DIR__;
$selenDir = $projectRoot . DIRECTORY_SEPARATOR . 'selen';
$mainPy = $selenDir . DIRECTORY_SEPARATOR . 'main.py';
$output = [];
$returnCode = 0;

if (!is_dir($selenDir) || !file_exists($mainPy)) {
    $_SESSION['test_error'] = 'Папка selen или файл main.py не найдены.';
    header('Location: report.php');
    exit;
}

// Запуск как из папки selen: python main.py
chdir($selenDir);
$cmd = 'python main.py 2>&1';
exec($cmd, $output, $returnCode);

if ($returnCode !== 0) {
    $_SESSION['test_error'] = 'Тесты завершились с кодом ' . $returnCode . '. ' . implode("\n", array_slice($output, -5));
}

header('Location: report.php');
exit;
