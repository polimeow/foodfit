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

// Фильтрация заказов
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Формируем запрос для заказов
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Статистика заказов пользователя
$total_orders = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?")->execute([$user_id])->fetch()['count'];
$total_spent = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'delivered'")->execute([$user_id])->fetch()['total'];
$avg_order = $total_orders > 0 ? $total_spent / $total_orders : 0;
?>

<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: between; align-items: start; margin-bottom: 30px;">
    <div>
        <h2>Заказы пользователя</h2>
        <p style="color: #666; margin: 0;">
            <?php echo e($user['name']); ?> • 
            <a href="mailto:<?php echo e($user['email']); ?>" style="color: #007bff;"><?php echo e($user['email']); ?></a>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="user_details.php?id=<?php echo $user_id; ?>" style="
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">👤 Профиль</a>
        <a href="users.php" style="
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">← Назад</a>
    </div>
</div>

<!-- Статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $total_orders; ?></div>
        <div style="color: #666;">Всего заказов</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo number_format($total_spent, 0, ',', ' '); ?> ₽</div>
        <div style="color: #666;">Всего потрачено</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #6f42c1;"><?php echo number_format($avg_order, 0, ',', ' '); ?> ₽</div>
        <div style="color: #666;">Средний чек</div>
    </div>
</div>

<!-- Фильтры -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <input type="hidden" name="id" value="<?php echo $user_id; ?>">
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Статус заказа:</label>
            <select name="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Все статусы</option>
                <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Ожидает подтверждения</option>
                <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>>Подтвержден</option>
                <option value="preparing" <?php echo ($status_filter === 'preparing') ? 'selected' : ''; ?>>Готовится</option>
                <option value="delivering" <?php echo ($status_filter === 'delivering') ? 'selected' : ''; ?>>В пути</option>
                <option value="delivered" <?php echo ($status_filter === 'delivered') ? 'selected' : ''; ?>>Доставлен</option>
                <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Отменен</option>
            </select>
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата с:</label>
            <input type="date" name="date_from" value="<?php echo e($date_from); ?>" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата по:</label>
            <input type="date" name="date_to" value="<?php echo e($date_to); ?>" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" style="
                background: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
            ">
                🔍 Применить
            </button>
            <a href="user_orders.php?id=<?php echo $user_id; ?>" style="
                background: #6c757d;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                display: inline-flex;
                align-items: center;
            ">
                Сбросить
            </a>
        </div>
    </form>
</div>

<!-- Таблица заказов -->
<div style="background: white; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: #333;">История заказов</h3>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Заказы не найдены</h4>
            <p>Попробуйте изменить параметры фильтрации</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Заказ</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Доставка</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Сумма</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Статус</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
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
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 15px;">
                            <div style="font-weight: bold; margin-bottom: 5px;">
                                #<?php echo $order['id']; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                            <?php if ($order['customer_notes']): ?>
                            <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                                💬 <?php echo e(substr($order['customer_notes'], 0, 50)); ?>...
                            </div>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <?php echo date('d.m.Y', strtotime($order['delivery_date'])); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo e($order['delivery_interval']); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                                🏠 <?php echo e(substr($order['delivery_address'], 0, 30)); ?>...
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="font-size: 1.2rem; font-weight: bold; color: #28a745;">
                                <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <span style="
                                background: <?php echo $status['color']; ?>;
                                color: white;
                                padding: 6px 12px;
                                border-radius: 20px;
                                font-size: 0.9rem;
                                font-weight: 500;
                                display: inline-block;
                            ">
                                <?php echo $status['text']; ?>
                            </span>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 8px;">
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                   style="
                                       background: #007bff;
                                       color: white;
                                       padding: 6px 12px;
                                       text-decoration: none;
                                       border-radius: 4px;
                                       font-size: 0.9rem;
                                   ">
                                    Детали
                                </a>
                                
                                <a href="../personal/orders.php?order_id=<?php echo $order['id']; ?>" 
                                   target="_blank"
                                   style="
                                       background: #17a2b8;
                                       color: white;
                                       padding: 6px 12px;
                                       text-decoration: none;
                                       border-radius: 4px;
                                       font-size: 0.9rem;
                                   ">
                                    Клиент
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Сводка по статусам -->
<?php
$status_stats = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
")->execute([$user_id])->fetchAll();
?>

<?php if (!empty($status_stats)): ?>
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 30px;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статистика по статусам</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
        <?php 
        $status_info = [
            'pending' => ['text' => 'Ожидает', 'color' => '#ffc107'],
            'confirmed' => ['text' => 'Подтвержден', 'color' => '#17a2b8'],
            'preparing' => ['text' => 'Готовится', 'color' => '#007bff'],
            'delivering' => ['text' => 'В пути', 'color' => '#6f42c1'],
            'delivered' => ['text' => 'Доставлен', 'color' => '#28a745'],
            'cancelled' => ['text' => 'Отменен', 'color' => '#dc3545']
        ];
        
        foreach ($status_stats as $stat): 
            $status = $status_info[$stat['status']];
        ?>
        <div style="text-align: center; padding: 15px; background: <?php echo $status['color']; ?>; color: white; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $stat['count']; ?></div>
            <div style="font-size: 0.9rem;"><?php echo $status['text']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>