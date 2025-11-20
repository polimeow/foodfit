<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Только главный администратор может управлять другими администраторами
if ($_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Обработка добавления администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'manager';
    
    $errors = [];
    
    if (empty($username) || empty($password)) {
        $errors[] = "Все поля обязательны для заполнения";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    
    // Проверяем, нет ли уже такого пользователя
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "Пользователь с таким логином уже существует";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $password_hash, $role]);
        
        $_SESSION['admin_message'] = "Администратор успешно добавлен!";
        header('Location: admins.php');
        exit();
    }
}

// Обработка изменения статуса
if (isset($_POST['toggle_status'])) {
    $admin_id = (int)$_POST['admin_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE admin_users SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $admin_id]);
    
    header('Location: admins.php?updated=1');
    exit();
}

// Получаем список администраторов
$admins = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h2>Управление администраторами</h2>

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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Форма добавления администратора -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Добавить администратора</h3>
        
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Логин:</label>
                <input type="text" name="username" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="Введите логин" required>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Пароль:</label>
                <input type="password" name="password" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="Минимум 6 символов" required minlength="6">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Роль:</label>
                <select name="role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="manager">Менеджер</option>
                    <option value="admin">Администратор</option>
                </select>
                <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                    <strong>Менеджер:</strong> управление заказами и программами<br>
                    <strong>Администратор:</strong> полные права + управление пользователями
                </div>
            </div>
            
            <button type="submit" name="add_admin" style="
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
                ➕ Добавить администратора
            </button>
        </form>
    </div>
    
    <!-- Информация -->
    <div style="background: #e7f3ff; padding: 25px; border-radius: 8px; border: 1px solid #b8daff;">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #004085;">Информация</h3>
        
        <div style="color: #004085;">
            <p><strong>Текущий пользователь:</strong> <?php echo e($_SESSION['admin_username']); ?> (<?php echo e($_SESSION['admin_role'] === 'admin' ? 'Администратор' : 'Менеджер'); ?>)</p>
            
            <div style="background: white; padding: 15px; border-radius: 4px; margin-top: 15px;">
                <h4 style="margin-top: 0; color: #004085;">Права доступа:</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Менеджер:</strong> заказы, программы, промо-коды</li>
                    <li><strong>Администратор:</strong> все права + пользователи + администраторы</li>
                </ul>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-top: 15px;">
                <strong>⚠️ Внимание:</strong> Будьте осторожны при назначении прав администратора.
            </div>
        </div>
    </div>
</div>

<!-- Список администраторов -->
<div style="background: white; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
        <h3 style="margin: 0; color: #333;">Список администраторов</h3>
    </div>
    
    <?php if (empty($admins)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Администраторы не найдены</h4>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 700px;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Администратор</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Роль</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Статус</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Дата создания</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 15px;">
                            <div style="font-weight: bold; margin-bottom: 5px;">
                                <?php echo e($admin['username']); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                ID: <?php echo $admin['id']; ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <span style="
                                background: <?php echo $admin['role'] === 'admin' ? '#dc3545' : '#007bff'; ?>;
                                color: white;
                                padding: 6px 12px;
                                border-radius: 20px;
                                font-size: 0.9rem;
                                font-weight: 500;
                            ">
                                <?php echo $admin['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?>
                            </span>
                        </td>
                        
                        <td style="padding: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo $admin['is_active'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()"
                                           <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'disabled' : ''; ?>>
                                    <span style="color: <?php echo $admin['is_active'] ? '#28a745' : '#dc3545'; ?>; font-weight: 500;">
                                        <?php echo $admin['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </label>
                                <input type="hidden" name="toggle_status" value="1">
                            </form>
                        </td>
                        
                        <td style="padding: 15px;">
                            <div style="margin-bottom: 5px;">
                                <?php echo date('d.m.Y', strtotime($admin['created_at'])); ?>
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <?php echo date('H:i', strtotime($admin['created_at'])); ?>
                            </div>
                        </td>
                        
                        <td style="padding: 15px;">
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <button onclick="confirmDelete(<?php echo $admin['id']; ?>)" 
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
                            <?php else: ?>
                                <span style="color: #666; font-size: 0.9rem;">Текущий пользователь</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(adminId) {
    if (confirm('Вы уверены, что хотите удалить этого администратора?')) {
        window.location.href = 'admin_delete.php?id=' + adminId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>