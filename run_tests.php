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
