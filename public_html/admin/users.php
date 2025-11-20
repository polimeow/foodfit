<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Проверяем права доступа
if (!canManageUsers()) {
    header('Location: index.php');
    exit();
}

// Поиск и фильтрация
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Формируем запрос
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Сортировка
switch ($sort) {
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Статистика
$total_users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$today_users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
$week_users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count'];
?>

<?php include 'includes/header.php'; ?>

<h2>Управление пользователями</h2>

<!-- Статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $total_users; ?></div>
        <div style="color: #666;">Всего пользователей</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $today_users; ?></div>
        <div style="color: #666;">Сегодня</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #6f42c1;"><?php echo $week_users; ?></div>
        <div style="color: #666;">За 7 дней</div>
    </div>
</div>

<!-- Фильтры и поиск -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Поиск пользователей:</label>
            <input type="text" name="search" value="<?php echo e($search); ?>" 
                   placeholder="Имя или email..."
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Сортировка:</label>
            <select name="sort" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Новые сначала</option>
                <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Старые сначала</option>
                <option value="name" <?php echo ($sort === 'name') ? 'selected' : ''; ?>>По имени (А-Я)</option>
            </select>
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
            <a href="users.php" style="
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

<!-- Таблица пользователей -->
<div style="background: white; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: #333;">Список пользователей</h3>
    </div>
    
    <?php if (empty($users)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Пользователи не найдены</h4>
            <p>Попробуйте изменить параметры поиска</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Пользователь</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Контактные данные</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Статистика</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Дата регистрации</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        // Получаем статистику пользователя
                        $orders_count = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?")->execute([$user['id']])->fetch()['count'];
                        $total_spent = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'delivered'")->execute([$user['id']])->fetch()['total'];
                    ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 15px;">
                            <div style="font-weight: bold; margin-bottom: 5px;">
                                <?php echo e($user['name']); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                ID: <?php echo $user['id']; ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <a href="mailto:<?php echo e($user['email']); ?>" style="color: #007bff; text-decoration: none;">
                                    <?php echo e($user['email']); ?>
                                </a>
                            </div>
                            <?php if ($user['phone']): ?>
                            <div style="color: #666;">
                                📞 <?php echo e($user['phone']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($user['delivery_address']): ?>
                            <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                                🏠 <?php echo e($user['delivery_address']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <strong>Заказов:</strong> <?php echo $orders_count; ?>
                            </div>
                            <div>
                                <strong>Потратил:</strong> 
                                <span style="color: #28a745; font-weight: bold;">
                                    <?php echo number_format($total_spent, 0, ',', ' '); ?> ₽
                                </span>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('H:i', strtotime($user['created_at'])); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="user_details.php?id=<?php echo $user['id']; ?>" 
                                   style="
                                       background: #007bff;
                                       color: white;
                                       padding: 6px 12px;
                                       text-decoration: none;
                                       border-radius: 4px;
                                       font-size: 0.9rem;
                                       display: inline-block;
                                   ">
                                    👁️ Детали
                                </a>
                                
                                <a href="user_orders.php?id=<?php echo $user['id']; ?>" 
                                   style="
                                       background: #17a2b8;
                                       color: white;
                                       padding: 6px 12px;
                                       text-decoration: none;
                                       border-radius: 4px;
                                       font-size: 0.9rem;
                                       display: inline-block;
                                   ">
                                    📦 Заказы
                                </a>
                                
                                <button onclick="confirmDelete(<?php echo $user['id']; ?>)" 
                                        style="
                                            background: #dc3545;
                                            color: white;
                                            padding: 6px 12px;
                                            border: none;
                                            border-radius: 4px;
                                            font-size: 0.9rem;
                                            cursor: pointer;
                                        ">
                                    🗑️ Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(userId) {
    if (confirm('Вы уверены, что хотите удалить этого пользователя? Все его заказы также будут удалены.')) {
        window.location.href = 'user_delete.php?id=' + userId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>