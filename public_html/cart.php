<?php
require_once 'config.php';

// Обработка добавления в корзину
if (isset($_GET['add'])) {
    $plan_id = (int)$_GET['add'];
    
    // Проверяем существование программы
    $stmt = $pdo->prepare("SELECT id FROM meal_plans WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$plan_id]);
    
    if ($stmt->fetch()) {
        // Инициализируем корзину в сессии
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Проверяем лимит (максимум 7 программ - на неделю)
        if (count($_SESSION['cart']) >= 7) {
            $_SESSION['cart_error'] = "Можно добавить не более 7 программ (на неделю)";
        } else {
            // Добавляем программу в корзину
            $_SESSION['cart'][] = $plan_id;
            $_SESSION['cart_success'] = "Программа добавлена в корзину!";
        }
    } else {
        $_SESSION['cart_error'] = "Программа не найдена";
    }
    
    header('Location: cart.php');
    exit();
}

// Обработка удаления из корзины
if (isset($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Переиндексируем массив
        $_SESSION['cart_success'] = "Программа удалена из корзины";
    }
    header('Location: cart.php');
    exit();
}

// Обработка очистки корзины
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    $_SESSION['cart_success'] = "Корзина очищена";
    header('Location: cart.php');
    exit();
}

// Получаем данные о программах в корзине
$cart_items = [];
$total_amount = 0;
$days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

if (!empty($_SESSION['cart'])) {
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE id IN ($placeholders) AND is_active = TRUE");
    $stmt->execute($_SESSION['cart']);
    $cart_items = $stmt->fetchAll();
    
    foreach ($cart_items as $item) {
        $total_amount += $item['price'];
    }
}
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
    <h2>🛒 Корзина</h2>
    
    <?php if (isset($_SESSION['cart_error'])): ?>
        <div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo e($_SESSION['cart_error']); ?>
            <?php unset($_SESSION['cart_error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['cart_success'])): ?>
        <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo e($_SESSION['cart_success']); ?>
            <?php unset($_SESSION['cart_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 60px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
            <h3>Корзина пуста</h3>
            <p>Добавьте программы питания из каталога</p>
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
                <a href="catalog.php" style="
                    background: #007bff;
                    color: white;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                ">
                    Перейти в каталог
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="favorites.php" style="
                        background: #6f42c1;
                        color: white;
                        padding: 12px 24px;
                        text-decoration: none;
                        border-radius: 6px;
                        font-weight: 600;
                    ">
                        ❤️ Избранные программы
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 20px;">
            <!-- Программы в корзине -->
            <?php foreach ($cart_items as $index => $item): ?>
            <div style="
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 20px;
                background: white;
                display: flex;
                justify-content: between;
                align-items: center;
                transition: all 0.3s ease;
            " onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" 
               onmouseout="this.style.boxShadow='none'">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 10px 0; color: #333;"><?php echo e($item['title']); ?></h4>
                    <div style="color: #666; font-size: 0.9rem;">
                        <span style="margin-right: 15px;"><?php echo $item['calories']; ?> ккал/день</span>
                        <span style="
                            background: #007bff;
                            color: white;
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-size: 0.8rem;
                        ">
                            <?php echo $days[$index] ?? 'День ' . ($index + 1); ?>
                        </span>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="font-size: 1.25rem; font-weight: bold; color: #28a745;">
                        <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                    </div>
                    
                    <a href="cart.php?remove=<?php echo $index; ?>" 
                       style="
                            color: #dc3545;
                            text-decoration: none;
                            font-size: 1.5rem;
                            width: 40px;
                            height: 40px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 50%;
                            transition: background 0.3s ease;
                       " 
                       onmouseover="this.style.background='#f8d7da'" 
                       onmouseout="this.style.background='transparent'"
                       title="Удалить из корзины">
                        ×
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Итоги и действия -->
            <div style="
                border-top: 2px solid #007bff;
                padding-top: 25px;
                margin-top: 10px;
            ">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 30px;">
                    <!-- Информация -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #333;">Ваш рацион на неделю</h4>
                        <div style="color: #666; line-height: 1.6;">
                            <p>✅ Разнообразное питание на 7 дней</p>
                            <p>✅ Сбалансированный рацион по БЖУ</p>
                            <p>✅ Удобная доставка на дом</p>
                            <p>✅ Экономия времени на готовке</p>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <a href="cart.php?clear=1" 
                               style="color: #dc3545; text-decoration: none; font-weight: 500;"
                               onclick="return confirm('Очистить всю корзину?')">
                                🗑️ Очистить корзину
                            </a>
                        </div>
                    </div>
                    
                    <!-- Сумма и кнопка -->
                    <div style="text-align: right;">
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 1.1rem; color: #666; margin-bottom: 5px;">
                                Итого к оплате:
                            </div>
                            <div style="font-size: 2rem; font-weight: bold; color: #28a745;">
                                <?php echo number_format($total_amount, 0, ',', ' '); ?> ₽
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                за 7 дней питания
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" 
                               style="
                                    background: #28a745;
                                    color: white;
                                    padding: 15px 30px;
                                    text-decoration: none;
                                    border-radius: 6px;
                                    font-size: 1.1rem;
                                    font-weight: 600;
                                    display: inline-block;
                                    transition: background 0.3s ease;
                               " 
                               onmouseover="this.style.background='#218838'" 
                               onmouseout="this.style.background='#28a745'">
                                ➡️ Перейти к оформлению
                            </a>
                        <?php else: ?>
                            <a href="auth.php" 
                               style="
                                    background: #007bff;
                                    color: white;
                                    padding: 15px 30px;
                                    text-decoration: none;
                                    border-radius: 6px;
                                    font-size: 1.1rem;
                                    font-weight: 600;
                                    display: inline-block;
                               ">
                                🔐 Войдите для оформления
                            </a>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px;">
                            <a href="catalog.php" style="color: #007bff; text-decoration: none;">
                                ← Добавить еще программы
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>