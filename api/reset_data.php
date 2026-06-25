<?php
/**
 * 重置数据API
 * 功能：清空维修记录（保留用户账号），需双重认证，自动备份
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once '../config/database.php';
require_once '../auth_check.php';

session_start();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// 双重认证：1. 验证登录密码  2. 验证确认文本
$password = $input['password'] ?? '';
$confirmText = $input['confirm_text'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => '请输入登录密码']);
    exit;
}

if ($confirmText !== '确认重置数据') {
    echo json_encode(['success' => false, 'message' => '确认文本不匹配，请输入"确认重置数据"']);
    exit;
}

// 验证密码
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => '密码错误']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. 备份数据
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $timestamp = date('Ymd_His');
    $backupFile = $backupDir . '/backup_' . $timestamp;

    if (DB_TYPE === 'sqlite') {
        // SQLite: 直接复制数据库文件
        $dbPath = DB_SQLITE_PATH;
        $backupFile .= '.db';
        if (file_exists($dbPath)) {
            copy($dbPath, $backupFile);
        }
    } else {
        // MySQL: 导出维修记录为SQL
        $backupFile .= '.sql';
        $stmt = $pdo->query("SELECT * FROM repair_records");
        $records = $stmt->fetchAll();

        $sqlContent = "-- 备份时间: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- 维修记录数据备份\n\n";

        foreach ($records as $row) {
            $columns = implode(', ', array_map(function($col) use ($pdo) {
                return '`' . $col . '`';
            }, array_keys($row)));
            $values = implode(', ', array_map(function($val) use ($pdo) {
                return $pdo->quote($val);
            }, array_values($row)));
            $sqlContent .= "INSERT INTO repair_records ($columns) VALUES ($values);\n";
        }

        file_put_contents($backupFile, $sqlContent);
    }

    // 2. 清空维修记录表
    $pdo->exec("DELETE FROM repair_records");

    // MySQL 需要重置自增ID
    if (DB_TYPE === 'mysql') {
        $pdo->exec("ALTER TABLE repair_records AUTO_INCREMENT = 1");
    }

    $pdo->commit();

    // 获取备份文件大小
    $backupSize = file_exists($backupFile) ? filesize($backupFile) : 0;
    $backupSizeStr = $backupSize > 1048576
        ? round($backupSize / 1048576, 2) . ' MB'
        : round($backupSize / 1024, 2) . ' KB';

    echo json_encode([
        'success' => true,
        'message' => '数据已重置，维修记录已清空，用户账号保留',
        'backup_file' => basename($backupFile),
        'backup_size' => $backupSizeStr
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => '重置失败: ' . $e->getMessage()
    ]);
}
