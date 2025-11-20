<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

// Только главный администратор может удалять других администраторов
if ($_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$admin_id = $_GET['id'] ?? 0;

if ($admin_id && $admin_id != $_SESSION['admin_id']) {
    $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    
    $_SESSION['admin_message'] = "Администратор успешно удален!";
} else {
    $_SESSION['admin_error'] = "Нельзя удалить самого себя!";
}

header('Location: admins.php');
exit();
?>