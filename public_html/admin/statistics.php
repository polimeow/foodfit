<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Период для статистики
$period = $_GET['period'] ?? 'week';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Определяем даты в зависимости от периода
switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to = date('Y-m-d');
        break;
    case 'month':
        $date_from = date('Y-m-d', strtotime('-30 days'));
        $date_to = date('Y-m-d');
        break;
    case 'custom':
        // Используем выбранные даты
        break;
}

// Основная статистика за период
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(DISTINCT user_id) as unique_customers,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
")->execute([$date_from, $date_to])->fetch();

// Статистика по дням
$daily_stats = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        COALESCE(SUM(total_amount), 0) as daily_revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
")->execute([$date_from, $date_to])->fetchAll();

// Популярные программы за период
$popular_plans = $pdo->prepare("
    SELECT 
        mp.title,
        COUNT(oi.id) as order_count,
        COALESCE(SUM(oi.price), 0) as total_revenue
    FROM order_items oi
    JOIN meal_plans mp ON oi.meal_plan_id = mp.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY mp.id, mp.title
    ORDER BY order_count DESC
    LIMIT 10
")->execute([$date_from, $date_to])->fetchAll();

// Статистика по промо-кодам
$promo_stats = $pdo->prepare("
    SELECT 
        pc.code,
        COUNT(upc.id) as usage_count,
        COALESCE(SUM(upc.discount_amount), 0) as total_discount
    FROM used_promo_codes upc
    JOIN promo_codes pc ON upc.promo_code_id = pc.id
    JOIN orders o ON upc.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY pc.id, pc.code
    ORDER BY usage_count DESC
")->execute([$date_from, $date_to])->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h2>Расширенная статистика</h2>

<!-- Фильтры -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Период:</label>
            <select name="period" onchange="this.form.submit()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="today" <?php echo ($period === 'today') ? 'selected' : ''; ?>>Сегодня</option>
                <option value="week" <?php echo ($period === 'week') ? 'selected' : ''; ?>>Последние 7 дней</option>
                <option value="month" <?php echo ($period === 'month') ? 'selected' : ''; ?>>Последние 30 дней</option>
                <option value="custom" <?php echo ($period === 'custom') ? 'selected' : ''; ?>>Произвольный период</option>
            </select>
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">С:</label>
            <input type="date" name="date_from" value="<?php echo e($date_from); ?>" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                   <?php echo ($period !== 'custom') ? 'readonly' : ''; ?>>
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">По:</label>
            <input type="date" name="date_to" value="<?php echo e($date_to); ?>" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                   <?php echo ($period !== 'custom') ? 'readonly' : ''; ?>>
        </div>
        
        <div>
            <button type="submit" style="
                background: #007bff;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                width: 100%;
            ">
                🔍 Обновить
            </button>
        </div>
    </form>
</div>

<!-- Основная статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $stats['total_orders']; ?></div>
        <div style="color: #666;">Заказов</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $stats['unique_customers']; ?></div>
        <div style="color: #666;">Уникальных клиентов</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> ₽</div>
        <div style="color: #666;">Общая выручка</div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #6f42c1;"><?php echo number_format($stats['avg_order_value'], 0, ',', ' '); ?> ₽</div>
        <div style="color: #666;">Средний чек</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Статистика по дням -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статистика по дням</h3>
        
        <?php if (empty($daily_stats)): ?>
            <p style="color: #666; text-align: center;">Нет данных за выбранный период</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 400px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Дата</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Заказы</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_stats as $day): ?>
                        <tr style="border-bottom: 1px solid #e9ecef;">
                            <td style="padding: 12px;"><?php echo date('d.m.Y', strtotime($day['date'])); ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: bold;"><?php echo $day['orders_count']; ?></td>
                            <td style="padding: 12px; text-align: right; color: #28a745; font-weight: bold;">
                                <?php echo number_format($day['daily_revenue'], 0, ',', ' '); ?> ₽
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Популярные программы -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Популярные программы</h3>
        
        <?php if (empty($popular_plans)): ?>
            <p style="color: #666; text-align: center;">Нет данных за выбранный период</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 400px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Программа</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Заказы</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_plans as $plan): ?>
                        <tr style="border-bottom: 1px solid #e9ecef;">
                            <td style="padding: 12px;"><?php echo e($plan['title']); ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: bold;"><?php echo $plan['order_count']; ?></td>
                            <td style="padding: 12px; text-align: right; color: #28a745; font-weight: bold;">
                                <?php echo number_format($plan['total_revenue'], 0, ',', ' '); ?> ₽
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Статистика по промо-кодам -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Эффективность промо-кодов</h3>
    
    <?php if (empty($promo_stats)): ?>
        <p style="color: #666; text-align: center;">Промо-коды не использовались в выбранный период</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Промо-код</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Использований</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Общая скидка</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 1px solid #dee2e6;">Средняя скидка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promo_stats as $promo): ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px; font-weight: bold; color: #007bff;"><?php echo e($promo['code']); ?></td>
                        <td style="padding: 12px; text-align: right; font-weight: bold;"><?php echo $promo['usage_count']; ?></td>
                        <td style="padding: 12px; text-align: right; color: #28a745; font-weight: bold;">
                            -<?php echo number_format($promo['total_discount'], 0, ',', ' '); ?> ₽
                        </td>
                        <td style="padding: 12px; text-align: right; color: #6f42c1; font-weight: bold;">
                            <?php echo $promo['usage_count'] > 0 ? number_format($promo['total_discount'] / $promo['usage_count'], 0, ',', ' ') : '0'; ?> ₽
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Экспорт данных -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 30px;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Экспорт данных</h3>
    
    <div style="display: flex; gap: 15px;">
        <a href="export.php?type=orders&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
           style="background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 500;">
            📊 Экспорт заказов (CSV)
        </a>
        
        <a href="export.php?type=users&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
           style="background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 500;">
            👥 Экспорт пользователей (CSV)
        </a>
        
        <a href="export.php?type=revenue&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
           style="background: #ffc107; color: #212529; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 500;">
            💰 Экспорт выручки (CSV)
        </a>
    </div>
</div>

<script>
// Автоматическое обновление дат при выборе периода
document.querySelector('select[name="period"]').addEventListener('change', function() {
    if (this.value !== 'custom') {
        document.querySelector('input[name="date_from"]').setAttribute('readonly', 'readonly');
        document.querySelector('input[name="date_to"]').setAttribute('readonly', 'readonly');
    } else {
        document.querySelector('input[name="date_from"]').removeAttribute('readonly');
        document.querySelector('input[name="date_to"]').removeAttribute('readonly');
    }
});
</script>

<?php include 'includes/footer.php'; ?>