<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();
?>
<?php include 'includes/header.php'; ?>

<h2>Управление программами питания</h2>

<a href="meal_plans_edit.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 20px;">
    ➕ Добавить программу
</a>

<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">ID</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Название</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Калории</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Цель</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Цена</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Статус</th>
            <th style="border: 1px solid #ddd; padding: 12px; text-align: left;">Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->query("
            SELECT mp.*, ng.name as goal_name 
            FROM meal_plans mp 
            LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id 
            ORDER BY mp.id DESC
        ");
        while ($plan = $stmt->fetch()):
        ?>
        <tr>
            <td style="border: 1px solid #ddd; padding: 12px;"><?php echo $plan['id']; ?></td>
            <td style="border: 1px solid #ddd; padding: 12px;"><?php echo e($plan['title']); ?></td>
            <td style="border: 1px solid #ddd; padding: 12px;"><?php echo $plan['calories']; ?> ккал</td>
            <td style="border: 1px solid #ddd; padding: 12px;"><?php echo e($plan['goal_name'] ?? 'Не указана'); ?></td>
            <td style="border: 1px solid #ddd; padding: 12px;"><?php echo number_format($plan['price'], 2, ',', ' '); ?> ₽</td>
            <td style="border: 1px solid #ddd; padding: 12px; color: <?php echo $plan['is_active'] ? '#28a745' : '#dc3545'; ?>">
                <?php echo $plan['is_active'] ? 'Активна' : 'Неактивна'; ?>
            </td>
            <td style="border: 1px solid #ddd; padding: 12px;">
                <a href="meal_plans_edit.php?id=<?php echo $plan['id']; ?>" 
                   style="background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin: 2px; display: inline-block;">
                    Редактировать
                </a>
                <a href="meal_plans_delete.php?id=<?php echo $plan['id']; ?>" 
                   style="background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; margin: 2px; display: inline-block;"
                   onclick="return confirm('Удалить программу?')">
                    Удалить
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>