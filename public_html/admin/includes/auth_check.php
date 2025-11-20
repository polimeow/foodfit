<?php
// admin/includes/auth_check.php
function requireAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireAdminRole() {
    if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
        header('Location: index.php');
        exit();
    }
}

function canManageUsers() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_role'] === 'admin';
}

function canManageContent() {
    return isset($_SESSION['admin_id']);
}
?>