<?php
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 安全头部设置
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 移除X-Powered-By头部
header_remove('X-Powered-By');

// 数据库配置
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'repair_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// 创建数据库连接
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}
?>
