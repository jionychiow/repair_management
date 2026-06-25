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

// ========== 数据库类型开关 ==========
// 可选值: 'sqlite' 或 'mysql'
// 设置为 'sqlite' 使用 SQLite 数据库（无需安装 MySQL）
// 设置为 'mysql' 使用 MySQL 数据库
define('DB_TYPE', 'sqlite');

// ========== MySQL 配置（DB_TYPE 为 mysql 时使用） ==========
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'repair_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// ========== SQLite 配置（DB_TYPE 为 sqlite 时使用） ==========
define('DB_SQLITE_PATH', __DIR__ . '/../data/repair_management.db');

// 创建数据库连接
function getDBConnection() {
    try {
        if (DB_TYPE === 'sqlite') {
            // SQLite 连接
            $dbPath = DB_SQLITE_PATH;
            $dbDir = dirname($dbPath);

            // 确保 data 目录存在
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $pdo = new PDO(
                "sqlite:" . $dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // 启用 SQLite 外键约束
            $pdo->exec("PRAGMA foreign_keys = ON;");
            // 设置 WAL 模式提升并发性能
            $pdo->exec("PRAGMA journal_mode = WAL;");

            // 检查表是否存在，如果不存在则自动创建
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='repair_records'");
            if (!$stmt->fetch()) {
                initSQLiteTables($pdo);
            }

            return $pdo;
        } else {
            // MySQL 连接
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
        }
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 初始化 SQLite 数据库表结构
function initSQLiteTables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        name TEXT,
        password TEXT NOT NULL,
        email TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS repair_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        device_number TEXT NOT NULL,
        device_model TEXT NOT NULL,
        device_type TEXT NOT NULL,
        device_belong TEXT NOT NULL,
        section TEXT NOT NULL,
        quantity INTEGER DEFAULT 1,
        fault_description TEXT NOT NULL,
        received_date DATE NOT NULL,
        status TEXT DEFAULT '待维修',
        priority TEXT DEFAULT 'medium',
        assigned_to TEXT,
        notes TEXT,
        completion_time TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 创建索引
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON repair_records(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_updated_at ON repair_records(updated_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_status ON repair_records(status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_device_number ON repair_records(device_number)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_completion_time ON repair_records(completion_time)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_status_date ON repair_records(status, received_date)");

    // 插入默认管理员用户（密码: admin123）
    $pdo->exec("INSERT INTO users (username, name, password, email) VALUES ('admin', '管理员', '\$2y\$10\$71bfxXxG076XCqAQ30/xj.EOjSvWItP0w3RFLtD4iDac.vc8XDGeK', 'admin@example.com')");
}
?>
