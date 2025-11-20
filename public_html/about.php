<?php
require_once 'config.php';
?>

<?php include 'includes/header.php'; ?>

<div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
    <!-- Герой-секция -->
    <section style="text-align: center; padding: 60px 0;">
        <h1 style="font-size: 3rem; margin-bottom: 20px; color: #333;">О ФитПаёк</h1>
        <p style="font-size: 1.3rem; color: #666; max-width: 600px; margin: 0 auto;">
            Мы делаем здоровое питание доступным и удобным для каждого
        </p>
    </section>

    <!-- Наша миссия -->
    <section style="padding: 60px 0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
            <div>
                <h2 style="color: #333; margin-bottom: 20px;">Наша миссия</h2>
                <p style="color: #666; line-height: 1.7; margin-bottom: 20px;">
                    ФитПаёк был создан с целью сделать здоровое питание простым и доступным. 
                    Мы понимаем, как сложно совмещать работу, тренировки и приготовление 
                    сбалансированной пищи.
                </p>
                <p style="color: #666; line-height: 1.7;">
                    Наша команда диетологов и шеф-поваров разрабатывает рационы, которые 
                    не только помогают достигать фитнес-целей, но и радуют вкусом.
                </p>
            </div>
            <div style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 40px;
                border-radius: 12px;
                text-align: center;
            ">
                <div style="font-size: 3rem; margin-bottom: 20px;">🥗</div>
                <h3 style="margin: 0 0 15px 0;">Свежесть и качество</h3>
                <p style="margin: 0; opacity: 0.9;">
                    Только свежие продукты и бережное приготовление
                </p>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section style="padding: 60px 0; background: #f8f9fa; border-radius: 12px; margin: 40px 0;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="color: #333; margin-bottom: 15px;">Почему выбирают нас</h2>
            <p style="color: #666; max-width: 600px; margin: 0 auto;">
                4 ключевых преимущества, которые делают ФитПаёк лучшим выбором
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #007bff;
                    color: white;
                    width: 70px;
                    height: 70px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.8rem;
                ">👨‍⚕️</div>
                <h3 style="color: #333; margin-bottom: 15px;">Экспертный подход</h3>
                <p style="color: #666;">
                    Рационы разработаны диетологами с учетом всех nutritional needs
                </p>
            </div>
            
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #28a745;
                    color: white;
                    width: 70px;
                    height: 70px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.8rem;
                ">🚚</div>
                <h3 style="color: #333; margin-bottom: 15px;">Удобная доставка</h3>
                <p style="color: #666;">
                    Привозим заказы утром в удобное время прямо к вашей двери
                </p>
            </div>
            
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #6f42c1;
                    color: white;
                    width: 70px;
                    height: 70px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.8rem;
                ">🎯</div>
                <h3 style="color: #333; margin-bottom: 15px;">Индивидуальный подход</h3>
                <p style="color: #666;">
                    Программы для любых целей: похудение, поддержание, набор массы
                </p>
            </div>
            
            <div style="text-align: center; padding: 30px;">
                <div style="
                    background: #ffc107;
                    color: white;
                    width: 70px;
                    height: 70px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 1.8rem;
                ">💚</div>
                <h3 style="color: #333; margin-bottom: 15px;">Качественные продукты</h3>
                <p style="color: #666;">
                    Используем только свежие и натуральные продукты высшего качества
                </p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section style="text-align: center; padding: 60px 0;">
        <h2 style="color: #333; margin-bottom: 20px;">Готовы начать?</h2>
        <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem;">
            Присоединяйтесь к тысячам довольных клиентов ФитПаёк
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
            ">
                Выбрать программу
            </a>
            <a href="auth.php" style="
                background: transparent;
                color: #007bff;
                padding: 15px 30px;
                text-decoration: none;
                border: 2px solid #007bff;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
            ">
                Создать аккаунт
            </a>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>