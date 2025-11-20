<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php');
    exit();
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $errors = [];
    $success_message = '';
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    // Обновление основных данных
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, delivery_address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $phone, $delivery_address, $_SESSION['user_id']]);
        
        // Обновляем данные в сессии
        $_SESSION['user_name'] = $name;
        
        $success_message = "Профиль успешно обновлен!";
        
        // Обновляем данные пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    
    // Смена пароля
    if (!empty($current_password) || !empty($new_password)) {
        if (empty($current_password) || empty($new_password)) {
            $errors[] = "Для смены пароля необходимо заполнить оба поля";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Новый пароль должен содержать минимум 6 символов";
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = "Текущий пароль указан неверно";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
            
            $success_message = $success_message ? $success_message . " Пароль успешно изменен!" : "Пароль успешно изменен!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
    <h2>Редактирование профиля</h2>
    
    <?php if (isset($success_message)): ?>
        <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?php echo e($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Основная информация -->
        <div>
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Основная информация</h3>
            
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Имя:</label>
                    <input type="text" name="name" value="<?php echo e($user['name']); ?>"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           required>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
                    <input type="email" value="<?php echo e($user['email']); ?>"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;"
                           readonly disabled>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                        Email нельзя изменить
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Телефон:</label>
                    <input type="tel" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           placeholder="+7 (999) 123-45-67">
                </div>
                
                <div style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Адрес доставки:</label>
                    <textarea name="delivery_address" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; height: 80px; resize: vertical;"
                              placeholder="Улица, дом, квартира"><?php echo e($user['delivery_address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" style="
                    background: #007bff;
                    color: white;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    width: 100%;
                ">
                    💾 Сохранить изменения
                </button>
            </form>
        </div>
        
        <!-- Смена пароля и информация -->
        <div>
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Смена пароля</h3>
            
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Текущий пароль:</label>
                    <input type="password" name="current_password"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           placeholder="Введите текущий пароль">
                </div>
                
                <div style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Новый пароль:</label>
                    <input type="password" name="new_password"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           placeholder="Минимум 6 символов">
                    <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                        Оставьте пустым, если не хотите менять пароль
                    </div>
                </div>
                
                <button type="submit" style="
                    background: #28a745;
                    color: white;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    width: 100%;
                ">
                    🔐 Сменить пароль
                </button>
            </form>
            
            <!-- Информация о аккаунте -->
            <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: #333;">Информация об аккаунте</h4>
                
                <div style="display: grid; gap: 10px;">
                    <div>
                        <strong>Дата регистрации:</strong><br>
                        <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                    </div>
                    
                    <div>
                        <strong>Последнее обновление:</strong><br>
                        <?php echo $user['updated_at'] ? date('d.m.Y H:i', strtotime($user['updated_at'])) : '—'; ?>
                    </div>
                    
                    <?php
                    $orders_count = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?")->execute([$_SESSION['user_id']])->fetch()['count'];
                    ?>
                    <div>
                        <strong>Всего заказов:</strong><br>
                        <?php echo $orders_count; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Быстрые ссылки -->
    <div style="margin-top: 40px; display: flex; gap: 15px; justify-content: center;">
        <a href="index.php" style="
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">← Назад в кабинет</a>
        
        <a href="orders.php" style="
            background: #17a2b8;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        ">📦 Мои заказы</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>