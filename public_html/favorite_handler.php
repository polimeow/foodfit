<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if (!$plan_id) {
        echo json_encode(['success' => false, 'message' => 'Неверный ID программы']);
        exit();
    }
    
    // Проверяем существование программы
    $stmt = $pdo->prepare("SELECT id FROM meal_plans WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$plan_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Программа не найдена']);
        exit();
    }
    
    if ($action === 'toggle') {
        if (isFavorite($_SESSION['user_id'], $plan_id)) {
            // Удаляем из избранного
            if (removeFromFavorite($_SESSION['user_id'], $plan_id)) {
                echo json_encode(['success' => true, 'is_favorite' => false]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при удалении']);
            }
        } else {
            // Добавляем в избранное
            if (addToFavorite($_SESSION['user_id'], $plan_id)) {
                echo json_encode(['success' => true, 'is_favorite' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверное действие']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>