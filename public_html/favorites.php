<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

// Получаем избранные программы
$stmt = $pdo->prepare("
    SELECT mp.*, ng.name as goal_name 
    FROM favorites f
    JOIN meal_plans mp ON f.meal_plan_id = mp.id
    LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
    WHERE f.user_id = ? AND mp.is_active = TRUE
    ORDER BY f.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$favorite_plans = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <h2>❤️ Избранные программы</h2>
    
    <?php if (empty($favorite_plans)): ?>
        <div style="text-align: center; padding: 60px; color: #666;">
            <div style="font-size: 4rem; margin-bottom: 20px;">❤️</div>
            <h3>У вас пока нет избранных программ</h3>
            <p>Добавляйте программы в избранное, чтобы вернуться к ним позже</p>
            <a href="catalog.php" style="
                background: #007bff;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                display: inline-block;
                margin-top: 20px;
            ">
                Перейти в каталог
            </a>
        </div>
    <?php else: ?>
        <p style="color: #666; margin-bottom: 30px;">
            Найдено программ: <strong><?php echo count($favorite_plans); ?></strong>
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
            <?php foreach ($favorite_plans as $plan): ?>
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
                
                <!-- Кнопка удаления из избранного -->
                <div style="position: absolute; top: 15px; left: 15px; z-index: 2;">
                    <button onclick="removeFromFavorites(<?php echo $plan['id']; ?>, this)" 
                            style="
                                background: #dc3545;
                                color: white;
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
                            title="Удалить из избранного">
                        ♥
                    </button>
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
                ">
                    <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; font-weight: 600;"><?php echo e($plan['title']); ?></h3>
                    <div style="opacity: 0.9; font-size: 0.95rem;"><?php echo e($plan['goal_name']); ?></div>
                </div>
                
                <div style="padding: 25px;">
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
                    
                    <p style="color: #666; line-height: 1.6; margin-bottom: 25px; font-size: 0.95rem;">
                        <?php echo e($plan['description']); ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                                <?php echo number_format($plan['price'], 0, ',', ' '); ?> ₽
                            </div>
                            <div style="color: #666; font-size: 0.9rem;">
                                за неделю
                            </div>
                        </div>
                        
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
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromFavorites(planId, button) {
    const card = button.closest('.favorite-card') || button.closest('div').parentElement;
    
    fetch('favorite_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'plan_id=' + planId + '&action=remove'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Анимация удаления
            card.style.transform = 'scale(0.9)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                showNotification('Удалено из избранного', 'info');
                
                // Обновляем счетчик если он есть
                updateFavoritesCount();
                
                // Если карточек не осталось, показываем пустое состояние
                if (document.querySelectorAll('.favorite-card, [style*="transform"]').length === 0) {
                    location.reload();
                }
            }, 300);
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

function updateFavoritesCount() {
    // Можно добавить обновление счетчика в шапке
    const countElements = document.querySelectorAll('.favorites-count');
    countElements.forEach(el => {
        const current = parseInt(el.textContent) || 0;
        el.textContent = current - 1;
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
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
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>