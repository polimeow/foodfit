<?php
// config.php
session_start();
ob_start(); // Помогает с перенаправлениями

// Настройки БД (данные от Beget)
define('DB_HOST', 'localhost');
define('DB_NAME', 'y91656q3_foodfit'); 
define('DB_USER', 'y91656q3_foodfit'); 
define('DB_PASS', 'Rroott1!'); 

// Настройки работы с избранным
function isFavorite($user_id, $meal_plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND meal_plan_id = ?");
    $stmt->execute([$user_id, $meal_plan_id]);
    return $stmt->fetch() !== false;
}

function addToFavorite($user_id, $meal_plan_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, meal_plan_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $meal_plan_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function removeFromFavorite($user_id, $meal_plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND meal_plan_id = ?");
    return $stmt->execute([$user_id, $meal_plan_id]);
}

// Добавляем в config.php после функций избранного
function getMealPlanRating($meal_plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT average_rating, reviews_count FROM meal_plans WHERE id = ?");
    $stmt->execute([$meal_plan_id]);
    return $stmt->fetch();
}

function hasUserReviewed($user_id, $meal_plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND meal_plan_id = ?");
    $stmt->execute([$user_id, $meal_plan_id]);
    return $stmt->fetch() !== false;
}

// Создание подключения
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для защиты от XSS
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Функция для редиректа
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Функция для рекомендаций
function trackUserBehavior($user_id, $meal_plan_id, $action) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO user_behavior (user_id, meal_plan_id, action) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $meal_plan_id, $action]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function getRecommendedPlans($user_id, $limit = 4) {
    global $pdo;
    
    // Получаем наиболее популярные программы среди пользователей с похожими предпочтениями
    $sql = "
        SELECT mp.*, ng.name as goal_name, COUNT(ub.id) as similarity_score
        FROM meal_plans mp
        LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
        LEFT JOIN user_behavior ub ON mp.id = ub.meal_plan_id
        WHERE mp.is_active = TRUE
        AND ub.user_id IN (
            SELECT DISTINCT ub2.user_id 
            FROM user_behavior ub2 
            WHERE ub2.meal_plan_id IN (
                SELECT meal_plan_id 
                FROM user_behavior 
                WHERE user_id = ? AND action IN ('purchase', 'favorite')
            ) 
            AND ub2.user_id != ?
        )
        AND mp.id NOT IN (
            SELECT meal_plan_id 
            FROM user_behavior 
            WHERE user_id = ? AND action IN ('purchase', 'favorite')
        )
        GROUP BY mp.id
        ORDER BY similarity_score DESC, mp.average_rating DESC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $limit]);
    return $stmt->fetchAll();
}

function getPopularPlans($limit = 4) {
    global $pdo;
    
    $sql = "
        SELECT mp.*, ng.name as goal_name, 
               (mp.average_rating * 0.7 + (mp.reviews_count * 0.3)) as popularity_score
        FROM meal_plans mp
        LEFT JOIN nutrition_goals ng ON mp.goal_id = ng.id
        WHERE mp.is_active = TRUE
        ORDER BY popularity_score DESC, mp.reviews_count DESC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
?>