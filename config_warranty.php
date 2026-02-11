<?php
// Конфигурация для БД с гарантиями
$host_warranty = '134.90.167.42:10306'; // третий сервер
$db_warranty   = 'project_Plotnikov';
$user_warranty = 'Plotnikov';
$pass_warranty = 'I(jo7(2qDnMtjh6F';
$charset = 'utf8mb4';

$dsn_warranty = "mysql:host=$host_warranty;dbname=$db_warranty;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo_warranty = new PDO($dsn_warranty, $user_warranty, $pass_warranty, $options);
} catch (PDOException $e) {
    die("Ошибка подключения к БД гарантий: " . $e->getMessage());
}
?>