<?php
require_once 'config.php';

// Получаем популярные программы для показа на главной
$stmt = $pdo->query("
    SELECT mp.*, ng.name as goal_name 
    FROM meal_plans mp 
    LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id 
    WHERE mp.is_active = TRUE 
    ORDER BY mp.price ASC 
    LIMIT 3
");
$popular_plans = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<!-- Герой-секция -->
<section style="
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h1 style="font-size: 3rem; margin-bottom: 20px; font-weight: 700;">
            ФитПаёк - Питание для ваших целей
        </h1>
        <p style="font-size: 1.3rem; margin-bottom: 30px; opacity: 0.9;">
            Сбалансированные рационы с доставкой на дом. Похудение, поддержание формы или набор массы - мы поможем достичь ваших целей!
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="catalog.php" style="
                background: #28a745;
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                transition: all 0.3s ease;
            " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                Выбрать программу
            </a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="auth.php" style="
                    background: transparent;
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border: 2px solid white;
                    border-radius: 50px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    transition: all 0.3s ease;
                " onmouseover="this.style.background='white'; this.style.color='#667eea'" 
                   onmouseout="this.style.background='transparent'; this.style.color='white'">
                    Начать сейчас
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Преимущества -->
<section style="padding: 80px 0; background: #f8f9fa;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.5rem; color: #333;">
            Почему выбирают ФитПаёк?
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #007bff;
                    color: white;
                    width: 80px;
                    height: 80px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 2rem;
                ">🥗</div>
                <h3 style="color: #333; margin-bottom: 15px;">Сбалансированное питание</h3>
                <p style="color: #666; line-height: 1.6;">
                    Каждое блюдо разработано диетологами с оптимальным балансом БЖУ. Только свежие и качественные продукты.
                </p>
            </div>
            
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #28a745;
                    color: white;
                    width: 80px;
                    height: 80px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 2rem;
                ">🚚</div>
                <h3 style="color: #333; margin-bottom: 15px;">Удобная доставка</h3>
                <p style="color: #666; line-height: 1.6;">
                    Привозим заказы утром в удобное время. Не нужно готовить - разогрей и наслаждайся!
                </p>
            </div>
            
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #6f42c1;
                    color: white;
                    width: 80px;
                    height: 80px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 2rem;
                ">🎯</div>
                <h3 style="color: #333; margin-bottom: 15px;">Индивидуальный подход</h3>
                <p style="color: #666; line-height: 1.6;">
                    Программы для похудения, поддержания веса или набора массы. Выбирай то, что подходит именно тебе.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Популярные программы -->
<section style="padding: 80px 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.5rem; color: #333;">
            Популярные программы
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
            <?php foreach ($popular_plans as $plan): ?>
            <div style="
                border: 1px solid #e9ecef;
                border-radius: 12px;
                overflow: hidden;
                background: white;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            " onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 25px;
                    text-align: center;
                ">
                    <h3 style="margin: 0 0 10px 0; font-size: 1.5rem;"><?php echo e($plan['title']); ?></h3>
                    <div style="opacity: 0.9;"><?php echo e($plan['goal_name']); ?></div>
                </div>
                
                <div style="padding: 25px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <div style="text-align: center;">
                            <div style="font-size: 0.9rem; color: #666;">Калории</div>
                            <div style="font-size: 1.2rem; font-weight: bold; color: #333;">
                                <?php echo $plan['calories']; ?> ккал
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 0.9rem; color: #666;">Питание</div>
                            <div style="font-size: 1.2rem; font-weight: bold; color: #333;">7 дней</div>
                        </div>
                    </div>
                    
                    <p style="color: #666; line-height: 1.6; margin-bottom: 25px;">
                        <?php echo e($plan['description']); ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                            <?php echo number_format($plan['price'], 0, ',', ' '); ?> ₽
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="catalog.php" style="
                                background: #007bff;
                                color: white;
                                padding: 10px 20px;
                                text-decoration: none;
                                border-radius: 6px;
                                font-weight: 600;
                                transition: background 0.3s ease;
                            " onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                                Заказать
                            </a>
                        <?php else: ?>
                            <a href="auth.php" style="
                                background: #28a745;
                                color: white;
                                padding: 10px 20px;
                                text-decoration: none;
                                border-radius: 6px;
                                font-weight: 600;
                                transition: background 0.3s ease;
                            " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                                Начать
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <a href="catalog.php" style="
                background: transparent;
                color: #007bff;
                padding: 15px 30px;
                text-decoration: none;
                border: 2px solid #007bff;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                transition: all 0.3s ease;
            " onmouseover="this.style.background='#007bff'; this.style.color='white'" 
               onmouseout="this.style.background='transparent'; this.style.color='#007bff'">
                Смотреть все программы →
            </a>
        </div>
    </div>
</section>

<!-- Как это работает -->
<section style="padding: 80px 0; background: #f8f9fa;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.5rem; color: #333;">
            Как это работает?
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px;">
            <div style="text-align: center;">
                <div style="
                    background: white;
                    color: #007bff;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.5rem;
                    font-weight: bold;
                    border: 2px solid #007bff;
                ">1</div>
                <h3 style="color: #333; margin-bottom: 15px;">Выбери программу</h3>
                <p style="color: #666;">
                    Подбери питание по своим целям и предпочтениям
                </p>
            </div>
            
            <div style="text-align: center;">
                <div style="
                    background: white;
                    color: #007bff;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.5rem;
                    font-weight: bold;
                    border: 2px solid #007bff;
                ">2</div>
                <h3 style="color: #333; margin-bottom: 15px;">Оформи заказ</h3>
                <p style="color: #666;">
                    Укажи адрес и удобное время доставки
                </p>
            </div>
            
            <div style="text-align: center;">
                <div style="
                    background: white;
                    color: #007bff;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.5rem;
                    font-weight: bold;
                    border: 2px solid #007bff;
                ">3</div>
                <h3 style="color: #333; margin-bottom: 15px;">Получи рацион</h3>
                <p style="color: #666;">
                    Мы привезем свежие блюда прямо к твоей двери
                </p>
            </div>
            
            <div style="text-align: center;">
                <div style="
                    background: white;
                    color: #007bff;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.5rem;
                    font-weight: bold;
                    border: 2px solid #007bff;
                ">4</div>
                <h3 style="color: #333; margin-bottom: 15px;">Наслаждайся</h3>
                <p style="color: #666;">
                    Питайся вкусно и двигайся к своей цели
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA секция -->
<section style="
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h2 style="font-size: 2.2rem; margin-bottom: 20px;">
            Готовы начать путь к здоровому питанию?
        </h2>
        <p style="font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9;">
            Присоединяйтесь к тысячам довольных клиентов, которые уже достигли своих целей с ФитПаёк
        </p>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'catalog.php' : 'auth.php'; ?>" style="
            background: white;
            color: #28a745;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
            Начать сейчас
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>