<?php
// admin/includes/header.php
require_once '../config.php';
require_once 'auth_check.php';
requireAdminAuth();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - ФитПаёк</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }
        .admin-header {
            background: #343a40;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav {
            background: #495057;
            padding: 1rem 2rem;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            margin-right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        .admin-nav a:hover {
            background: #6c757d;
        }
        .admin-main {
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .user-info {
            color: #adb5bd;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 1rem;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Панель управления ФитПаёк</h1>
        <div class="user-info">
            Вы вошли как: <strong><?php echo e($_SESSION['admin_username']); ?></strong> 
            (<?php echo e($_SESSION['admin_role'] === 'admin' ? 'Администратор' : 'Менеджер'); ?>)
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="index.php">Главная</a>
        <a href="statistics.php">📊 Статистика</a>
        <a href="meal_plans.php">Программы питания</a>
        <a href="orders.php">Заказы</a>
        <?php if (canManageUsers()): ?>
            <a href="users.php">Пользователи</a>
            <a href="admins.php">Администраторы</a>
        <?php endif; ?>
        <a href="promo_codes.php">Промо-коды</a>
        <a href="promo_usage.php">История промо-кодов</a>
    </nav>
    
    <main class="admin-main">
        <div class="container">