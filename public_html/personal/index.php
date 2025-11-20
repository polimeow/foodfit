<?php
require_once '../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php');
    exit();
}

// Получаем статистику пользователя
$stmt = $pdo->prepare("SELECT COUNT(*) as orders_count FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$orders_count = $stmt->fetch()['orders_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_orders = $stmt->fetch()['pending_orders'];
?>

<?php include '../includes/header.php'; ?>

<h2>Личный кабинет</h2>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
    <div style="background: #007bff; color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?php echo $orders_count; ?></div>
        <div>Всего заказов</div>
    </div>
    
    <div style="background: #28a745; color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?php echo $pending_orders; ?></div>
        <div>Текущие заказы</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 20px;">
        <h3>Быстрые действия</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin: 10px 0;">
                <a href="../catalog.php" style="color: #007bff; text-decoration: none; font-size: 1.1rem;">
                    🛍️ Сделать новый заказ
                </a>
            </li>
            <li style="margin: 10px 0;">
                <a href="orders.php" style="color: #007bff; text-decoration: none; font-size: 1.1rem;">
                    📦 Мои заказы
                </a>
            </li>
            <li style="margin: 10px 0;">
                <a href="profile.php" style="color: #007bff; text-decoration: none; font-size: 1.1rem;">
                    👤 Редактировать профиль
                </a>
            </li>
        </ul>
    </div>
    
    <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 20px;">
        <h3>Последние заказы</h3>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_orders = $stmt->fetchAll();
        
        if (empty($recent_orders)): 
        ?>
            <p style="color: #666;">У вас пока нет заказов</p>
        <?php else: ?>
            <?php foreach ($recent_orders as $order): ?>
                <div style="border-bottom: 1px solid #e9ecef; padding: 10px 0;">
                    <div style="font-weight: bold;">Заказ #<?php echo $order['id']; ?></div>
                    <div style="color: #666; font-size: 0.9rem;">
                        <?php echo date('d.m.Y', strtotime($order['created_at'])); ?> • 
                        <?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽
                    </div>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 10px;">
                <a href="orders.php" style="color: #007bff; text-decoration: none;">Все заказы →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>