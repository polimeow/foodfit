<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php');
    exit();
}

// Показываем сообщение об успешном заказе
if (isset($_SESSION['order_success'])) {
    $success_message = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

// Получаем заказы пользователя
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as items_count,
           (SELECT GROUP_CONCAT(mp.title SEPARATOR ', ') 
            FROM order_items oi 
            JOIN meal_plans mp ON oi.meal_plan_id = mp.id 
            WHERE oi.order_id = o.id) as plan_titles
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <h2>Мои заказы</h2>

    <?php if (isset($success_message)): ?>
        <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo e($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 40px;">
            <h3>У вас пока нет заказов</h3>
            <p>Перейдите в <a href="../catalog.php">каталог</a> чтобы сделать первый заказ</p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($orders as $order): 
                // Получаем информацию о примененном промо-коде
                $stmt_promo = $pdo->prepare("
                    SELECT upc.discount_amount, pc.code 
                    FROM used_promo_codes upc 
                    JOIN promo_codes pc ON upc.promo_code_id = pc.id 
                    WHERE upc.order_id = ?
                ");
                $stmt_promo->execute([$order['id']]);
                $promo_info = $stmt_promo->fetch();
                
                $status_labels = [
                    'pending' => ['text' => 'Ожидает подтверждения', 'color' => '#ffc107'],
                    'confirmed' => ['text' => 'Подтвержден', 'color' => '#17a2b8'],
                    'preparing' => ['text' => 'Готовится', 'color' => '#007bff'],
                    'delivering' => ['text' => 'В пути', 'color' => '#6f42c1'],
                    'delivered' => ['text' => 'Доставлен', 'color' => '#28a745'],
                    'cancelled' => ['text' => 'Отменен', 'color' => '#dc3545']
                ];
                $status = $status_labels[$order['status']] ?? $status_labels['pending'];
            ?>
                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #333;">Заказ #<?php echo $order['id']; ?></h3>
                            <p style="margin: 0; color: #666;">от <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="font-size: 1.25rem; font-weight: bold; color: #28a745;">
                                <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                            </div>
                            <div style="margin-top: 5px;">
                                <span style="background: <?php echo $status['color']; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                                    <?php echo $status['text']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($promo_info): ?>
                    <div style="color: #28a745; margin-bottom: 15px; padding: 10px; background: #d4edda; border-radius: 4px;">
                        <strong>✅ Применен промо-код "<?php echo e($promo_info['code']); ?>"</strong>
                        <div>Скидка: <?php echo number_format($promo_info['discount_amount'], 0, ',', ' '); ?> ₽</div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <strong>Адрес доставки:</strong><br>
                            <?php echo e($order['delivery_address']); ?>
                        </div>
                        <div>
                            <strong>Дата доставки:</strong><br>
                            <?php echo date('d.m.Y', strtotime($order['delivery_date'])); ?>, <?php echo e($order['delivery_interval']); ?>
                        </div>
                    </div>
                    
                    <?php if ($order['customer_notes']): ?>
                        <div style="margin-bottom: 15px;">
                            <strong>Ваши пожелания:</strong><br>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                <?php echo nl2br(e($order['customer_notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <strong>Программы в заказе:</strong><br>
                        <div style="color: #666; margin-top: 5px;">
                            <?php 
                            // Получаем детали заказа
                            $stmt_items = $pdo->prepare("
                                SELECT oi.*, mp.title, mp.calories 
                                FROM order_items oi 
                                JOIN meal_plans mp ON oi.meal_plan_id = mp.id 
                                WHERE oi.order_id = ?
                            ");
                            $stmt_items->execute([$order['id']]);
                            $items = $stmt_items->fetchAll();
                            
                            $days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                            foreach ($items as $item): 
                            ?>
                                <div style="margin: 5px 0; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong><?php echo e($item['title']); ?></strong>
                                            <div style="color: #666; font-size: 0.9rem;">
                                                <?php echo $days[$item['day_of_week'] - 1] ?? 'День ' . $item['day_of_week']; ?> • 
                                                <?php echo $item['calories']; ?> ккал
                                            </div>
                                        </div>
                                        <div style="font-weight: bold; color: #333;">
                                            <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] === 'delivering'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 4px; border-left: 4px solid #007bff;">
                            <strong>🚚 Заказ в пути</strong>
                            <div style="color: #666; font-size: 0.9rem;">
                                Ожидайте доставку в указанный интервал: <?php echo e($order['delivery_interval']); ?>
                            </div>
                        </div>
                    <?php elseif ($order['status'] === 'preparing'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                            <strong>👨🍳 Заказ готовится</strong>
                            <div style="color: #666; font-size: 0.9rem;">
                                Мы начали готовить ваш заказ. Скоро он будет у вас!
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>