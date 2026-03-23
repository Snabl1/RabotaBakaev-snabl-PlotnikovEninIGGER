<?php
// Конфигурация для БД с зонами доставки
$host_delivery = '134.90.167.42:10306'; // другой сервер
$db_delivery   = 'project_Plotnikov';
$user_delivery = 'Plotnikov';
$pass_delivery = 'I(jo7(2qDnMtjh6F';
$charset = 'utf8mb4';

$dsn_delivery = "mysql:host=$host_delivery;dbname=$db_delivery;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo_delivery = new PDO($dsn_delivery, $user_delivery, $pass_delivery, $options);
} catch (PDOException $e) {
    die("Ошибка подключения к БД доставки: " . $e->getMessage());
}
?>