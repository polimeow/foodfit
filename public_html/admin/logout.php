<?php
require_once '../config.php';

// Уничтожаем сессию администратора
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// Перенаправляем на страницу входа
header('Location: login.php');
exit();
?>