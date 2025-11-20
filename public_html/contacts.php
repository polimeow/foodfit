<?php
require_once 'config.php';

// Обработка формы обратной связи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    $success_message = '';
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email";
    }
    
    if (empty($message)) {
        $errors[] = "Сообщение обязательно для заполнения";
    }
    
    if (empty($errors)) {
        // Здесь можно добавить отправку email или сохранение в БД
        $success_message = "Спасибо за ваше сообщение! Мы свяжемся с вами в ближайшее время.";
        
        // Очищаем форму
        $name = $email = $phone = $message = '';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
    <!-- Герой-секция -->
    <section style="text-align: center; padding: 40px 0;">
        <h1 style="font-size: 2.5rem; margin-bottom: 15px; color: #333;">Свяжитесь с нами</h1>
        <p style="font-size: 1.2rem; color: #666; max-width: 600px; margin: 0 auto;">
            Мы всегда рады помочь вам и ответить на все вопросы
        </p>
    </section>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 60px;">
        <!-- Форма обратной связи -->
        <div>
            <h2 style="color: #333; margin-bottom: 25px;">Напишите нам</h2>
            
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
            
            <form method="POST" style="display: grid; gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Имя *</label>
                    <input type="text" name="name" value="<?php echo e($name ?? ''); ?>" 
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;"
                           required>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Email *</label>
                    <input type="email" name="email" value="<?php echo e($email ?? ''); ?>" 
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;"
                           required>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Телефон</label>
                    <input type="tel" name="phone" value="<?php echo e($phone ?? ''); ?>" 
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;"
                           placeholder="+7 (999) 123-45-67">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Сообщение *</label>
                    <textarea name="message" 
                              style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; height: 120px; resize: vertical;"
                              placeholder="Расскажите, чем мы можем вам помочь..."
                              required><?php echo e($message ?? ''); ?></textarea>
                </div>
                
                <button type="submit" style="
                    background: #28a745;
                    color: white;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 6px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background 0.3s ease;
                " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                    📨 Отправить сообщение
                </button>
            </form>
        </div>
        
        <!-- Контактная информация -->
        <div>
            <h2 style="color: #333; margin-bottom: 25px;">Контактная информация</h2>
            
            <div style="display: grid; gap: 25px;">
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="
                        background: #007bff;
                        color: white;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        flex-shrink: 0;
                    ">📞</div>
                    <div>
                        <h3 style="margin: 0 0 8px 0; color: #333;">Телефон</h3>
                        <p style="margin: 0; color: #666; font-size: 1.1rem;">
                            <a href="tel:+79991234567" style="color: #007bff; text-decoration: none;">
                                +7 (999) 123-45-67
                            </a>
                        </p>
                        <p style="margin: 5px 0 0 0; color: #999; font-size: 0.9rem;">
                            Ежедневно с 9:00 до 21:00
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="
                        background: #28a745;
                        color: white;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        flex-shrink: 0;
                    ">✉️</div>
                    <div>
                        <h3 style="margin: 0 0 8px 0; color: #333;">Email</h3>
                        <p style="margin: 0; color: #666; font-size: 1.1rem;">
                            <a href="mailto:info@fitpaek.ru" style="color: #007bff; text-decoration: none;">
                                info@fitpaek.ru
                            </a>
                        </p>
                        <p style="margin: 5px 0 0 0; color: #999; font-size: 0.9rem;">
                            Ответим в течение 24 часов
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="
                        background: #6f42c1;
                        color: white;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        flex-shrink: 0;
                    ">🏢</div>
                    <div>
                        <h3 style="margin: 0 0 8px 0; color: #333;">Адрес</h3>
                        <p style="margin: 0; color: #666; font-size: 1.1rem;">
                            г. Москва, ул. Примерная, д. 123
                        </p>
                        <p style="margin: 5px 0 0 0; color: #999; font-size: 0.9rem;">
                            Пн-Пт: 10:00-19:00
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="
                        background: #ffc107;
                        color: white;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        flex-shrink: 0;
                    ">💬</div>
                    <div>
                        <h3 style="margin: 0 0 8px 0; color: #333;">Социальные сети</h3>
                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                            <a href="#" style="color: #666; text-decoration: none; font-size: 1.5rem;" title="Instagram">
                                📷
                            </a>
                            <a href="#" style="color: #666; text-decoration: none; font-size: 1.5rem;" title="VK">
                                📘
                            </a>
                            <a href="#" style="color: #666; text-decoration: none; font-size: 1.5rem;" title="Telegram">
                                ✈️
                            </a>
                            <a href="#" style="color: #666; text-decoration: none; font-size: 1.5rem;" title="WhatsApp">
                                💬
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div style="margin-top: 40px; padding: 25px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin: 0 0 15px 0; color: #333;">Частые вопросы</h3>
                <div style="display: grid; gap: 10px;">
                    <div>
                        <strong>❓ Как происходит доставка?</strong>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                            Доставляем утром с 9:00 до 12:00 в выбранный вами день.
                        </div>
                    </div>
                    <div>
                        <strong>❓ Можно ли изменить заказ?</strong>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                            Изменить заказ можно до 20:00 предыдущего дня.
                        </div>
                    </div>
                    <div>
                        <strong>❓ Есть ли пробный период?</strong>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                            Да, первый заказ со скидкой 20% для новых клиентов.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>