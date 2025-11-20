<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

$id = $_GET['id'] ?? 0;

if ($id) {
    // В реальном приложении лучше делать мягкое удаление (is_active = FALSE)
    $stmt = $pdo->prepare("DELETE FROM meal_plans WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: meal_plans.php');
exit();
?>