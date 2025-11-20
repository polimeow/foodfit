<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Обработка изменения статуса
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    header('Location: orders.php?updated=' . $order_id);
    exit();
}

// Фильтрация
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Формируем запрос
$sql = "SELECT o.*, u.name as user_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h2>Управление заказами</h2>

<!-- Фильтры -->
<form method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Статус:</label>
            <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
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
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Поиск:</label>
            <input type="text" name="search" value="<?php echo e($search); ?>" 
                   placeholder="ID заказа, имя или email"
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="display: flex; align-items: end; gap: 10px;">
            <button type="submit" style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
                Применить
            </button>
            <a href="orders.php" style="color: #6c757d; padding: 8px 0;">Сбросить</a>
        </div>
    </div>
</form>

<?php if (isset($_GET['updated'])): ?>
    <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        Статус заказа #<?php echo e($_GET['updated']); ?> успешно обновлен!
    </div>
<?php endif; ?>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
        <thead>
            <tr style="background: #343a40; color: white;">
                <th style="padding: 12px; text-align: left;">ID</th>
                <th style="padding: 12px; text-align: left;">Клиент</th>
                <th style="padding: 12px; text-align: left;">Дата доставки</th>
                <th style="padding: 12px; text-align: left;">Сумма</th>
                <th style="padding: 12px; text-align: left;">Статус</th>
                <th style="padding: 12px; text-align: left;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 12px;">
                    <strong>#<?php echo $order['id']; ?></strong><br>
                    <small style="color: #666;"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></small>
                </td>
                <td style="padding: 12px;">
                    <div><strong><?php echo e($order['user_name']); ?></strong></div>
                    <div style="color: #666; font-size: 0.9rem;">
                        <?php echo e($order['email']); ?><br>
                        <?php echo e($order['phone']); ?>
                    </div>
                    <div style="font-size: 0.9rem; margin-top: 5px;">
                        <?php echo e($order['delivery_address']); ?>
                    </div>
                </td>
                <td style="padding: 12px;">
                    <?php echo date('d.m.Y', strtotime($order['delivery_date'])); ?><br>
                    <small style="color: #666;"><?php echo e($order['delivery_interval']); ?></small>
                </td>
                <td style="padding: 12px; font-weight: bold;">
                    <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                </td>
                <td style="padding: 12px;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status" onchange="this.form.submit()" 
                                style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="pending" <?php echo ($order['status'] === 'pending') ? 'selected' : ''; ?>>Ожидает</option>
                            <option value="confirmed" <?php echo ($order['status'] === 'confirmed') ? 'selected' : ''; ?>>Подтвержден</option>
                            <option value="preparing" <?php echo ($order['status'] === 'preparing') ? 'selected' : ''; ?>>Готовится</option>
                            <option value="delivering" <?php echo ($order['status'] === 'delivering') ? 'selected' : ''; ?>>В пути</option>
                            <option value="delivered" <?php echo ($order['status'] === 'delivered') ? 'selected' : ''; ?>>Доставлен</option>
                            <option value="cancelled" <?php echo ($order['status'] === 'cancelled') ? 'selected' : ''; ?>>Отменен</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </td>
                <td style="padding: 12px;">
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                       style="background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">
                        Детали
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($orders)): ?>
    <div style="text-align: center; padding: 40px; color: #6c757d;">
        <h3>Заказы не найдены</h3>
        <p>Попробуйте изменить параметры фильтрации</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>