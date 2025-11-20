<?php
// includes/footer.php
?>
    </main>
    
    <footer style="background: #343a40; color: white; padding: 40px 0; margin-top: 80px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px;">
                <div>
                    <h3 style="color: #28a745; margin-bottom: 20px; font-size: 1.5rem;">ФитПаёк</h3>
                    <p style="color: #adb5bd; line-height: 1.6;">
                        Доставка сбалансированного питания для достижения ваших фитнес-целей. Качество, вкус и результат в каждой порции.
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 15px; color: white;">Контакты</h4>
                    <div style="color: #adb5bd;">
                        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <span>📞</span>
                            <span>+7 (999) 123-45-67</span>
                        </div>
                        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <span>✉️</span>
                            <span>info@fitpaek.ru</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>🕒</span>
                            <span>Ежедневно 9:00-21:00</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 15px; color: white;">Быстрые ссылки</h4>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="/" style="color: #adb5bd; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#adb5bd'">
                            Главная
                        </a>
                        <a href="catalog.php" style="color: #adb5bd; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#adb5bd'">
                            Программы питания
                        </a>
                        <a href="auth.php" style="color: #adb5bd; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='white'" onmouseout="this.style.color='#adb5bd'">
                            Личный кабинет
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="personal/orders.php" style="color: #adb5bd; text-decoration: none; transition: color 0.3s ease;"
                               onmouseover="this.style.color='white'" onmouseout="this.style.color='#adb5bd'">
                                Мои заказы
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid #495057; margin-top: 40px; padding-top: 20px; text-align: center; color: #adb5bd;">
                <p>&copy; <?php echo date('Y'); ?> ФитПаёк. Все права защищены.</p>
            </div>
        </div>
    </footer>
</body>
</html>