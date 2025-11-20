<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_review') {
        $meal_plan_id = (int)($_POST['meal_plan_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        // Валидация
        if (!$meal_plan_id) {
            echo json_encode(['success' => false, 'message' => 'Неверный ID программы']);
            exit();
        }
        
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Рейтинг должен быть от 1 до 5']);
            exit();
        }
        
        // Проверяем существование программы
        $stmt = $pdo->prepare("SELECT id FROM meal_plans WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$meal_plan_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Программа не найдена']);
            exit();
        }
        
        // Проверяем, не оставлял ли пользователь уже отзыв
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND meal_plan_id = ?");
        $stmt->execute([$_SESSION['user_id'], $meal_plan_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Вы уже оставляли отзыв на эту программу']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            // Добавляем отзыв
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, meal_plan_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $meal_plan_id, $rating, $comment]);
            
            // Обновляем статистику программы
            updateMealPlanRating($meal_plan_id);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Отзыв успешно добавлен!',
                'review_id' => $pdo->lastInsertId()
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении отзыва: ' . $e->getMessage()]);
        }
        
    } elseif ($action === 'get_reviews') {
        $meal_plan_id = (int)($_POST['meal_plan_id'] ?? 0);
        $page = (int)($_POST['page'] ?? 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;
        
        // Получаем отзывы
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.meal_plan_id = ? AND r.is_approved = TRUE 
            ORDER BY r.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$meal_plan_id, $limit, $offset]);
        $reviews = $stmt->fetchAll();
        
        // Получаем общее количество отзывов
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews WHERE meal_plan_id = ? AND is_approved = TRUE");
        $stmt->execute([$meal_plan_id]);
        $total_reviews = $stmt->fetch()['total'];
        
        // Форматируем отзывы для вывода
        $formatted_reviews = [];
        foreach ($reviews as $review) {
            $formatted_reviews[] = [
                'id' => $review['id'],
                'user_name' => $review['user_name'],
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'created_at' => date('d.m.Y', strtotime($review['created_at'])),
                'stars' => str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'reviews' => $formatted_reviews,
            'has_more' => ($offset + count($reviews)) < $total_reviews,
            'total' => $total_reviews
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверное действие']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}

function updateMealPlanRating($meal_plan_id) {
    global $pdo;
    
    // Вычисляем средний рейтинг и количество отзывов
    $stmt = $pdo->prepare("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as reviews_count 
        FROM reviews 
        WHERE meal_plan_id = ? AND is_approved = TRUE
    ");
    $stmt->execute([$meal_plan_id]);
    $stats = $stmt->fetch();
    
    // Обновляем программу
    $stmt = $pdo->prepare("UPDATE meal_plans SET average_rating = ?, reviews_count = ? WHERE id = ?");
    $stmt->execute([$stats['avg_rating'] ?? 0, $stats['reviews_count'] ?? 0, $meal_plan_id]);
}
?>