<?php
// Единый навбар для всех страниц. Ожидает: $isLoggedIn, $clientName, $clientRole (если не заданы — подставляются по умолчанию)
$nav_current = basename($_SERVER['PHP_SELF']);
$nav_is_receipt = ($nav_current === 'order_receipt.php');
// Квитанция — считаем активным раздел «Заказы»
if ($nav_is_receipt) {
    $nav_current = 'orders.php';
}
$nav_logged_in = isset($isLoggedIn) ? $isLoggedIn : isset($_SESSION['client_id']);
$nav_client_name = $clientName ?? $_SESSION['client_name'] ?? '';
$nav_client_role = $clientRole ?? $_SESSION['client_role'] ?? '';
?>
<div class="menu">
    <nav class="menu-nav">
        <a href="index.php" class="<?= $nav_current === 'index.php' ? 'active' : '' ?> icon-tank">Главная</a>
        
        <?php if ($nav_logged_in): ?>
            <a href="orders.php" class="<?= $nav_current === 'orders.php' ? 'active' : '' ?> icon-military">Заказы</a>
            <?php if ($nav_client_role === 'admin'): ?>
                <a href="clients.php" class="<?= $nav_current === 'clients.php' ? 'active' : '' ?> icon-tank">Клиенты</a>
                <a href="components.php" class="<?= $nav_current === 'components.php' ? 'active' : '' ?> icon-military">Комплектующие</a>
                <a href="categories.php" class="<?= $nav_current === 'categories.php' ? 'active' : '' ?> icon-ammo">Категории</a>
                <a href="delivery_zones.php" class="<?= $nav_current === 'delivery_zones.php' ? 'active' : '' ?> icon-tank">Доставка</a>
                <a href="warranties.php" class="<?= $nav_current === 'warranties.php' ? 'active' : '' ?> icon-military">Гарантии</a>
            <?php endif; ?>
            
            <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                <?php if ($nav_is_receipt): ?>
                    <a href="#" onclick="window.print(); return false;" class="icon-ammo" style="padding: 12px 24px;">🖨️ Печать</a>
                <?php endif; ?>
                <span style="color: var(--digital-green);">
                    🎖️ <?= htmlspecialchars($nav_client_name) ?>
                </span>
                <a href="logout.php" class="btn btn-delete" style="padding: 5px 10px;">Выход</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="<?= $nav_current === 'login.php' ? 'active' : '' ?> icon-military">Вход</a>
            <a href="register.php" class="<?= $nav_current === 'register.php' ? 'active' : '' ?> icon-ammo">Регистрация</a>
            <?php if ($nav_is_receipt): ?>
                <a href="#" onclick="window.print(); return false;" class="icon-ammo">🖨️ Печать</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>
