<?php
require_once 'config.php';

$program_id = $_GET['id'] ?? 0;

if (!$program_id) {
    header('Location: catalog.php');
    exit();
}

// Получаем информацию о программе
$stmt = $pdo->prepare("
    SELECT mp.*, ng.name as goal_name, ng.description as goal_description
    FROM meal_plans mp
    LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
    WHERE mp.id = ? AND mp.is_active = TRUE
");
$stmt->execute([$program_id]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: catalog.php');
    exit();
}

// Отслеживаем просмотр
if (isset($_SESSION['user_id'])) {
    trackUserBehavior($_SESSION['user_id'], $program_id, 'view');
}

// Получаем рейтинг и отзывы
$rating_info = getMealPlanRating($program_id);

// Получаем похожие программы
$similar_plans = $pdo->prepare("
    SELECT mp.*, ng.name as goal_name
    FROM meal_plans mp
    LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
    WHERE mp.goal_id = ? AND mp.id != ? AND mp.is_active = TRUE
    ORDER BY mp.average_rating DESC
    LIMIT 3
")->execute([$program['goal_id'], $program_id])->fetchAll();

// Получаем отзывы
$reviews = $pdo->prepare("
    SELECT r.*, u.name as user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.meal_plan_id = ? AND r.is_approved = TRUE
    ORDER BY r.created_at DESC
    LIMIT 5
")->execute([$program_id])->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <!-- Хлебные крошки -->
    <nav style="margin: 20px 0; color: #666;">
        <a href="/" style="color: #007bff; text-decoration: none;">Главная</a> /
        <a href="catalog.php" style="color: #007bff; text-decoration: none;">Программы</a> /
        <span><?php echo e($program['title']); ?></span>
    </nav>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
        <!-- Основная информация -->
        <div>
            <div style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 12px;
                text-align: center;
                margin-bottom: 20px;
            ">
                <h1 style="margin: 0 0 10px 0; font-size: 2rem;"><?php echo e($program['title']); ?></h1>
                <div style="font-size: 1.1rem; opacity: 0.9;"><?php echo e($program['goal_name']); ?></div>
            </div>

            <!-- Рейтинг -->
            <?php if ($rating_info && $rating_info['reviews_count'] > 0): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <div style="font-size: 2rem; color: #ffc107; font-weight: bold;">
                        <?php echo number_format($rating_info['average_rating'], 1); ?>
                    </div>
                    <div>
                        <div style="font-size: 1.2rem; color: #ffc107; margin-bottom: 5px;">
                            <?php echo str_repeat('★', round($rating_info['average_rating'])) . str_repeat('☆', 5 - round($rating_info['average_rating'])); ?>
                        </div>
                        <div style="color: #666;">
                            На основе <?php echo $rating_info['reviews_count']; ?> отзывов
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Описание -->
            <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef;">
                <h3 style="margin: 0 0 15px 0; color: #333;">Описание программы</h3>
                <p style="color: #666; line-height: 1.7; margin-bottom: 20px;">
                    <?php echo nl2br(e($program['description'])); ?>
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">Калорийность</div>
                        <div style="font-size: 1.3rem; font-weight: bold; color: #333;">
                            <?php echo $program['calories']; ?> ккал/день
                        </div>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">Продолжительность</div>
                        <div style="font-size: 1.3rem; font-weight: bold; color: #333;">7 дней</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div>
            <!-- Цена и заказ -->
            <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 20px; position: sticky; top: 90px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 2.5rem; font-weight: bold; color: #28a745;">
                        <?php echo number_format($program['price'], 0, ',', ' '); ?> ₽
                    </div>
                    <div style="color: #666;">за неделю питания</div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <button onclick="addToCart(<?php echo $program['id']; ?>)" 
                            style="
                                background: #28a745;
                                color: white;
                                padding: 15px 30px;
                                border: none;
                                border-radius: 6px;
                                font-size: 1.1rem;
                                font-weight: 600;
                                cursor: pointer;
                                width: 100%;
                                margin-bottom: 10px;
                                transition: background 0.3s ease;
                            " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                        🛒 Добавить в корзину
                    </button>
                    
                    <!-- Кнопка избранного -->
                    <div style="text-align: center;">
                        <?php if (isFavorite($_SESSION['user_id'], $program['id'])): ?>
                            <button onclick="toggleFavorite(<?php echo $program['id']; ?>, this)" 
                                    style="
                                        background: #dc3545;
                                        color: white;
                                        border: none;
                                        padding: 10px 20px;
                                        border-radius: 20px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                    ">
                                ❤️ В избранном
                            </button>
                        <?php else: ?>
                            <button onclick="toggleFavorite(<?php echo $program['id']; ?>, this)" 
                                    style="
                                        background: transparent;
                                        color: #666;
                                        border: 1px solid #ddd;
                                        padding: 10px 20px;
                                        border-radius: 20px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                        transition: all 0.3s ease;
                                    " onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                                ♥ Добавить в избранное
                            </button>
                        <?php endif; ?>
                    </div>
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
                            display: block;
                            text-align: center;
                            margin-bottom: 10px;
                       ">
                        🔐 Войдите чтобы заказать
                    </a>
                <?php endif; ?>

                <!-- Преимущества -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #333;">Что включено:</h4>
                    <ul style="color: #666; line-height: 1.6; padding-left: 20px;">
                        <li>Сбалансированное питание на 7 дней</li>
                        <li>Разнообразное меню каждый день</li>
                        <li>Расчет БЖУ под вашу цель</li>
                        <li>Удобная доставка на дом</li>
                        <li>Поддержка диетолога</li>
                    </ul>
                </div>
            </div>

            <!-- Быстрый заказ -->
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; border: 1px solid #b8daff;">
                <h4 style="margin: 0 0 10px 0; color: #004085;">🚀 Быстрый заказ</h4>
                <p style="color: #004085; margin-bottom: 15px; font-size: 0.9rem;">
                    Хотите получить программу уже завтра?
                </p>
                <a href="checkout.php?quick=<?php echo $program['id']; ?>" 
                   style="
                        background: #007bff;
                        color: white;
                        padding: 10px 20px;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 0.9rem;
                        display: inline-block;
                   ">
                    Оформить быстрый заказ
                </a>
            </div>
        </div>
    </div>

    <!-- Отзывы -->
    <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 40px;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333;">Отзывы покупателей</h3>
            <?php if (isset($_SESSION['user_id']) && !hasUserReviewed($_SESSION['user_id'], $program_id)): ?>
                <button onclick="showReviewModal(<?php echo $program_id; ?>, '<?php echo e($program['title']); ?>')" 
                        style="
                            background: #28a745;
                            color: white;
                            padding: 10px 20px;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 0.9rem;
                        ">
                    💬 Написать отзыв
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>Пока нет отзывов о этой программе</p>
                <?php if (isset($_SESSION['user_id']) && !hasUserReviewed($_SESSION['user_id'], $program_id)): ?>
                    <p>Будьте первым, кто оставит отзыв!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 20px;">
                <?php foreach ($reviews as $review): ?>
                <div style="padding: 20px; background: #f8f9fa; border-radius: 6px;">
                    <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <div style="font-weight: bold; color: #333;"><?php echo e($review['user_name']); ?></div>
                            <div style="color: #ffc107; font-size: 1.1rem;">
                                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                            </div>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <?php if ($review['comment']): ?>
                        <p style="color: #666; line-height: 1.6; margin: 0;">
                            <?php echo nl2br(e($review['comment'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Похожие программы -->
    <?php if (!empty($similar_plans)): ?>
    <div style="margin-bottom: 40px;">
        <h3 style="margin: 0 0 20px 0; color: #333;">Похожие программы</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($similar_plans as $similar): ?>
            <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; background: white;">
                <h4 style="margin: 0 0 10px 0; color: #333;"><?php echo e($similar['title']); ?></h4>
                <div style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">
                    <?php echo $similar['calories']; ?> ккал
                </div>
                <div style="font-weight: bold; color: #28a745; margin-bottom: 15px;">
                    <?php echo number_format($similar['price'], 0, ',', ' '); ?> ₽
                </div>
                <a href="program.php?id=<?php echo $similar['id']; ?>" 
                   style="
                        color: #007bff;
                        text-decoration: none;
                        font-size: 0.9rem;
                        font-weight: 500;
                   ">
                    Подробнее →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Подключаем тот же модальный окно отзывов из catalog.php -->
<div id="reviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <!-- Тот же код модального окна что и в catalog.php -->
</div>

<script>
// Функции из catalog.php
function addToCart(planId) {
    showNotification('Программа добавлена в корзину!', 'success');
    setTimeout(() => {
        window.location.href = 'cart.php?add=' + planId;
    }, 1000);
}

function toggleFavorite(planId, button) {
    // Тот же код что и в catalog.php
}

function showReviewModal(planId, planTitle) {
    // Тот же код что и в catalog.php
}

function showNotification(message, type) {
    // Тот же код что и в catalog.php
}
</script>

<?php include 'includes/footer.php'; ?>