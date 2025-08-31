<?php
// 认证检查文件
// 用于检查用户是否已登录
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        // 用户未登录，返回错误信息
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'message' => '需要登录才能执行此操作',
            'redirect' => 'login.php'
        ]);
        exit();
    }
}
?>