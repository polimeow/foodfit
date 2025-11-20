<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

$promo_id = $_GET['id'] ?? 0;

if (!$promo_id) {
    header('Location: promo_codes.php');
    exit();
}

// Получаем информацию о промо-коде
$stmt = $pdo->prepare("
    SELECT pc.*, 
           (SELECT COUNT(*) FROM used_promo_codes upc WHERE upc.promo_code_id = pc.id) as used_count
    FROM promo_codes pc 
    WHERE pc.id = ?
");
$stmt->execute([$promo_id]);
$promo = $stmt->fetch();

if (!$promo) {
    header('Location: promo_codes.php');
    exit();
}

// Получаем историю использований
$usage_history = $pdo->prepare("
    SELECT upc.*, u.name as user_name, u.email, o.total_amount, o.created_at as order_date
    FROM used_promo_codes upc
    JOIN users u ON upc.user_id = u.id
    JOIN orders o ON upc.order_id = o.id
    WHERE upc.promo_code_id = ?
    ORDER BY upc.used_at DESC
")->execute([$promo_id])->fetchAll();

// Статистика
$total_discount = $pdo->prepare("
    SELECT COALESCE(SUM(discount_amount), 0) as total 
    FROM used_promo_codes 
    WHERE promo_code_id = ?
")->execute([$promo_id])->fetch()['total'];
?>

<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: between; align-items: start; margin-bottom: 30px;">
    <div>
        <h2>Детали промо-кода</h2>
        <p style="color: #666; margin: 0;"><?php echo e($promo['code']); ?></p>
    </div>
    <a href="promo_codes.php" style="
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
    ">← Назад к промо-кодам</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Основная информация -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Основная информация</h3>
        
        <div style="display: grid; gap: 15px;">
            <div>
                <strong>Промо-код:</strong><br>
                <span style="font-size: 1.2rem; font-weight: bold; color: #007bff;">
                    <?php echo e($promo['code']); ?>
                </span>
            </div>
            
            <div>
                <strong>Тип скидки:</strong><br>
                <?php echo $promo['discount_type'] === 'percentage' ? 'Процентная' : 'Фиксированная'; ?>
            </div>
            
            <div>
                <strong>Размер скидки:</strong><br>
                <span style="font-size: 1.2rem; font-weight: bold; color: #28a745;">
                    <?php if ($promo['discount_type'] === 'percentage'): ?>
                        <?php echo number_format($promo['discount_value'], 0); ?>%
                    <?php else: ?>
                        <?php echo number_format($promo['discount_value'], 0, ',', ' '); ?> ₽
                    <?php endif; ?>
                </span>
            </div>
            
            <div>
                <strong>Минимальная сумма заказа:</strong><br>
                <?php echo $promo['min_order_amount'] > 0 ? number_format($promo['min_order_amount'], 0, ',', ' ') . ' ₽' : 'Нет'; ?>
            </div>
            
            <div>
                <strong>Лимит использований:</strong><br>
                <?php echo $promo['usage_limit'] ? $promo['usage_limit'] . ' раз' : 'Безлимитно'; ?>
            </div>
        </div>
    </div>
    
    <!-- Даты и статус -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Срок действия и статус</h3>
        
        <div style="display: grid; gap: 15px;">
            <div>
                <strong>Действует с:</strong><br>
                <?php echo $promo['valid_from'] ? date('d.m.Y', strtotime($promo['valid_from'])) : 'Сразу после создания'; ?>
            </div>
            
            <div>
                <strong>Действует до:</strong><br>
                <?php echo $promo['valid_until'] ? date('d.m.Y', strtotime($promo['valid_until'])) : 'Бессрочно'; ?>
            </div>
            
            <div>
                <strong>Статус:</strong><br>
                <?php if ($promo['is_active']): ?>
                    <span style="color: #28a745; font-weight: bold;">✅ Активен</span>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: bold;">❌ Неактивен</span>
                <?php endif; ?>
            </div>
            
            <div>
                <strong>Дата создания:</strong><br>
                <?php echo date('d.m.Y H:i', strtotime($promo['created_at'])); ?>
            </div>
        </div>
    </div>
</div>

<!-- Статистика -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 30px;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Статистика использования</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="text-align: center; padding: 20px; background: #e7f3ff; border-radius: 6px;">
            <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $promo['used_count']; ?></div>
            <div style="color: #666;">Всего использований</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #d4edda; border-radius: 6px;">
            <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo number_format($total_discount, 0, ',', ' '); ?> ₽</div>
            <div style="color: #666;">Общая скидка</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #fff3cd; border-radius: 6px;">
            <div style="font-size: 2rem; font-weight: bold; color: #ffc107;">
                <?php echo $promo['used_count'] > 0 ? number_format($total_discount / $promo['used_count'], 0, ',', ' ') : '0'; ?> ₽
            </div>
            <div style="color: #666;">Средняя скидка</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #f8d7da; border-radius: 6px;">
            <div style="font-size: 2rem; font-weight: bold; color: #dc3545;">
                <?php echo $promo['usage_limit'] ? $promo['usage_limit'] - $promo['used_count'] : '∞'; ?>
            </div>
            <div style="color: #666;">Осталось использований</div>
        </div>
    </div>
</div>

<!-- История использований -->
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
    <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">История использований</h3>
    
    <?php if (empty($usage_history)): ?>
        <p style="color: #666; text-align: center; padding: 20px;">Промо-код еще не использовался</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 700px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Клиент</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Заказ</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Скидка</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6;">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_history as $usage): ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px;">
                            <div style="font-weight: bold;"><?php echo e($usage['user_name']); ?></div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo e($usage['email']); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div>
                                <a href="order_details.php?id=<?php echo $usage['order_id']; ?>" style="color: #007bff; text-decoration: none;">
                                    Заказ #<?php echo $usage['order_id']; ?>
                                </a>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo number_format($usage['total_amount'], 0, ',', ' '); ?> ₽
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="font-weight: bold; color: #28a745;">
                                -<?php echo number_format($usage['discount_amount'], 0, ',', ' '); ?> ₽
                            </div>
                        </td>
                        
                        <td style="padding: 12px;">
                            <div style="margin-bottom: 5px;">
                                <?php echo date('d.m.Y', strtotime($usage['used_at'])); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('H:i', strtotime($usage['used_at'])); ?>
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