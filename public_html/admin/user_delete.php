<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

if (!canManageUsers()) {
    header('Location: index.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;

if ($user_id) {
    try {
        $pdo->beginTransaction();
        
        // Удаляем заказы пользователя
        $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Удаляем пользователя
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        $_SESSION['admin_message'] = "Пользователь и все его заказы успешно удалены!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_error'] = "Ошибка при удалении пользователя: " . $e->getMessage();
    }
}

header('Location: users.php');
exit();
?>