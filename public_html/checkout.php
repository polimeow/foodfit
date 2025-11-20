<?php
require_once 'config.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

// Проверяем что корзина не пуста
if (empty($_SESSION['cart'])) {
    header('Location: catalog.php');
    exit();
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Получаем программы из корзины
$placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE id IN ($placeholders) AND is_active = TRUE");
$stmt->execute($_SESSION['cart']);
$cart_items = $stmt->fetchAll();

$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'];
}

// Обработка применения промо-кода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promo_code = trim($_POST['promo_code'] ?? '');
    
    if (empty($promo_code)) {
        $_SESSION['promo_error'] = "Введите промо-код";
    } else {
        // Проверяем промо-код
        $stmt = $pdo->prepare("
            SELECT * FROM promo_codes 
            WHERE code = ? AND is_active = TRUE 
            AND (valid_from IS NULL OR valid_from <= CURDATE())
            AND (valid_until IS NULL OR valid_until >= CURDATE())
        ");
        $stmt->execute([$promo_code]);
        $promo = $stmt->fetch();
        
        if (!$promo) {
            $_SESSION['promo_error'] = "Промо-код не найден или недействителен";
        } elseif ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
            $_SESSION['promo_error'] = "Лимит использований промо-кода исчерпан";
        } elseif ($promo['min_order_amount'] > 0 && $total_amount < $promo['min_order_amount']) {
            $_SESSION['promo_error'] = "Минимальная сумма заказа для этого промо-кода: " . number_format($promo['min_order_amount'], 0, ',', ' ') . " ₽";
        } else {
            // Промо-код действителен
            $_SESSION['applied_promo'] = $promo;
            $_SESSION['promo_success'] = "Промо-код успешно применен!";
        }
    }
    header('Location: checkout.php');
    exit();
}

// Обработка удаления промо-кода
if (isset($_GET['remove_promo'])) {
    unset($_SESSION['applied_promo']);
    header('Location: checkout.php');
    exit();
}

// Расчет скидки
$discount_amount = 0;
$final_amount = $total_amount;

if (isset($_SESSION['applied_promo'])) {
    $promo = $_SESSION['applied_promo'];
    
    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = $total_amount * ($promo['discount_value'] / 100);
    } else {
        $discount_amount = $promo['discount_value'];
    }
    
    // Не позволяем скидке превышать сумму заказа
    if ($discount_amount > $total_amount) {
        $discount_amount = $total_amount;
    }
    
    $final_amount = $total_amount - $discount_amount;
}

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_date = $_POST['delivery_date'] ?? '';
    $delivery_interval = $_POST['delivery_interval'] ?? '';
    $customer_notes = trim($_POST['customer_notes'] ?? '');
    
    $errors = [];
    
    // Валидация
    if (empty($delivery_address)) {
        $errors[] = "Адрес доставки обязателен";
    }
    
    if (empty($delivery_date) || strtotime($delivery_date) < strtotime('today')) {
        $errors[] = "Выберите корректную дату доставки";
    }
    
    if (empty($delivery_interval)) {
        $errors[] = "Выберите интервал доставки";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Создаем заказ
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, delivery_date, delivery_interval, customer_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $final_amount, $delivery_address, $delivery_date, $delivery_interval, $customer_notes]);
            $order_id = $pdo->lastInsertId();
            
            // Добавляем элементы заказа (программы на разные дни)
            $days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
            foreach ($_SESSION['cart'] as $day => $plan_id) {
                $plan = $cart_items[array_search($plan_id, array_column($cart_items, 'id'))];
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, meal_plan_id, day_of_week, quantity, price) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$order_id, $plan_id, $day + 1, $plan['price']]);
            }
            
            // Если применен промо-код, записываем использование
            if (isset($_SESSION['applied_promo'])) {
                $promo = $_SESSION['applied_promo'];
                $stmt = $pdo->prepare("INSERT INTO used_promo_codes (promo_code_id, order_id, user_id, discount_amount, used_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$promo['id'], $order_id, $_SESSION['user_id'], $discount_amount]);
                
                // Обновляем счетчик использований
                $stmt = $pdo->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$promo['id']]);
            }
            
            $pdo->commit();
            
            // Очищаем корзину и промо-код
            unset($_SESSION['cart']);
            unset($_SESSION['applied_promo']);
            
            // Перенаправляем в личный кабинет
            $_SESSION['order_success'] = "Заказ №{$order_id} успешно оформлен!" . (isset($promo) ? " Скидка по промо-коду: " . number_format($discount_amount, 0, ',', ' ') . " ₽" : "");
            header('Location: personal/orders.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка при оформлении заказа: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Оформление заказа</h2>

<?php if (!empty($errors)): ?>
    <div style="color: red; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['promo_error'])): ?>
    <div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php echo e($_SESSION['promo_error']); ?>
        <?php unset($_SESSION['promo_error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['promo_success'])): ?>
    <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php echo e($_SESSION['promo_success']); ?>
        <?php unset($_SESSION['promo_success']); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px;">
    <!-- Форма заказа -->
    <div>
        <h3>Данные доставки</h3>
        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Адрес доставки:</label>
                <input type="text" name="delivery_address" 
                       value="<?php echo e($_POST['delivery_address'] ?? $user['delivery_address'] ?? ''); ?>"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="Улица, дом, квартира" required>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата доставки:</label>
                <input type="date" name="delivery_date" 
                       value="<?php echo e($_POST['delivery_date'] ?? date('Y-m-d', strtotime('+1 day'))); ?>"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Интервал доставки:</label>
                <select name="delivery_interval" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                    <option value="">Выберите время</option>
                    <option value="09:00-12:00" <?php echo (($_POST['delivery_interval'] ?? '') === '09:00-12:00') ? 'selected' : ''; ?>>09:00 - 12:00</option>
                    <option value="12:00-15:00" <?php echo (($_POST['delivery_interval'] ?? '') === '12:00-15:00') ? 'selected' : ''; ?>>12:00 - 15:00</option>
                    <option value="15:00-18:00" <?php echo (($_POST['delivery_interval'] ?? '') === '15:00-18:00') ? 'selected' : ''; ?>>15:00 - 18:00</option>
                    <option value="18:00-21:00" <?php echo (($_POST['delivery_interval'] ?? '') === '18:00-21:00') ? 'selected' : ''; ?>>18:00 - 21:00</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Примечания к заказу:</label>
                <textarea name="customer_notes" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; height: 100px;"
                          placeholder="Дополнительные пожелания..."><?php echo e($_POST['customer_notes'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" name="create_order" style="background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1rem; width: 100%;">
                Подтвердить заказ
            </button>
        </form>
    </div>
    
    <!-- Информация о заказе -->
    <div>
        <h3>Ваш заказ</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <?php foreach ($cart_items as $index => $item): ?>
                <div style="display: flex; justify-content: between; align-items: center; padding: 10px 0; border-bottom: 1px solid #dee2e6;">
                    <div>
                        <strong><?php echo e($item['title']); ?></strong>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?php echo $item['calories']; ?> ккал • 
                            <?php 
                            $days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                            echo $days[$index] ?? 'День ' . ($index + 1);
                            ?>
                        </div>
                    </div>
                    <div style="font-weight: bold;">
                        <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Промо-код -->
            <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                <?php if (isset($_SESSION['applied_promo'])): ?>
                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <strong>Промо-код применен:</strong>
                            <div style="color: #28a745; font-weight: bold;">
                                <?php echo e($_SESSION['applied_promo']['code']); ?>
                            </div>
                        </div>
                        <a href="checkout.php?remove_promo=1" style="color: #dc3545; text-decoration: none;">Удалить</a>
                    </div>
                <?php else: ?>
                    <form method="POST" style="display: flex; gap: 10px;">
                        <input type="text" name="promo_code" 
                               placeholder="Введите промо-код"
                               style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <button type="submit" name="apply_promo" style="
                            background: #17a2b8;
                            color: white;
                            padding: 10px 20px;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                        ">
                            Применить
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Итоги -->
            <div style="border-top: 2px solid #007bff; padding-top: 15px; margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Сумма заказа:</span>
                    <span><?php echo number_format($total_amount, 0, ',', ' '); ?> ₽</span>
                </div>
                
                <?php if (isset($_SESSION['applied_promo'])): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #28a745;">
                        <span>Скидка по промо-коду:</span>
                        <span>-<?php echo number_format($discount_amount, 0, ',', ' '); ?> ₽</span>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; padding-top: 10px; border-top: 1px solid #dee2e6;">
                    <span>Итого к оплате:</span>
                    <span style="color: #28a745;"><?php echo number_format($final_amount, 0, ',', ' '); ?> ₽</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
            <h4>Информация о доставке:</h4>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Доставка осуществляется на следующий день после заказа</li>
                <li>Время доставки: выбранный вами интервал</li>
                <li>Оплата производится при получении</li>
                <?php if (isset($_SESSION['applied_promo'])): ?>
                    <li style="color: #28a745; font-weight: bold;">✅ Промо-код применен! Экономия: <?php echo number_format($discount_amount, 0, ',', ' '); ?> ₽</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>