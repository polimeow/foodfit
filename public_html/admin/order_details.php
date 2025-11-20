<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Получаем основную информацию о заказе
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email, u.phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Получаем информацию о примененном промо-коде
$stmt_promo = $pdo->prepare("
    SELECT upc.discount_amount, pc.code, pc.discount_type, pc.discount_value
    FROM used_promo_codes upc 
    JOIN promo_codes pc ON upc.promo_code_id = pc.id 
    WHERE upc.order_id = ?
");
$stmt_promo->execute([$order_id]);
$promo_info = $stmt_promo->fetch();

// Получаем элементы заказа
$stmt = $pdo->prepare("
    SELECT oi.*, mp.title, mp.calories, mp.goal_id, ng.name as goal_name
    FROM order_items oi 
    JOIN meal_plans mp ON oi.meal_plan_id = mp.id 
    LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
    WHERE oi.order_id = ?
    ORDER BY oi.day_of_week
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $admin_notes, $order_id]);
    
    header('Location: order_details.php?id=' . $order_id . '&updated=1');
    exit();
}

// Статусы заказа
$status_labels = [
    'pending' => ['text' => 'Ожидает подтверждения', 'color' => '#ffc107', 'class' => 'warning'],
    'confirmed' => ['text' => 'Подтвержден', 'color' => '#17a2b8', 'class' => 'info'],
    'preparing' => ['text' => 'Готовится', 'color' => '#007bff', 'class' => 'primary'],
    'delivering' => ['text' => 'В пути', 'color' => '#6f42c1', 'class' => 'secondary'],
    'delivered' => ['text' => 'Доставлен', 'color' => '#28a745', 'class' => 'success'],
    'cancelled' => ['text' => 'Отменен', 'color' => '#dc3545', 'class' => 'danger']
];
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px;">
        <div>
            <h2>Детали заказа #<?php echo $order['id']; ?></h2>
            <p style="color: #666; margin: 0;">Создан: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
        </div>
        <a href="orders.php" style="
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">← Назад к заказам</a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            Статус заказа успешно обновлен!
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <!-- Информация о заказе -->
        <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Информация о заказе</h3>
            
            <div style="display: grid; gap: 15px;">
                <div>
                    <strong>Статус:</strong><br>
                    <span style="
                        background: <?php echo $status_labels[$order['status']]['color']; ?>;
                        color: white;
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 0.9rem;
                        font-weight: 500;
                        display: inline-block;
                        margin-top: 5px;
                    ">
                        <?php echo $status_labels[$order['status']]['text']; ?>
                    </span>
                </div>
                
                <div>
                    <strong>Общая сумма:</strong><br>
                    <span style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                        <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                    </span>
                </div>
                
                <?php if ($promo_info): ?>
                <div>
                    <strong>Промо-код:</strong><br>
                    <div style="
                        background: #d4edda;
                        padding: 10px;
                        border-radius: 4px;
                        margin-top: 5px;
                        border-left: 4px solid #28a745;
                    ">
                        <div style="font-weight: bold; color: #155724;">
                            <?php echo e($promo_info['code']); ?> 
                            (<?php echo $promo_info['discount_type'] === 'percentage' ? $promo_info['discount_value'] . '%' : number_format($promo_info['discount_value'], 0, ',', ' ') . ' ₽'; ?>)
                        </div>
                        <div style="color: #0f5132;">
                            Скидка: <strong><?php echo number_format($promo_info['discount_amount'], 0, ',', ' '); ?> ₽</strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <strong>Дата доставки:</strong><br>
                    <?php echo date('d.m.Y', strtotime($order['delivery_date'])); ?>
                </div>
                
                <div>
                    <strong>Время доставки:</strong><br>
                    <?php echo e($order['delivery_interval']); ?>
                </div>
                
                <?php if ($order['customer_notes']): ?>
                <div>
                    <strong>Примечания клиента:</strong><br>
                    <div style="
                        background: #f8f9fa;
                        padding: 12px;
                        border-radius: 4px;
                        margin-top: 5px;
                        border-left: 4px solid #007bff;
                    ">
                        <?php echo nl2br(e($order['customer_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($order['admin_notes']): ?>
                <div>
                    <strong>Заметки администратора:</strong><br>
                    <div style="
                        background: #fff3cd;
                        padding: 12px;
                        border-radius: 4px;
                        margin-top: 5px;
                        border-left: 4px solid #ffc107;
                    ">
                        <?php echo nl2br(e($order['admin_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Информация о клиенте -->
        <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Информация о клиенте</h3>
            
            <div style="display: grid; gap: 15px;">
                <div>
                    <strong>Имя:</strong><br>
                    <?php echo e($order['user_name']); ?>
                </div>
                
                <div>
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo e($order['email']); ?>" style="color: #007bff;">
                        <?php echo e($order['email']); ?>
                    </a>
                </div>
                
                <?php if ($order['phone']): ?>
                <div>
                    <strong>Телефон:</strong><br>
                    <a href="tel:<?php echo e($order['phone']); ?>" style="color: #007bff;">
                        <?php echo e($order['phone']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div>
                    <strong>Адрес доставки:</strong><br>
                    <?php echo e($order['delivery_address']); ?>
                </div>
                
                <div>
                    <strong>ID пользователя:</strong><br>
                    <a href="user_details.php?id=<?php echo $order['user_id']; ?>" style="color: #007bff;">
                        #<?php echo $order['user_id']; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Программы питания в заказе -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 30px;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Программы питания</h3>
        
        <?php if (empty($order_items)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">Программы не найдены</p>
        <?php else: ?>
            <div style="display: grid; gap: 15px;">
                <?php
                $days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                $subtotal = 0;
                foreach ($order_items as $item): 
                    $subtotal += $item['price'];
                ?>
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 6px;
                    border-left: 4px solid #007bff;
                ">
                    <div style="flex: 1;">
                        <div style="font-weight: bold; margin-bottom: 5px;">
                            <?php echo e($item['title']); ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            <span style="margin-right: 15px;"><?php echo $item['calories']; ?> ккал</span>
                            <span style="margin-right: 15px;"><?php echo e($item['goal_name']); ?></span>
                            <span style="
                                background: #6c757d;
                                color: white;
                                padding: 2px 8px;
                                border-radius: 12px;
                                font-size: 0.8rem;
                            ">
                                <?php echo $days[$item['day_of_week'] - 1] ?? 'День ' . $item['day_of_week']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <div style="font-size: 1.1rem; font-weight: bold; color: #28a745;">
                            <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            Количество: <?php echo $item['quantity']; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Итого -->
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    background: #e7f3ff;
                    border-radius: 6px;
                    border-left: 4px solid #007bff;
                    margin-top: 10px;
                ">
                    <div style="font-size: 1.2rem; font-weight: bold;">
                        Общая сумма заказа:
                    </div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                        <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                    </div>
                </div>
                
                <?php if ($promo_info): ?>
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    background: #d4edda;
                    border-radius: 6px;
                    border-left: 4px solid #28a745;
                ">
                    <div style="font-size: 1.1rem; font-weight: bold; color: #155724;">
                        Скидка по промо-коду "<?php echo e($promo_info['code']); ?>":
                    </div>
                    <div style="font-size: 1.3rem; font-weight: bold; color: #28a745;">
                        -<?php echo number_format($promo_info['discount_amount'], 0, ',', ' '); ?> ₽
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Управление заказом -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Управление заказом</h3>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Изменение статуса:</label>
                    <select name="status" style="
                        width: 100%;
                        padding: 10px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 1rem;
                    ">
                        <?php foreach ($status_labels as $value => $status_info): ?>
                            <option value="<?php echo $value; ?>" 
                                <?php echo ($order['status'] === $value) ? 'selected' : ''; ?>>
                                <?php echo $status_info['text']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Примечания администратора:</label>
                    <textarea name="admin_notes" style="
                        width: 100%;
                        padding: 10px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 1rem;
                        height: 100px;
                        resize: vertical;
                    " placeholder="Внутренние заметки по заказу..."><?php echo e($order['admin_notes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 15px;">
                <button type="submit" name="update_status" style="
                    background: #28a745;
                    color: white;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.3s ease;
                " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                    💾 Сохранить изменения
                </button>
                
                <a href="orders.php" style="
                    background: #6c757d;
                    color: white;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                ">
                    Отмена
                </a>
            </div>
        </form>
    </div>

    <!-- История изменений -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 30px;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Информация о заказе</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #666;">Создан</div>
                <div style="font-weight: bold;"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #666;">Обновлен</div>
                <div style="font-weight: bold;">
                    <?php echo $order['updated_at'] ? date('d.m.Y H:i', strtotime($order['updated_at'])) : '—'; ?>
                </div>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #666;">Кол-во программ</div>
                <div style="font-weight: bold;"><?php echo count($order_items); ?></div>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #666;">ID заказа</div>
                <div style="font-weight: bold;">#<?php echo $order['id']; ?></div>
            </div>
            
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #666;">ID клиента</div>
                <div style="font-weight: bold;">#<?php echo $order['user_id']; ?></div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>