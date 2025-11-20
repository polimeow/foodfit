<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Фильтрация
$promo_id = $_GET['promo_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Формируем запрос
$sql = "
    SELECT upc.*, pc.code as promo_code, u.name as user_name, u.email, o.total_amount, o.created_at as order_date
    FROM used_promo_codes upc
    JOIN promo_codes pc ON upc.promo_code_id = pc.id
    JOIN users u ON upc.user_id = u.id
    JOIN orders o ON upc.order_id = o.id
    WHERE 1=1
";
$params = [];

if ($promo_id) {
    $sql .= " AND upc.promo_code_id = ?";
    $params[] = $promo_id;
}

if ($date_from) {
    $sql .= " AND DATE(upc.used_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(upc.used_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY upc.used_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usage_history = $stmt->fetchAll();

// Получаем список промо-кодов для фильтра
$promo_codes = $pdo->query("SELECT id, code FROM promo_codes ORDER BY code")->fetchAll();

// Статистика
$total_usage = $pdo->query("SELECT COUNT(*) as count FROM used_promo_codes")->fetch()['count'];
$total_discount = $pdo->query("SELECT COALESCE(SUM(discount_amount), 0) as total FROM used_promo_codes")->fetch()['total'];
?>

<?php include 'includes/header.php'; ?>

<h2>История использований промо-кодов</h2>

<!-- Статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $total_usage; ?></div>
        <div style="color: #666;">Всего использований</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo number_format($total_discount, 0, ',', ' '); ?> ₽</div>
        <div style="color: #666;">Общая скидка</div>
    </div>
</div>

<!-- Фильтры -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Промо-код:</label>
            <select name="promo_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Все промо-коды</option>
                <?php foreach ($promo_codes as $promo): ?>
                    <option value="<?php echo $promo['id']; ?>" <?php echo ($promo_id == $promo['id']) ? 'selected' : ''; ?>>
                        <?php echo e($promo['code']); ?>
                    </option>
                <?php endforeach; ?>
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
            <a href="promo_usage.php" style="
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

<!-- Таблица использований -->
<div style="background: white; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: #333;">История использований</h3>
    </div>
    
    <?php if (empty($usage_history)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Использования не найдены</h4>
            <p>Попробуйте изменить параметры фильтрации</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Дата</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Промо-код</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Клиент</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Заказ</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Сумма заказа</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Скидка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_history as $usage): ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px;">
                            <div style="font-weight: bold;">
                                <?php echo date('d.m.Y', strtotime($usage['used_at'])); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('H:i', strtotime($usage['used_at'])); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="font-weight: bold; color: #007bff;">
                                <?php echo e($usage['promo_code']); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="font-weight: bold;"><?php echo e($usage['user_name']); ?></div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo e($usage['email']); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <a href="order_details.php?id=<?php echo $usage['order_id']; ?>" 
                               style="color: #007bff; text-decoration: none; font-weight: bold;">
                                Заказ #<?php echo $usage['order_id']; ?>
                            </a>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('d.m.Y', strtotime($usage['order_date'])); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="font-weight: bold;">
                                <?php echo number_format($usage['total_amount'], 0, ',', ' '); ?> ₽
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="font-weight: bold; color: #28a745;">
                                -<?php echo number_format($usage['discount_amount'], 0, ',', ' '); ?> ₽
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>