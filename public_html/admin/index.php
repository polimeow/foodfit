<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Получаем расширенную статистику
$users_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$orders_count = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
$active_plans = $pdo->query("SELECT COUNT(*) as count FROM meal_plans WHERE is_active = TRUE")->fetch()['count'];
$revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'delivered'")->fetch()['revenue'];

// Статистика за сегодня
$today_users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
$today_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
$today_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'delivered'")->fetch()['revenue'];

// Статистика за последние 7 дней
$week_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count'];
$week_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'delivered'")->fetch()['revenue'];

// Последние заказы
$recent_orders = $pdo->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Статистика по статусам заказов
$order_statuses = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
")->fetchAll();

// Популярные программы
$popular_plans = $pdo->query("
    SELECT mp.title, COUNT(oi.id) as order_count
    FROM order_items oi
    JOIN meal_plans mp ON oi.meal_plan_id = mp.id
    GROUP BY mp.id, mp.title
    ORDER BY order_count DESC
    LIMIT 5
")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h2>Панель управления</h2>
<p style="color: #666; margin-bottom: 30px;">Обзор статистики и последних активностей</p>

<!-- Основная статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: bold; color: #007bff; margin-bottom: 10px;"><?php echo $users_count; ?></div>
        <div style="color: #666; font-size: 0.9rem;">Всего пользователей</div>
        <div style="color: #28a745; font-size: 0.8rem; margin-top: 5px;">+<?php echo $today_users; ?> сегодня</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: bold; color: #28a745; margin-bottom: 10px;"><?php echo $orders_count; ?></div>
        <div style="color: #666; font-size: 0.9rem;">Всего заказов</div>
        <div style="color: #28a745; font-size: 0.8rem; margin-top: 5px;">+<?php echo $today_orders; ?> сегодня</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: bold; color: #6f42c1; margin-bottom: 10px;"><?php echo $active_plans; ?></div>
        <div style="color: #666; font-size: 0.9rem;">Активных программ</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: bold; color: #ffc107; margin-bottom: 10px;"><?php echo number_format($revenue, 0, ',', ' '); ?> ₽</div>
        <div style="color: #666; font-size: 0.9rem;">Общая выручка</div>
        <div style="color: #28a745; font-size: 0.8rem; margin-top: 5px;">+<?php echo number_format($today_revenue, 0, ',', ' '); ?> ₽ сегодня</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Последние заказы -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333;">Последние заказы</h3>
            <a href="orders.php" style="color: #007bff; text-decoration: none; font-weight: 500;">Все заказы →</a>
        </div>
        
        <?php if (empty($recent_orders)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">Заказов пока нет</p>
        <?php else: ?>
            <div style="display: grid; gap: 15px;">
                <?php foreach ($recent_orders as $order): 
                    $status_labels = [
                        'pending' => ['text' => 'Ожидает', 'color' => '#ffc107'],
                        'confirmed' => ['text' => 'Подтвержден', 'color' => '#17a2b8'],
                        'preparing' => ['text' => 'Готовится', 'color' => '#007bff'],
                        'delivering' => ['text' => 'В пути', 'color' => '#6f42c1'],
                        'delivered' => ['text' => 'Доставлен', 'color' => '#28a745'],
                        'cancelled' => ['text' => 'Отменен', 'color' => '#dc3545']
                    ];
                    $status = $status_labels[$order['status']];
                ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                    <div style="flex: 1;">
                        <div style="font-weight: bold; margin-bottom: 5px;">
                            Заказ #<?php echo $order['id']; ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?php echo e($order['user_name']); ?> • <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <div style="font-weight: bold; color: #28a745; margin-bottom: 5px;">
                            <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                        </div>
                        <span style="
                            background: <?php echo $status['color']; ?>;
                            color: white;
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-size: 0.8rem;
                        ">
                            <?php echo $status['text']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Статистика -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статистика</h3>
        
        <div style="display: grid; gap: 15px;">
            <div style="text-align: center; padding: 15px; background: #e7f3ff; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;"><?php echo $week_orders; ?></div>
                <div style="color: #666; font-size: 0.9rem;">Заказов за 7 дней</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?php echo number_format($week_revenue, 0, ',', ' '); ?> ₽</div>
                <div style="color: #666; font-size: 0.9rem;">Выручка за 7 дней</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;">
                    <?php echo $orders_count > 0 ? number_format($revenue / $orders_count, 0, ',', ' ') : '0'; ?> ₽
                </div>
                <div style="color: #666; font-size: 0.9rem;">Средний чек</div>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Статусы заказов -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статусы заказов</h3>
        
        <?php if (empty($order_statuses)): ?>
            <p style="color: #666; text-align: center;">Нет данных</p>
        <?php else: ?>
            <div style="display: grid; gap: 10px;">
                <?php 
                $status_info = [
                    'pending' => ['text' => 'Ожидает подтверждения', 'color' => '#ffc107'],
                    'confirmed' => ['text' => 'Подтвержден', 'color' => '#17a2b8'],
                    'preparing' => ['text' => 'Готовится', 'color' => '#007bff'],
                    'delivering' => ['text' => 'В пути', 'color' => '#6f42c1'],
                    'delivered' => ['text' => 'Доставлен', 'color' => '#28a745'],
                    'cancelled' => ['text' => 'Отменен', 'color' => '#dc3545']
                ];
                
                foreach ($order_statuses as $status): 
                    $status_data = $status_info[$status['status']];
                ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="
                            width: 12px;
                            height: 12px;
                            border-radius: 50%;
                            background: <?php echo $status_data['color']; ?>;
                        "></div>
                        <span><?php echo $status_data['text']; ?></span>
                    </div>
                    <div style="font-weight: bold; color: #333;">
                        <?php echo $status['count']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Популярные программы -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Популярные программы</h3>
        
        <?php if (empty($popular_plans)): ?>
            <p style="color: #666; text-align: center;">Нет данных</p>
        <?php else: ?>
            <div style="display: grid; gap: 10px;">
                <?php foreach ($popular_plans as $plan): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    <div style="font-weight: 500;">
                        <?php echo e($plan['title']); ?>
                    </div>
                    <div style="
                        background: #007bff;
                        color: white;
                        padding: 4px 8px;
                        border-radius: 12px;
                        font-size: 0.8rem;
                        font-weight: bold;
                    ">
                        <?php echo $plan['order_count']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Быстрые действия -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 30px;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Быстрые действия</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="meal_plans.php?action=create" style="
            background: #28a745;
            color: white;
            padding: 15px;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            transition: background 0.3s ease;
        " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
            ➕ Добавить программу
        </a>
        
        <a href="orders.php" style="
            background: #007bff;
            color: white;
            padding: 15px;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            transition: background 0.3s ease;
        " onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
            📦 Просмотреть заказы
        </a>
        
        <a href="promo_codes.php" style="
            background: #6f42c1;
            color: white;
            padding: 15px;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            transition: background 0.3s ease;
        " onmouseover="this.style.background='#5a3790'" onmouseout="this.style.background='#6f42c1'">
            🎫 Управление промо-кодами
        </a>
        
        <?php if (canManageUsers()): ?>
        <a href="users.php" style="
            background: #17a2b8;
            color: white;
            padding: 15px;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            transition: background 0.3s ease;
        " onmouseover="this.style.background='#138496'" onmouseout="this.style.background='#17a2b8'">
            👥 Управление пользователями
        </a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>