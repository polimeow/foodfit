<?php
require_once 'config.php';

// Обработка формы
if ($_POST['action'] ?? '') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Базовая валидация
    if (empty($email) || empty($password)) {
        $error = "Все поля обязательны для заполнения.";
    } else {
        if ($_POST['action'] == 'register') {
            // РЕГИСТРАЦИЯ
            $name = $_POST['name'] ?? '';
            // Проверяем, нет ли уже такого пользователя
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Пользователь с таким email уже существует.";
            } else {
                // Хешируем пароль
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt->execute([$email, $passwordHash, $name])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    header('Location: /personal/');
                    exit();
                } else {
                    $error = "Ошибка при регистрации.";
                }
            }
        } elseif ($_POST['action'] == 'login') {
            // АВТОРИЗАЦИЯ
            $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: /personal/');
                exit();
            } else {
                $error = "Неверный email или пароль.";
            }
        }
    }
}

// Выход
if ($_GET['action'] ?? '' == 'logout') {
    session_destroy();
    header('Location: /');
    exit();
}

include 'includes/header.php';
?>

<h2>Вход / Регистрация</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo e($error); ?></p>
<?php endif; ?>

<!-- Форма Входа -->
<form method="POST">
    <h3>Вход</h3>
    <input type="hidden" name="action" value="login">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Пароль" required>
    <button type="submit">Войти</button>
</form>

<hr>

<!-- Форма Регистрации -->
<form method="POST">
    <h3>Регистрация</h3>
    <input type="hidden" name="action" value="register">
    <input type="text" name="name" placeholder="Ваше имя" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Пароль" required>
    <button type="submit">Зарегистрироваться</button>
</form>

<?php include 'includes/footer.php'; ?>