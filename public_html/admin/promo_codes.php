<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Обработка добавления промо-кода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $code = trim($_POST['code'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
    $usage_limit = $_POST['usage_limit'] ? (int)$_POST['usage_limit'] : NULL;
    $valid_from = $_POST['valid_from'] ?: NULL;
    $valid_until = $_POST['valid_until'] ?: NULL;
    
    $errors = [];
    
    // Валидация
    if (empty($code)) {
        $errors[] = "Код обязателен для заполнения";
    }
    
    if ($discount_value <= 0) {
        $errors[] = "Значение скидки должно быть больше 0";
    }
    
    if ($discount_type === 'percentage' && $discount_value > 100) {
        $errors[] = "Процентная скидка не может превышать 100%";
    }
    
    if ($valid_until && $valid_from && strtotime($valid_until) < strtotime($valid_from)) {
        $errors[] = "Дата окончания не может быть раньше даты начала";
    }
    
    // Проверяем, нет ли уже такого кода
    $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        $errors[] = "Промо-код с таким названием уже существует";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO promo_codes 
            (code, discount_type, discount_value, min_order_amount, usage_limit, valid_from, valid_until, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $usage_limit, $valid_from, $valid_until]);
        
        $_SESSION['admin_message'] = "Промо-код успешно создан!";
        header('Location: promo_codes.php');
        exit();
    }
}

// Обработка изменения статуса
if (isset($_POST['toggle_status'])) {
    $promo_id = (int)$_POST['promo_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $promo_id]);
    
    header('Location: promo_codes.php?updated=1');
    exit();
}

// Получаем список промо-кодов
$promo_codes = $pdo->query("
    SELECT pc.*, 
           (SELECT COUNT(*) FROM used_promo_codes upc WHERE upc.promo_code_id = pc.id) as used_count
    FROM promo_codes pc 
    ORDER BY pc.created_at DESC
")->fetchAll();

// Статистика
$total_promos = $pdo->query("SELECT COUNT(*) as count FROM promo_codes")->fetch()['count'];
$active_promos = $pdo->query("SELECT COUNT(*) as count FROM promo_codes WHERE is_active = TRUE")->fetch()['count'];
$total_usage = $pdo->query("SELECT COUNT(*) as count FROM used_promo_codes")->fetch()['count'];
?>

<?php include 'includes/header.php'; ?>

<h2>Управление промо-кодами</h2>

<?php if (isset($_SESSION['admin_message'])): ?>
    <div style="color: #155724; background: #d4edda; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php echo $_SESSION['admin_message']; ?>
        <?php unset($_SESSION['admin_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($errors)): ?>
    <div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Статистика -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #007bff;"><?php echo $total_promos; ?></div>
        <div style="color: #666;">Всего промо-кодов</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $active_promos; ?></div>
        <div style="color: #666;">Активных кодов</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #6f42c1;"><?php echo $total_usage; ?></div>
        <div style="color: #666;">Всего использований</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Форма добавления промо-кода -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Создать промо-код</h3>
        
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Промо-код:</label>
                <input type="text" name="code" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="Например: SUMMER2024" required>
                <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                    Только латинские буквы и цифры, без пробелов
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Тип скидки:</label>
                    <select name="discount_type" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="percentage">Процентная (%)</option>
                        <option value="fixed">Фиксированная (₽)</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Значение скидки:</label>
                    <input type="number" name="discount_value" step="0.01" min="0"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           placeholder="0.00" required>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Минимальная сумма заказа:</label>
                <input type="number" name="min_order_amount" step="0.01" min="0"
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="0.00" value="0">
                <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                    0 = без ограничений
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Лимит использований:</label>
                    <input type="number" name="usage_limit" min="0"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                           placeholder="Без лимита">
                    <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                        Оставьте пустым для безлимита
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Действует с:</label>
                    <input type="date" name="valid_from" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Действует до:</label>
                    <input type="date" name="valid_until" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <button type="submit" name="add_promo" style="
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
                ➕ Создать промо-код
            </button>
        </form>
    </div>
    
    <!-- Информация -->
    <div style="background: #e7f3ff; padding: 25px; border-radius: 8px; border: 1px solid #b8daff;">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #004085;">Информация о промо-кодах</h3>
        
        <div style="color: #004085;">
            <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <h4 style="margin-top: 0; color: #004085;">Типы скидок:</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Процентная:</strong> скидка в % от суммы заказа</li>
                    <li><strong>Фиксированная:</strong> фиксированная сумма скидки в рублях</li>
                </ul>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 4px;">
                <strong>💡 Примеры использования:</strong><br>
                • WELCOME10 - 10% скидка для новых клиентов<br>
                • FREE500 - 500₽ скидка на первый заказ<br>
                • SUMMER15 - 15% скидка в летний период
            </div>
        </div>
    </div>
</div>

<!-- Список промо-кодов -->
<div style="background: white; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: #333;">Список промо-кодов</h3>
    </div>
    
    <?php if (empty($promo_codes)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Промо-коды не найдены</h4>
            <p>Создайте первый промо-код используя форму выше</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Промо-код</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Скидка</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Условия</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Использования</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Статус</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promo_codes as $promo): 
                        $is_expired = $promo['valid_until'] && strtotime($promo['valid_until']) < time();
                        $is_future = $promo['valid_from'] && strtotime($promo['valid_from']) > time();
                        $usage_limit_reached = $promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit'];
                    ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 15px;">
                            <div style="font-weight: bold; margin-bottom: 5px; font-size: 1.1rem;">
                                <?php echo e($promo['code']); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                Создан: <?php echo date('d.m.Y', strtotime($promo['created_at'])); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="font-weight: bold; color: #28a745; margin-bottom: 5px;">
                                <?php if ($promo['discount_type'] === 'percentage'): ?>
                                    <?php echo number_format($promo['discount_value'], 0); ?>%
                                <?php else: ?>
                                    <?php echo number_format($promo['discount_value'], 0, ',', ' '); ?> ₽
                                <?php endif; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo $promo['discount_type'] === 'percentage' ? 'Процентная' : 'Фиксированная'; ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <strong>Мин. заказ:</strong> 
                                <?php echo $promo['min_order_amount'] > 0 ? number_format($promo['min_order_amount'], 0, ',', ' ') . ' ₽' : 'Нет'; ?>
                            </div>
                            <div style="margin-bottom: 5px;">
                                <strong>Лимит:</strong> 
                                <?php echo $promo['usage_limit'] ? $promo['usage_limit'] . ' раз' : 'Безлимитно'; ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php if ($promo['valid_from'] && $promo['valid_until']): ?>
                                    <?php echo date('d.m.Y', strtotime($promo['valid_from'])); ?> - <?php echo date('d.m.Y', strtotime($promo['valid_until'])); ?>
                                <?php elseif ($promo['valid_until']): ?>
                                    До <?php echo date('d.m.Y', strtotime($promo['valid_until'])); ?>
                                <?php else: ?>
                                    Бессрочно
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="text-align: center;">
                                <div style="font-size: 1.2rem; font-weight: bold; color: #007bff;">
                                    <?php echo $promo['used_count']; ?>
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">
                                    из <?php echo $promo['usage_limit'] ?: '∞'; ?>
                                </div>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <?php if (!$promo['is_active']): ?>
                                <span style="color: #dc3545; font-weight: bold;">❌ Неактивен</span>
                            <?php elseif ($is_expired): ?>
                                <span style="color: #dc3545; font-weight: bold;">⏰ Истек</span>
                            <?php elseif ($is_future): ?>
                                <span style="color: #ffc107; font-weight: bold;">⏳ Будущий</span>
                            <?php elseif ($usage_limit_reached): ?>
                                <span style="color: #dc3545; font-weight: bold;">🚫 Лимит</span>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: bold;">✅ Активен</span>
                            <?php endif; ?>
                            
                            <form method="POST" style="margin-top: 8px;">
                                <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo $promo['is_active'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()">
                                    <span style="font-size: 0.9rem; color: #666;">
                                        <?php echo $promo['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </label>
                                <input type="hidden" name="toggle_status" value="1">
                            </form>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 8px;">
                                <a href="promo_details.php?id=<?php echo $promo['id']; ?>" 
                                   style="
                                       background: #007bff;
                                       color: white;
                                       padding: 6px 12px;
                                       text-decoration: none;
                                       border-radius: 4px;
                                       font-size: 0.9rem;
                                   ">
                                    Детали
                                </a>
                                
                                <button onclick="confirmDelete(<?php echo $promo['id']; ?>)" 
                                        style="
                                            background: #dc3545;
                                            color: white;
                                            padding: 6px 12px;
                                            border: none;
                                            border-radius: 4px;
                                            font-size: 0.9rem;
                                            cursor: pointer;
                                        ">
                                    Удалить
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
function confirmDelete(promoId) {
    if (confirm('Вы уверены, что хотите удалить этот промо-код?')) {
        window.location.href = 'promo_delete.php?id=' + promoId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>