<?php
require_once 'config.php';

// Получаем фильтры и сортировку
$goal_filter = $_GET['goal'] ?? '';
$calories_filter = $_GET['calories'] ?? '';
$sort = $_GET['sort'] ?? 'price_asc';
$search = $_GET['search'] ?? '';

// Формируем запрос с фильтрами
$sql = "SELECT mp.*, ng.name as goal_name FROM meal_plans mp LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id WHERE mp.is_active = TRUE";
$params = [];

if ($goal_filter) {
    $sql .= " AND ng.name = ?";
    $params[] = $goal_filter;
}

if ($calories_filter) {
    if ($calories_filter === 'low') {
        $sql .= " AND mp.calories <= 1500";
    } elseif ($calories_filter === 'medium') {
        $sql .= " AND mp.calories BETWEEN 1501 AND 2000";
    } elseif ($calories_filter === 'high') {
        $sql .= " AND mp.calories > 2000";
    }
}

if ($search) {
    $sql .= " AND (mp.title LIKE ? OR mp.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Сортировка
switch ($sort) {
    case 'price_desc':
        $sql .= " ORDER BY mp.price DESC";
        break;
    case 'calories_asc':
        $sql .= " ORDER BY mp.calories ASC";
        break;
    case 'calories_desc':
        $sql .= " ORDER BY mp.calories DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY mp.title ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY mp.title DESC";
        break;
    default:
        $sql .= " ORDER BY mp.price ASC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$meal_plans = $stmt->fetchAll();

// Получаем цели для фильтра
$goals = $pdo->query("SELECT DISTINCT name FROM nutrition_goals")->fetchAll();

// Статистика
$total_plans = count($meal_plans);

if (isset($_SESSION['user_id'])) {
    // Отслеживаем просмотр каталога (общее)
    trackUserBehavior($_SESSION['user_id'], 0, 'view');
}
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <h2>Программы питания</h2>
    
    <!-- Поиск и фильтры -->
    <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <!-- Поиск -->
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Поиск:</label>
                <input type="text" name="search" value="<?php echo e($search); ?>" 
                       placeholder="Название программы..."
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <!-- Цель -->
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Цель:</label>
                <select name="goal" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Все цели</option>
                    <?php foreach ($goals as $goal): ?>
                        <option value="<?php echo e($goal['name']); ?>" <?php echo ($goal_filter === $goal['name']) ? 'selected' : ''; ?>>
                            <?php echo e($goal['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Калории -->
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Калории:</label>
                <select name="calories" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Все</option>
                    <option value="low" <?php echo ($calories_filter === 'low') ? 'selected' : ''; ?>>До 1500 ккал</option>
                    <option value="medium" <?php echo ($calories_filter === 'medium') ? 'selected' : ''; ?>>1500-2000 ккал</option>
                    <option value="high" <?php echo ($calories_filter === 'high') ? 'selected' : ''; ?>>Выше 2000 ккал</option>
                </select>
            </div>
            
            <!-- Сортировка -->
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Сортировка:</label>
                <select name="sort" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Цена (по возрастанию)</option>
                    <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Цена (по убыванию)</option>
                    <option value="calories_asc" <?php echo ($sort === 'calories_asc') ? 'selected' : ''; ?>>Калории (по возрастанию)</option>
                    <option value="calories_desc" <?php echo ($sort === 'calories_desc') ? 'selected' : ''; ?>>Калории (по убыванию)</option>
                    <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Название (А-Я)</option>
                    <option value="name_desc" <?php echo ($sort === 'name_desc') ? 'selected' : ''; ?>>Название (Я-А)</option>
                </select>
            </div>
            
            <!-- Кнопки -->
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="
                    background: #007bff;
                    color: white;
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                ">
                    🔍 Применить
                </button>
                <a href="catalog.php" style="
                    background: #6c757d;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 4px;
                    display: inline-flex;
                    align-items: center;
                ">
                    Сбросить
                </a>
            </div>
        </form>
        
        <!-- Статистика -->
        <div style="margin-top: 15px; color: #666; font-size: 0.9rem;">
            Найдено программ: <strong><?php echo $total_plans; ?></strong>
            <?php if ($goal_filter): ?> • Цель: <strong><?php echo e($goal_filter); ?></strong><?php endif; ?>
            <?php if ($calories_filter): ?> • Калории: <strong>
                <?php 
                if ($calories_filter === 'low') echo 'До 1500 ккал';
                elseif ($calories_filter === 'medium') echo '1500-2000 ккал';
                else echo 'Выше 2000 ккал';
                ?>
            </strong><?php endif; ?>
        </div>
    </div>

    <!-- Сетка программ -->
    <?php if (empty($meal_plans)): ?>
        <div style="text-align: center; padding: 60px; color: #666;">
            <h3>Программы не найдены</h3>
            <p>Попробуйте изменить параметры поиска или сбросить фильтры</p>
            <a href="catalog.php" style="
                background: #007bff;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                display: inline-block;
                margin-top: 15px;
            ">
                Сбросить фильтры
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
            <?php foreach ($meal_plans as $plan): ?>
            <div style="
                border: 1px solid #e9ecef;
                border-radius: 12px;
                overflow: hidden;
                background: white;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                position: relative;
            " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 15px rgba(0,0,0,0.15)'" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'">
                
                <!-- Кнопка избранного -->
                <div style="position: absolute; top: 15px; left: 15px; z-index: 2;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="toggleFavorite(<?php echo $plan['id']; ?>, this)" 
                                style="
                                    background: <?php echo isFavorite($_SESSION['user_id'], $plan['id']) ? '#dc3545' : 'rgba(255,255,255,0.9)'; ?>;
                                    color: <?php echo isFavorite($_SESSION['user_id'], $plan['id']) ? 'white' : '#666'; ?>;
                                    border: none;
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    cursor: pointer;
                                    font-size: 1.2rem;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                                " 
                                title="<?php echo isFavorite($_SESSION['user_id'], $plan['id']) ? 'Удалить из избранного' : 'Добавить в избранное'; ?>">
                            ♥
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Бейдж цели -->
                <div style="
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: <?php 
                        if ($plan['goal_name'] === 'похудение') echo '#28a745';
                        elseif ($plan['goal_name'] === 'поддержание') echo '#007bff';
                        else echo '#ffc107';
                    ?>;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 500;
                    z-index: 2;
                ">
                    <?php echo e($plan['goal_name']); ?>
                </div>
                
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 25px;
                    text-align: center;
                    position: relative;
                ">
                    <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 600;"><?php echo e($plan['title']); ?></h3>
                    <div style="opacity: 0.9; font-size: 0.95rem;"><?php echo e($plan['goal_name']); ?></div>
                </div>
                
                <div style="padding: 25px;">
                    <!-- Характеристики -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <div style="font-size: 0.8rem; color: #666; margin-bottom: 5px;">Калории</div>
                            <div style="font-size: 1.1rem; font-weight: bold; color: #333;">
                                <?php echo $plan['calories']; ?> ккал
                            </div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <div style="font-size: 0.8rem; color: #666; margin-bottom: 5px;">Питание</div>
                            <div style="font-size: 1.1rem; font-weight: bold; color: #333;">7 дней</div>
                        </div>
                    </div>
                    
                    <!-- Рейтинг -->
                    <div style="margin-bottom: 15px;">
                        <?php
                        $rating_info = getMealPlanRating($plan['id']);
                        if ($rating_info && $rating_info['reviews_count'] > 0):
                        ?>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                                <div style="color: #ffc107; font-weight: bold;">
                                    <?php echo str_repeat('★', round($rating_info['average_rating'])) . str_repeat('☆', 5 - round($rating_info['average_rating'])); ?>
                                </div>
                                <div style="color: #666;">
                                    <?php echo number_format($rating_info['average_rating'], 1); ?> (<?php echo $rating_info['reviews_count']; ?>)
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="color: #999; font-size: 0.9rem;">
                                ★★★★★ (пока нет отзывов)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Описание -->
                    <p style="color: #666; line-height: 1.6; margin-bottom: 25px; font-size: 0.95rem;">
                        <?php echo e($plan['description']); ?>
                    </p>
                    
                    <!-- Цена и кнопка -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                                <?php echo number_format($plan['price'], 0, ',', ' '); ?> ₽
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                за неделю
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button onclick="addToCart(<?php echo $plan['id']; ?>)" 
                                    style="
                                        background: #28a745;
                                        color: white;
                                        padding: 12px 24px;
                                        border: none;
                                        border-radius: 6px;
                                        cursor: pointer;
                                        font-weight: 600;
                                        transition: background 0.3s ease;
                                    " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                                🛒 В корзину
                            </button>
                        <?php else: ?>
                            <a href="auth.php" style="
                                background: #007bff;
                                color: white;
                                padding: 12px 24px;
                                text-decoration: none;
                                border-radius: 6px;
                                font-weight: 600;
                                transition: background 0.3s ease;
                                display: inline-block;
                            " onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                                Войдите чтобы заказать
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Кнопка отзыва -->
                    <?php if (isset($_SESSION['user_id']) && !hasUserReviewed($_SESSION['user_id'], $plan['id'])): ?>
                        <div style="margin-top: 15px; text-align: center;">
                            <button onclick="showReviewModal(<?php echo $plan['id']; ?>, '<?php echo e($plan['title']); ?>')" 
                                    style="
                                        background: transparent;
                                        color: #007bff;
                                        border: 1px solid #007bff;
                                        padding: 8px 16px;
                                        border-radius: 20px;
                                        cursor: pointer;
                                        font-size: 0.9rem;
                                        transition: all 0.3s ease;
                                    " onmouseover="this.style.background='#007bff'; this.style.color='white'" 
                                    onmouseout="this.style.background='transparent'; this.style.color='#007bff'">
                                💬 Оставить отзыв
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно отзыва -->
<div id="reviewModal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: white;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        position: relative;
    ">
        <button onclick="closeReviewModal()" style="
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        ">×</button>
        
        <h3 style="margin: 0 0 20px 0; color: #333;" id="reviewModalTitle">Оставить отзыв</h3>
        
        <form id="reviewForm">
            <input type="hidden" id="reviewMealPlanId" name="meal_plan_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Ваша оценка:</label>
                <div style="display: flex; gap: 5px; margin-bottom: 10px;" id="ratingStars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span style="
                            font-size: 2rem;
                            color: #ddd;
                            cursor: pointer;
                            transition: color 0.2s ease;
                        " data-rating="<?php echo $i; ?>" onmouseover="highlightStars(<?php echo $i; ?>)" 
                           onmouseout="resetStars()" onclick="setRating(<?php echo $i; ?>)">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="selectedRating" name="rating" required>
                <div style="color: #666; font-size: 0.9rem;" id="ratingText">Выберите оценку</div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Ваш отзыв:</label>
                <textarea id="reviewComment" name="comment" 
                          style="
                              width: 100%;
                              padding: 12px;
                              border: 1px solid #ddd;
                              border-radius: 6px;
                              height: 100px;
                              resize: vertical;
                          " 
                          placeholder="Поделитесь вашим опытом использования программы..."></textarea>
            </div>
            
            <button type="submit" style="
                background: #28a745;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                width: 100%;
            ">
                📨 Отправить отзыв
            </button>
        </form>
    </div>
</div>

<script>
// Переменные для избранного
function toggleFavorite(planId, button) {
    fetch('favorite_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'plan_id=' + planId + '&action=toggle'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.is_favorite) {
                button.style.background = '#dc3545';
                button.style.color = 'white';
                button.title = 'Удалить из избранного';
                showNotification('Добавлено в избранное!', 'success');
                updateFavoritesCount(1);
            } else {
                button.style.background = 'rgba(255,255,255,0.9)';
                button.style.color = '#666';
                button.title = 'Добавить в избранное';
                showNotification('Удалено из избранного', 'info');
                updateFavoritesCount(-1);
            }
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка сети', 'error');
    });
}

function addToCart(planId) {
    showNotification('Программа добавлена в корзину!', 'success');
    
    setTimeout(() => {
        window.location.href = 'cart.php?add=' + planId;
    }, 1000);
}

function updateFavoritesCount(change) {
    const countElements = document.querySelectorAll('.favorites-count');
    countElements.forEach(el => {
        const current = parseInt(el.textContent) || 0;
        const newCount = Math.max(0, current + change);
        el.textContent = newCount;
        
        const badge = el.closest('a').querySelector('span');
        if (badge) {
            if (newCount > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = newCount;
            } else {
                badge.style.display = 'none';
            }
        }
    });
}

// Переменные для модального окна отзывов
let currentRating = 0;
let currentMealPlanId = null;

function showReviewModal(mealPlanId, mealPlanTitle) {
    currentMealPlanId = mealPlanId;
    document.getElementById('reviewModalTitle').textContent = `Оставить отзыв: ${mealPlanTitle}`;
    document.getElementById('reviewMealPlanId').value = mealPlanId;
    document.getElementById('reviewModal').style.display = 'flex';
    resetStars();
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
    currentRating = 0;
    document.getElementById('selectedRating').value = '';
    document.getElementById('reviewComment').value = '';
}

function highlightStars(rating) {
    const stars = document.querySelectorAll('#ratingStars span');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.color = '#ffc107';
        } else {
            star.style.color = '#ddd';
        }
    });
}

function resetStars() {
    const stars = document.querySelectorAll('#ratingStars span');
    stars.forEach((star, index) => {
        if (index < currentRating) {
            star.style.color = '#ffc107';
        } else {
            star.style.color = '#ddd';
        }
    });
}

function setRating(rating) {
    currentRating = rating;
    document.getElementById('selectedRating').value = rating;
    
    const ratingTexts = [
        'Ужасно',
        'Плохо',
        'Нормально',
        'Хорошо',
        'Отлично'
    ];
    document.getElementById('ratingText').textContent = ratingTexts[rating - 1];
    resetStars();
}

// Обработка формы отзыва
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_review');
    
    fetch('review_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Отзыв успешно добавлен!', 'success');
            closeReviewModal();
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка сети', 'error');
    });
});

// Закрытие модального окна по клику на фон
document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReviewModal();
    }
});

// Функция уведомлений
function showNotification(message, type) {
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        font-weight: 500;
        max-width: 300px;
        word-wrap: break-word;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Обработчики для всех кнопок "В корзину"
document.addEventListener('DOMContentLoaded', function() {
    const cartButtons = document.querySelectorAll('button[onclick*="addToCart"]');
    cartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const planId = this.getAttribute('onclick').match(/\d+/)[0];
            addToCart(planId);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>