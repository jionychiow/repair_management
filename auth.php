<?php
// 为session cookie添加安全属性
ini_set('session.cookie_secure', 1);       // 仅在HTTPS下传输
ini_set('session.cookie_httponly', 1);     // 防止JavaScript访问
ini_set('session.cookie_samesite', 'Strict'); // SameSite保护
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = '用户名和密码不能为空';
        header('Location: login.php');
        exit();
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, password, name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['success'] = '登录成功！';
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['error'] = '用户名或密码错误';
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = '登录失败，请稍后重试';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>