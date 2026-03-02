<?php
// Старый экспорт в PDF через wkhtmltopdf убран, чтобы не вешать встроенный PHP-сервер.
// Теперь просто перенаправляем на печатную версию, из которой можно сохранить в PDF.
require_once 'functions.php';
requireAdmin();

$run = isset($_GET['run']) ? (string)$_GET['run'] : 'latest_result.json';
header('Location: report_print.php?run=' . urlencode($run));
exit;

