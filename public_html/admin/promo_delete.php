<?php
require_once '../config.php';
require_once 'includes/auth_check.php';
requireAdminAuth();

$promo_id = $_GET['id'] ?? 0;

if ($promo_id) {
    // Удаляем историю использований
    $stmt = $pdo->prepare("DELETE FROM used_promo_codes WHERE promo_code_id = ?");
    $stmt->execute([$promo_id]);
    
    // Удаляем промо-код
    $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->execute([$promo_id]);
    
    $_SESSION['admin_message'] = "Промо-код успешно удален!";
}

header('Location: promo_codes.php');
exit();
?>