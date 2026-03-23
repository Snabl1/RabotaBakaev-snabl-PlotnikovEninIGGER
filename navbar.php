<?php
// Единый навбар для всех страниц в стиле ANIME WORLD
$nav_current = basename($_SERVER['PHP_SELF']);
$nav_is_receipt = ($nav_current === 'order_receipt.php');
if ($nav_is_receipt) {
    $nav_current = 'orders.php';
}
$nav_logged_in = isset($isLoggedIn) ? $isLoggedIn : isset($_SESSION['client_id']);
$nav_client_name = $clientName ?? $_SESSION['client_name'] ?? '';
$nav_client_role = $clientRole ?? $_SESSION['client_role'] ?? '';
?>
<div class="anime-header">
    <div class="header-content">
        <div class="logo-section">
            <div class="logo-anime">ANIME WORLD</div>
            <div class="logo-mascot">🐱</div>
        </div>
        
        <nav class="anime-nav">
            <a href="index.php" class="<?= $nav_current === 'index.php' ? 'active' : '' ?>">🏠 Главная</a>

            <?php if ($nav_logged_in): ?>
                <a href="orders.php" class="<?= $nav_current === 'orders.php' ? 'active' : '' ?>">📦 Заказы</a>
                <?php if ($nav_client_role === 'admin'): ?>
                    <a href="clients.php" class="<?= $nav_current === 'clients.php' ? 'active' : '' ?>">👥 Клиенты</a>
                    <a href="components.php" class="<?= $nav_current === 'components.php' ? 'active' : '' ?>">👙 Товары</a>
                    <a href="categories.php" class="<?= $nav_current === 'categories.php' ? 'active' : '' ?>">🏷️ Категории</a>
                    <a href="delivery_zones.php" class="<?= $nav_current === 'delivery_zones.php' ? 'active' : '' ?>">🚚 Доставка</a>
                    <a href="warranties.php" class="<?= $nav_current === 'warranties.php' ? 'active' : '' ?>">💝 Гарантии</a>
                    <a href="admin_videos.php" class="<?= $nav_current === 'admin_videos.php' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #ff69b4, #ff1493); color: white; border-color: #ffb7c5;">🎬 Видео</a>
                    <a href="admin_content.php" class="<?= $nav_current === 'admin_content.php' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #a55eea, #8854d0); color: white; border-color: #d980fa;">🎨 Контент</a>
                    <a href="test_results.php" class="<?= $nav_current === 'test_results.php' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #26de81, #20bf6b); color: white; border-color: #7bed9f;">🧪 Тесты</a>
                <?php endif; ?>

                <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                    <?php if ($nav_is_receipt): ?>
                        <a href="#" onclick="window.print(); return false;" style="padding: 12px 24px;">🖨️ Печать</a>
                    <?php endif; ?>
                    <a href="forum.php" style="padding: 12px 24px; background: rgba(255,255,255,0.2); border-radius: 20px; color: white; text-decoration: none;">💬 Форум</a>
                    <a href="profile.php" style="padding: 12px 24px; background: rgba(255,255,255,0.2); border-radius: 20px; color: white; text-decoration: none;">👤 Профиль</a>
                    <span style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.3); font-weight: 700;">
                        💖 <?= htmlspecialchars($nav_client_name) ?>
                    </span>
                    <a href="logout.php" class="anime-nav" style="background: linear-gradient(145deg, #ff4757, #ff6b35); padding: 10px 20px;">Выход</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="<?= $nav_current === 'login.php' ? 'active' : '' ?>">🔐 Вход</a>
                <a href="register.php" class="<?= $nav_current === 'register.php' ? 'active' : '' ?>">💕 Регистрация</a>
                <?php if ($nav_is_receipt): ?>
                    <a href="#" onclick="window.print(); return false;">🖨️ Печать</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</div>
