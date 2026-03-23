<?php
require_once 'config.php';
require_once 'config_delivery.php';
require_once 'config_warranty.php';

// Старт сессии если не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['client_id']);
}

// Получение текущего клиента
function getCurrentClient() {
    global $pdo;
    if (!isset($_SESSION['client_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    return $stmt->fetch();
}

// Проверка прав администратора
function isAdmin() {
    return isset($_SESSION['client_role']) && $_SESSION['client_role'] === 'admin';
}

// Требование авторизации
function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Требование прав администратора
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        die("Доступ запрещен! Требуются права администратора.");
    }
    // Авто-бэкап и автосейв в git при изменении кода (требование t2.txt)
    maybeAutoMaintenance();
}

// Остальные функции...
function safeDelete($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
    return $stmt->execute([$id]);
}

// Получение зон доставки
function getDeliveryZones() {
    global $pdo_delivery;
    try {
        return $pdo_delivery->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY min_distance")->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting delivery zones: " . $e->getMessage());
        return [];
    }
}

// Получение гарантий
function getWarranties($component_id = null) {
    global $pdo_warranty;
    
    try {
        if ($component_id) {
            $stmt = $pdo_warranty->prepare("
                SELECT * FROM warranties 
                WHERE component_id = ? OR component_id IS NULL 
                ORDER BY warranty_period_months DESC
            ");
            $stmt->execute([$component_id]);
            return $stmt->fetchAll();
        } else {
            return $pdo_warranty->query("
                SELECT * FROM warranties 
                ORDER BY warranty_period_months DESC
            ")->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Error getting warranties: " . $e->getMessage());
        return [];
    }
}

// Расчет стоимости доставки
function calculateDeliveryCost($zone_id, $distance = null) {
    global $pdo_delivery;
    
    try {
        $stmt = $pdo_delivery->prepare("SELECT * FROM delivery_zones WHERE zone_id = ?");
        $stmt->execute([$zone_id]);
        $zone = $stmt->fetch();
        
        if (!$zone) return 0;
        
        if ($distance === null) {
            return $zone['base_price'];
        }
        
        $extra_distance = max(0, $distance - $zone['min_distance']);
        return $zone['base_price'] + ($extra_distance * $zone['price_per_km']);
    } catch (Exception $e) {
        error_log("Error calculating delivery cost: " . $e->getMessage());
        return 0;
    }
}

// Расчет стоимости гарантии
function calculateWarrantyCost($warranty_id, $component_price) {
    global $pdo_warranty;
    
    try {
        $stmt = $pdo_warranty->prepare("SELECT price_multiplier FROM warranties WHERE warranty_id = ?");
        $stmt->execute([$warranty_id]);
        $warranty = $stmt->fetch();
        
        if (!$warranty) return 0;
        
        return $component_price * ($warranty['price_multiplier'] - 1);
    } catch (Exception $e) {
        error_log("Error calculating warranty cost: " . $e->getMessage());
        return 0;
    }
}

// Функция для получения записи
function getRecord($pdo, $table, $id_field, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $id_field = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Функция безопасного удаления с проверкой зависимостей
function safeDeleteWithCheck($pdo, $table, $id_field, $id, $dependencies = []) {
    $pdo->beginTransaction();
    try {
        foreach ($dependencies as $dep_table => $dep_field) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $dep_table WHERE $dep_field = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("Нельзя удалить запись, так как она используется в таблице $dep_table");
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Функция для редиректа с сообщением
function redirectWithMessage($url, $type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
}

// Генерация CSRF токена
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Обработка флеш-сообщений
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type_class = $flash['type'] == 'error' ? 'alert-error' : ($flash['type'] == 'warning' ? 'alert-warning' : 'alert-success');
        echo "<div class='alert $type_class'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}

// ---------------- Авто-бэкап и автосейв git ----------------

function appProjectRoot(): string {
    return __DIR__;
}

function appAutoBackupEnabled(): bool {
    $v = getenv('APP_AUTO_BACKUP');
    return $v === false ? true : !in_array(strtolower((string)$v), ['0', 'false', 'no'], true);
}

function appAutoGitEnabled(): bool {
    $v = getenv('APP_AUTO_GIT');
    return $v === false ? true : !in_array(strtolower((string)$v), ['0', 'false', 'no'], true);
}

function appAutosaveStatePath(): string {
    return appProjectRoot() . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . '.autosave_state.json';
}

function appReadAutosaveState(): array {
    $path = appAutosaveStatePath();
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    $data = $raw ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function appWriteAutosaveState(array $state): void {
    $dir = appProjectRoot() . DIRECTORY_SEPARATOR . 'backup';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents(appAutosaveStatePath(), json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function appCollectSourceFilesForSignature(string $root): array {
    $files = [];
    $excludeDirs = [
        $root . DIRECTORY_SEPARATOR . 'backup',
        $root . DIRECTORY_SEPARATOR . '.git',
        $root . DIRECTORY_SEPARATOR . 'vendor',
        $root . DIRECTORY_SEPARATOR . 'node_modules',
        $root . DIRECTORY_SEPARATOR . 'selen' . DIRECTORY_SEPARATOR . 'screenshots',
        $root . DIRECTORY_SEPARATOR . 'selen' . DIRECTORY_SEPARATOR . 'reports',
        $root . DIRECTORY_SEPARATOR . 'selen' . DIRECTORY_SEPARATOR . '__pycache__',
    ];

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        if (!$f->isFile()) continue;
        $path = $f->getPathname();
        $skip = false;
        foreach ($excludeDirs as $ex) {
            if (strpos($path, $ex) === 0) { $skip = true; break; }
        }
        if ($skip) continue;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'css', 'py', 'md', 'txt', 'json'], true)) continue;
        $files[] = $path;
    }
    sort($files, SORT_STRING);
    return $files;
}

function appComputeSourceSignature(): string {
    $root = appProjectRoot();
    $files = appCollectSourceFilesForSignature($root);
    $parts = [];
    foreach ($files as $path) {
        $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $mtime = @filemtime($path) ?: 0;
        $size = @filesize($path) ?: 0;
        $parts[] = $rel . '|' . $mtime . '|' . $size;
    }
    return hash('sha1', implode("\n", $parts));
}

function appCreateBackupSnapshot(string $reason = 'auto'): ?string {
    $root = appProjectRoot();
    $backupDir = $root . DIRECTORY_SEPARATOR . 'backup';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }

    $lockPath = $backupDir . DIRECTORY_SEPARATOR . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock) {
        @flock($lock, LOCK_EX);
    }

    $timestamp = date('Ymd_His');
    $target = $backupDir . DIRECTORY_SEPARATOR . $timestamp;
    if (!is_dir($target) && !@mkdir($target, 0755, true)) {
        if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
        return null;
    }

    $toCopy = [];
    foreach (scandir($root) ?: [] as $name) {
        if ($name === '.' || $name === '..' || in_array($name, ['selen', 'backup', 'node_modules', '.git', 'vendor'], true)) {
            continue;
        }
        $path = $root . DIRECTORY_SEPARATOR . $name;
        if (is_file($path) && preg_match('/\.(php|css)$/i', $name)) {
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
    foreach (array_slice($toCopy, 0, 500) as $item) {
        [$src, $rel] = $item;
        if (!is_file($src)) continue;
        $dest = $target . DIRECTORY_SEPARATOR . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (@copy($src, $dest)) {
            $copied++;
        }
    }

    @file_put_contents(
        $target . DIRECTORY_SEPARATOR . 'meta.json',
        json_encode(['reason' => $reason, 'copied' => $copied, 'created_at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
    return $timestamp;
}

function appShellExecSafe(string $cmd, string $cwd = null): array {
    $descriptor = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $env = $_ENV;
    // Без git config: задаём автора/коммитера через env
    $env['GIT_AUTHOR_NAME'] = $env['GIT_AUTHOR_NAME'] ?? 'AutoSave Bot';
    $env['GIT_AUTHOR_EMAIL'] = $env['GIT_AUTHOR_EMAIL'] ?? 'autosave@local';
    $env['GIT_COMMITTER_NAME'] = $env['GIT_COMMITTER_NAME'] ?? 'AutoSave Bot';
    $env['GIT_COMMITTER_EMAIL'] = $env['GIT_COMMITTER_EMAIL'] ?? 'autosave@local';

    $p = @proc_open($cmd, $descriptor, $pipes, $cwd ?: appProjectRoot(), $env);
    if (!is_resource($p)) {
        return ['code' => 127, 'out' => '', 'err' => 'proc_open failed'];
    }
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($p);
    return ['code' => (int)$code, 'out' => (string)$out, 'err' => (string)$err];
}

function appGitAvailable(): bool {
    $r = appShellExecSafe('git --version');
    return $r['code'] === 0;
}

function appEnsureGitRepo(): void {
    $root = appProjectRoot();
    if (is_dir($root . DIRECTORY_SEPARATOR . '.git')) return;
    if (!appGitAvailable()) return;
    appShellExecSafe('git init', $root);
    // Первичный коммит, если получится (игнорируются backup/ и отчёты через .gitignore)
    appShellExecSafe('git add -A', $root);
    appShellExecSafe('git commit -m "Initial autosave snapshot"', $root);
}

function appGitCommitIfChanged(string $message): bool {
    $root = appProjectRoot();
    if (!appGitAvailable()) return false;
    appEnsureGitRepo();
    $st = appShellExecSafe('git status --porcelain', $root);
    if (trim(($st['out'] ?? '') . ($st['err'] ?? '')) === '') {
        return false;
    }
    appShellExecSafe('git add -A', $root);
    $r = appShellExecSafe('git commit -m ' . escapeshellarg($message), $root);
    return $r['code'] === 0;
}

function maybeAutoMaintenance(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // Только админ и только если включено
    if (!isAdmin()) return;

    $state = appReadAutosaveState();
    $sig = appComputeSourceSignature();
    $prev = $state['signature'] ?? '';
    if ($sig === $prev) return;

    $state['signature'] = $sig;
    $state['updated_at'] = date('c');

    // 1) Авто-бэкап на каждое изменение кода
    if (appAutoBackupEnabled()) {
        $id = appCreateBackupSnapshot('auto_code_change');
        if ($id) {
            $state['last_backup'] = $id;
        }
    }

    // 2) Автосейв в git на каждое изменение кода
    if (appAutoGitEnabled()) {
        $msg = 'Auto-save code change ' . date('Y-m-d H:i:s');
        $ok = appGitCommitIfChanged($msg);
        $state['last_git_commit_ok'] = $ok;
        $state['last_git_commit_at'] = date('c');
    }

    appWriteAutosaveState($state);
}
?>