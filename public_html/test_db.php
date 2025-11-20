<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $result = $stmt->fetch();
    echo "Успешное подключение к БД! Найдено таблиц: " . $result['table_count'];
} catch(PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>