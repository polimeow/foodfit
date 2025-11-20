<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ФитПаёк - Доставка готового питания</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo:hover {
            color: #218838;
        }
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 8px 0;
            position: relative;
        }
        .nav-links a:hover {
            color: #28a745;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #28a745;
            transition: width 0.3s ease;
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .auth-buttons {
            display: flex;
            gap: 15px;
        }
        .btn-outline {
            background: transparent;
            color: #28a745;
            padding: 8px 20px;
            border: 2px solid #28a745;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline:hover {
            background: #28a745;
            color: white;
        }
        .btn-primary {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border: 2px solid #28a745;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #218838;
            border-color: #218838;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-name {
            color: #333;
            font-weight: 500;
        }
        .favorites-link {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #333;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .favorites-link:hover {
            background: #f8f9fa;
            color: #dc3545;
        }
        .favorites-badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            min-width: 18px;
            text-align: center;
        }
        
        /* Мобильная навигация */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                gap: 15px;
            }
            .nav-links.active {
                display: flex;
            }
            .auth-buttons {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            .btn-outline, .btn-primary {
                text-align: center;
                width: 100%;
            }
            .user-menu {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="/" class="logo">
                🥗 ФитПаёк
            </a>
            
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
            
            <div class="nav-links" id="navLinks">
                <a href="/">Главная</a>
                <a href="about.php">О нас</a>
                <a href="catalog.php">Программы</a>
                <a href="contacts.php">Контакты</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <a href="favorites.php" class="favorites-link" title="Избранное">
                            ❤️ Избранное
                            <?php
                            $favorites_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $favorites_count = $stmt->fetch()['count'];
                            }
                            if ($favorites_count > 0): ?>
                                <span class="favorites-badge favorites-count"><?php echo $favorites_count; ?></span>
                            <?php else: ?>
                                <span class="favorites-badge favorites-count" style="display: none;">0</span>
                            <?php endif; ?>
                        </a>
                        <span class="user-name"><?php echo e($_SESSION['user_name']); ?></span>
                        <a href="personal/" class="btn-outline">Кабинет</a>
                        <a href="auth.php?action=logout" class="btn-primary">Выйти</a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="auth.php" class="btn-outline">Войти</a>
                        <a href="auth.php" class="btn-primary">Регистрация</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>

<script>
function toggleMobileMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('active');
}

// Закрытие мобильного меню при клике outside
document.addEventListener('click', function(e) {
    const navLinks = document.getElementById('navLinks');
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    
    if (!navLinks.contains(e.target) && !mobileBtn.contains(e.target)) {
        navLinks.classList.remove('active');
    }
});

// Закрытие мобильного меню при ресайзе
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        document.getElementById('navLinks').classList.remove('active');
    }
});
</script>