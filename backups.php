<?php
require_once 'functions.php';
requireAdmin();

$root = __DIR__;
$backupDir = $root . DIRECTORY_SEPARATOR . 'backup';

// Создание бэкапа по кнопке
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $timestamp = date('Ymd_His');
    $target = $backupDir . DIRECTORY_SEPARATOR . $timestamp;
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    mkdir($target, 0755, true);
    $toCopy = [];
    foreach (scandir($root) ?: [] as $name) {
        if ($name === '.' || $name === '..' || in_array($name, ['selen', 'backup', 'node_modules', '.git', 'vendor'], true)) {
            continue;
        }
        $path = $root . DIRECTORY_SEPARATOR . $name;
        if (is_file($path) && (preg_match('/\.(php|css)$/', $name))) {
            $toCopy[] = [$path, $name];
        } elseif (is_dir($path) && !in_array($name, ['screenshots', 'backup'], true)) {
            foreach (scandir($path) ?: [] as $sub) {
                if (substr($sub, -4) === '.php') {
                    $toCopy[] = [$path . DIRECTORY_SEPARATOR . $sub, $name . DIRECTORY_SEPARATOR . $sub];
                }
            }
        }
    }
    $copied = 0;
    foreach (array_slice($toCopy, 0, 200) as $item) {
        list($src, $rel) = $item;
        if (is_file($src)) {
            $dest = $target . DIRECTORY_SEPARATOR . $rel;
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (@copy($src, $dest)) {
                $copied++;
            }
        }
    }
    header('Location: backups.php?created=1&count=' . $copied);
    exit;
}

// Список бэкапов (папки в backup/)
$backups = [];
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $backupDir . DIRECTORY_SEPARATOR . $name;
        if (is_dir($path)) {
            $count = 0;
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile()) {
                    $count++;
                }
            }
            $backups[$name] = $count;
        }
    }
    krsort($backups, SORT_STRING);
}

$viewId = isset($_GET['view']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['view']) : null;
$viewFiles = [];
if ($viewId && isset($backups[$viewId])) {
    $viewPath = $backupDir . DIRECTORY_SEPARATOR . $viewId;
    $baseLen = strlen($viewPath) + 1;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewPath, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $viewFiles[] = substr($f->getPathname(), $baseLen);
        }
    }
    sort($viewFiles);
}

$pageTitle = 'Бэкапы сайта';
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
        .backups-page { max-width: 900px; margin: 0 auto; padding: 20px; }
        .backups-nav { margin-bottom: 20px; }
        .backups-actions { margin-bottom: 24px; }
        .backups-list { background: #1a1a2e; border-radius: 12px; padding: 20px; border: 1px solid #2d2d44; }
        .backups-list h2 { color: var(--digital-green, #00ff88); margin-top: 0; }
        .backup-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid #2d2d44; }
        .backup-item:last-child { border-bottom: none; }
        .backup-item a { color: var(--digital-green); }
        .backup-files { background: #0f0f1a; padding: 12px; border-radius: 8px; margin-top: 12px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 13px; }
        .backup-files li { padding: 2px 0; }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .msg.ok { background: #1b3d1b; color: #90ee90; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="backups-page">
    <div class="backups-nav">
        <a href="index.php" class="btn">← Главная</a>
    </div>

    <h1 style="color: var(--digital-green);">💾 Бэкапы сайта</h1>

    <?php if (isset($_GET['created'])): ?>
        <div class="msg ok">Бэкап создан. Скопировано файлов: <?= (int)($_GET['count'] ?? 0) ?></div>
    <?php endif; ?>

    <div class="backups-actions">
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn btn-success">Создать бэкап сейчас</button>
        </form>
        <p style="color: #888; margin-top: 8px;">При нажатии текущие .php и .css копируются в папку backup с датой и временем.</p>
    </div>

    <?php if ($viewId): ?>
        <div class="backups-list">
            <h2>Просмотр бэкапа: <?= htmlspecialchars($viewId) ?></h2>
            <p><a href="backups.php">← К списку бэкапов</a></p>
            <p>Файлов: <?= count($viewFiles) ?></p>
            <ul class="backup-files">
                <?php foreach ($viewFiles as $f): ?>
                    <li><?= htmlspecialchars($f) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="backups-list">
            <h2>Список бэкапов</h2>
            <?php if (empty($backups)): ?>
                <p style="color: #888;">Пока нет бэкапов. Нажмите «Создать бэкап сейчас» или запустите тестирование (бекап создаётся при DO_BACKUP=1).</p>
            <?php else: ?>
                <?php foreach ($backups as $id => $count): ?>
                    <?php
                    $d = substr($id, 0, 8);
                    $t = substr($id, 9, 6);
                    $dateStr = (strlen($d) === 8 && strlen($t) === 6)
                        ? substr($d, 6, 2) . '.' . substr($d, 4, 2) . '.' . substr($d, 0, 4) . ' ' . substr($t, 0, 2) . ':' . substr($t, 2, 2) . ':' . substr($t, 4, 2)
                        : $id;
                    ?>
                    <div class="backup-item">
                        <span><strong><?= htmlspecialchars($dateStr) ?></strong> — файлов: <?= $count ?></span>
                        <a href="backups.php?view=<?= urlencode($id) ?>">Просмотр</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
