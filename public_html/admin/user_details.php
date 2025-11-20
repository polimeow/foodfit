<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

if (!canManageUsers()) {
    header('Location: index.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    header('Location: users.php');
    exit();
}

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Получаем статистику пользователя
$orders_count = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?")->execute([$user_id])->fetch()['count'];
$total_spent = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'delivered'")->execute([$user_id])->fetch()['total'];
$pending_orders = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'confirmed', 'preparing')")->execute([$user_id])->fetch()['count'];

// Последние заказы
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: between; align-items: start; margin-bottom: 30px;">
    <div>
        <h2>Профиль пользователя</h2>
        <p style="color: #666; margin: 0;"><?php echo e($user['name']); ?> • ID: <?php echo $user['id']; ?></p>
    </div>
    <a href="users.php" style="
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
    ">← Назад к пользователям</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Основная информация -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Основная информация</h3>
        
        <div style="display: grid; gap: 15px;">
            <div>
                <strong>Имя:</strong><br>
                <?php echo e($user['name']); ?>
            </div>
            
            <div>
                <strong>Email:</strong><br>
                <a href="mailto:<?php echo e($user['email']); ?>" style="color: #007bff;">
                    <?php echo e($user['email']); ?>
                </a>
            </div>
            
            <?php if ($user['phone']): ?>
            <div>
                <strong>Телефон:</strong><br>
                <a href="tel:<?php echo e($user['phone']); ?>" style="color: #007bff;">
                    <?php echo e($user['phone']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <div>
                <strong>Адрес доставки:</strong><br>
                <?php echo $user['delivery_address'] ? e($user['delivery_address']) : '<span style="color: #666;">Не указан</span>'; ?>
            </div>
            
            <div>
                <strong>Дата регистрации:</strong><br>
                <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
            </div>
        </div>
    </div>
    
    <!-- Статистика -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статистика</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #e7f3ff; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;"><?php echo $orders_count; ?></div>
                <div style="color: #666; font-size: 0.9rem;">Всего заказов</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?php echo number_format($total_spent, 0, ',', ' '); ?> ₽</div>
                <div style="color: #666; font-size: 0.9rem;">Всего потрачено</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;"><?php echo $pending_orders; ?></div>
                <div style="color: #666; font-size: 0.9rem;">Текущие заказы</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 6px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545;">
                    <?php echo $orders_count > 0 ? number_format($total_spent / $orders_count, 0, ',', ' ') : '0'; ?> ₽
                </div>
                <div style="color: #666; font-size: 0.9rem;">Средний чек</div>
            </div>
        </div>
    </div>
</div>

<!-- Последние заказы -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #333;">Последние заказы</h3>
        <a href="user_orders.php?id=<?php echo $user_id; ?>" style="
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        ">Все заказы →</a>
    </div>
    
    <?php if (empty($recent_orders)): ?>
        <p style="color: #666; text-align: center; padding: 20px;">У пользователя пока нет заказов</p>
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
            <div style="
                display: flex;
                justify-content: between;
                align-items: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 6px;
                border-left: 4px solid <?php echo $status['color']; ?>;
            ">
                <div style="flex: 1;">
                    <div style="font-weight: bold; margin-bottom: 5px;">
                        Заказ #<?php echo $order['id']; ?>
                    </div>
                    <div style="color: #666; font-size: 0.9rem;">
                        <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?> • 
                        <?php echo e($order['delivery_address']); ?>
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

<!-- Действия -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 30px;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Действия</h3>
    
    <div style="display: flex; gap: 15px;">
        <a href="user_orders.php?id=<?php echo $user_id; ?>" style="
            background: #007bff;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">
            📦 Просмотреть все заказы
        </a>
        
        <a href="mailto:<?php echo e($user['email']); ?>" style="
            background: #17a2b8;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">
            ✉️ Написать на email
        </a>
        
        <?php if ($user['phone']): ?>
        <a href="tel:<?php echo e($user['phone']); ?>" style="
            background: #28a745;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">
            📞 Позвонить
        </a>
        <?php endif; ?>
        
        <button onclick="confirmDelete(<?php echo $user_id; ?>)" style="
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
        ">
            🗑️ Удалить пользователя
        </button>
    </div>
</div>

<script>
function confirmDelete(userId) {
    if (confirm('Вы уверены, что хотите удалить этого пользователя? Все его заказы также будут удалены.')) {
        window.location.href = 'user_delete.php?id=' + userId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>