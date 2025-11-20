<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

$id = $_GET['id'] ?? 0;
$plan = null;
$goals = $pdo->query("SELECT * FROM nutrition_goals")->fetchAll();

// Режим редактирования
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE id = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        header('Location: meal_plans.php');
        exit();
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $calories = (int)($_POST['calories'] ?? 0);
    $goal_id = (int)($_POST['goal_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Валидация
    $errors = [];
    if (empty($title)) $errors[] = "Название обязательно";
    if ($calories <= 0) $errors[] = "Калории должны быть больше 0";
    if ($price <= 0) $errors[] = "Цена должна быть больше 0";
    
    if (empty($errors)) {
        if ($id) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE meal_plans SET title = ?, description = ?, calories = ?, goal_id = ?, price = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $description, $calories, $goal_id, $price, $is_active, $id]);
            $message = "Программа обновлена успешно!";
        } else {
            // Создание
            $stmt = $pdo->prepare("INSERT INTO meal_plans (title, description, calories, goal_id, price, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $description, $calories, $goal_id, $price, $is_active]);
            $id = $pdo->lastInsertId();
            $message = "Программа создана успешно!";
        }
        
        // Обновляем данные программы
        $stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2><?php echo $id ? 'Редактирование' : 'Добавление'; ?> программы питания</h2>

<?php if (isset($message)): ?>
    <div style="color: green; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0;">
        <?php echo e($message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div style="color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0;">
        <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" style="max-width: 600px;">
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Название программы:</label>
        <input type="text" name="title" value="<?php echo e($plan['title'] ?? ''); ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Описание:</label>
        <textarea name="description" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; height: 100px;"><?php echo e($plan['description'] ?? ''); ?></textarea>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Калории:</label>
        <input type="number" name="calories" value="<?php echo e($plan['calories'] ?? ''); ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Цель:</label>
        <select name="goal_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">-- Выберите цель --</option>
            <?php foreach ($goals as $goal): ?>
                <option value="<?php echo $goal['id']; ?>" 
                    <?php echo (($plan['goal_id'] ?? 0) == $goal['id']) ? 'selected' : ''; ?>>
                    <?php echo e($goal['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Цена (руб):</label>
        <input type="number" step="0.01" name="price" value="<?php echo e($plan['price'] ?? ''); ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: inline-flex; align-items: center;">
            <input type="checkbox" name="is_active" value="1" 
                   <?php echo (($plan['is_active'] ?? 1) ? 'checked' : ''); ?> 
                   style="margin-right: 8px;">
            Активная программа
        </label>
    </div>
    
    <div>
        <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            <?php echo $id ? 'Обновить' : 'Создать'; ?> программу
        </button>
        <a href="meal_plans.php" style="margin-left: 10px; color: #6c757d;">Отмена</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>