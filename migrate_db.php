<?php
/**
 * 数据库双向迁移工具
 * 支持 MySQL ↔ SQLite 互转
 */
session_start();

$message = '';
$messageType = '';
$migrationResult = null;

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'test_mysql') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'repair_management');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';

        try {
            $mysqlPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            $stmt = $mysqlPdo->query("SELECT COUNT(*) as cnt FROM repair_records");
            $recordCount = $stmt->fetch()['cnt'];
            $stmt = $mysqlPdo->query("SELECT COUNT(*) as cnt FROM users");
            $userCount = $stmt->fetch()['cnt'];
            $message = "MySQL 连接成功！发现 {$userCount} 个用户，{$recordCount} 条维修记录。";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'MySQL 连接失败：' . $e->getMessage();
            $messageType = 'danger';
        }

    } elseif ($action === 'test_sqlite') {
        $sqlitePath = __DIR__ . '/data/repair_management.db';
        if (!file_exists($sqlitePath)) {
            $message = 'SQLite 数据库文件不存在：' . $sqlitePath;
            $messageType = 'danger';
        } else {
            try {
                $sqlitePdo = new PDO("sqlite:" . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $stmt = $sqlitePdo->query("SELECT COUNT(*) as cnt FROM repair_records");
                $recordCount = $stmt->fetch()['cnt'];
                $stmt = $sqlitePdo->query("SELECT COUNT(*) as cnt FROM users");
                $userCount = $stmt->fetch()['cnt'];
                $message = "SQLite 连接成功！发现 {$userCount} 个用户，{$recordCount} 条维修记录。";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'SQLite 连接失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }

    } elseif ($action === 'migrate_mysql_to_sqlite') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'repair_management');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';

        try {
            set_time_limit(300);

            $mysqlPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]
            );

            $sqlitePath = __DIR__ . '/data/repair_management.db';
            $sqliteDir = dirname($sqlitePath);
            if (!is_dir($sqliteDir)) mkdir($sqliteDir, 0755, true);

            if (file_exists($sqlitePath)) {
                $backupPath = $sqlitePath . '.backup.' . date('Ymd_His');
                copy($sqlitePath, $backupPath);
                unlink($sqlitePath);
            }

            $sqlitePdo = new PDO("sqlite:" . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $sqlitePdo->exec("PRAGMA foreign_keys = ON;");
            $sqlitePdo->exec("PRAGMA journal_mode = WAL;");

            $sqlitePdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                name TEXT,
                password TEXT NOT NULL,
                email TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $sqlitePdo->exec("CREATE TABLE IF NOT EXISTS repair_records (
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
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON repair_records(created_at)");
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_updated_at ON repair_records(updated_at)");
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_status ON repair_records(status)");
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_device_number ON repair_records(device_number)");
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_completion_time ON repair_records(completion_time)");
            $sqlitePdo->exec("CREATE INDEX IF NOT EXISTS idx_status_date ON repair_records(status, received_date)");

            $userCount = 0;
            $stmt = $mysqlPdo->query("SELECT * FROM users ORDER BY id");
            $users = $stmt->fetchAll();
            $insertUser = $sqlitePdo->prepare("INSERT INTO users (id, username, name, password, email, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($users as $user) {
                $insertUser->execute([$user['id'], $user['username'], $user['name'] ?? '', $user['password'], $user['email'] ?? '', $user['created_at'] ?? date('Y-m-d H:i:s')]);
                $userCount++;
            }

            $recordCount = 0;
            $stmt = $mysqlPdo->query("SELECT * FROM repair_records ORDER BY id");
            $records = $stmt->fetchAll();
            $insertRecord = $sqlitePdo->prepare("INSERT INTO repair_records (id, device_number, device_model, device_type, device_belong, section, quantity, fault_description, received_date, status, priority, assigned_to, notes, completion_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($records as $record) {
                $insertRecord->execute([
                    $record['id'], $record['device_number'], $record['device_model'],
                    $record['device_type'], $record['device_belong'], $record['section'],
                    $record['quantity'] ?? 1, $record['fault_description'], $record['received_date'],
                    $record['status'], $record['priority'], $record['assigned_to'] ?? '',
                    $record['notes'] ?? '', $record['completion_time'] ?? null,
                    $record['created_at'] ?? date('Y-m-d H:i:s'), $record['updated_at'] ?? date('Y-m-d H:i:s')
                ]);
                $recordCount++;
            }

            if ($recordCount > 0) {
                $maxId = $records[count($records) - 1]['id'];
                $sqlitePdo->exec("DELETE FROM sqlite_sequence WHERE name='repair_records'");
                $sqlitePdo->exec("INSERT INTO sqlite_sequence (name, seq) VALUES ('repair_records', {$maxId})");
            }
            if ($userCount > 0) {
                $maxUserId = $users[count($users) - 1]['id'];
                $sqlitePdo->exec("DELETE FROM sqlite_sequence WHERE name='users'");
                $sqlitePdo->exec("INSERT INTO sqlite_sequence (name, seq) VALUES ('users', {$maxUserId})");
            }

            $migrationResult = ['direction' => 'mysql_to_sqlite', 'users' => $userCount, 'records' => $recordCount, 'file_size' => filesize($sqlitePath)];
            $message = "MySQL → SQLite 迁移成功！共迁移 {$userCount} 个用户和 {$recordCount} 条维修记录。";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'MySQL → SQLite 迁移失败：' . $e->getMessage();
            $messageType = 'danger';
        }

    } elseif ($action === 'migrate_sqlite_to_mysql') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'repair_management');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';

        try {
            set_time_limit(300);

            $sqlitePath = __DIR__ . '/data/repair_management.db';
            if (!file_exists($sqlitePath)) {
                throw new Exception('SQLite 数据库文件不存在：' . $sqlitePath);
            }

            $sqlitePdo = new PDO("sqlite:" . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

            $mysqlPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 5]
            );

            // 确保 MySQL 表存在
            $mysqlPdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100),
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $mysqlPdo->exec("CREATE TABLE IF NOT EXISTS repair_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_number VARCHAR(50) NOT NULL,
                device_model VARCHAR(100) NOT NULL,
                device_type VARCHAR(50) NOT NULL,
                device_belong VARCHAR(100) NOT NULL,
                section VARCHAR(100) NOT NULL,
                quantity INT DEFAULT 1,
                fault_description TEXT NOT NULL,
                received_date DATE NOT NULL,
                status VARCHAR(20) DEFAULT '待维修',
                priority VARCHAR(10) DEFAULT 'medium',
                assigned_to VARCHAR(100),
                notes TEXT,
                completion_time TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_device_number (device_number),
                INDEX idx_created_at (created_at),
                INDEX idx_completion_time (completion_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 清空 MySQL 表（先禁用外键检查）
            $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $mysqlPdo->exec("TRUNCATE TABLE repair_records");
            $mysqlPdo->exec("TRUNCATE TABLE users");
            $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // 迁移 users
            $userCount = 0;
            $stmt = $sqlitePdo->query("SELECT * FROM users ORDER BY id");
            $users = $stmt->fetchAll();
            $insertUser = $mysqlPdo->prepare("INSERT INTO users (id, username, name, password, email, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($users as $user) {
                $insertUser->execute([$user['id'], $user['username'], $user['name'] ?? '', $user['password'], $user['email'] ?? '', $user['created_at'] ?? date('Y-m-d H:i:s')]);
                $userCount++;
            }

            // 迁移 repair_records
            $recordCount = 0;
            $stmt = $sqlitePdo->query("SELECT * FROM repair_records ORDER BY id");
            $records = $stmt->fetchAll();
            $insertRecord = $mysqlPdo->prepare("INSERT INTO repair_records (id, device_number, device_model, device_type, device_belong, section, quantity, fault_description, received_date, status, priority, assigned_to, notes, completion_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($records as $record) {
                $insertRecord->execute([
                    $record['id'], $record['device_number'], $record['device_model'],
                    $record['device_type'], $record['device_belong'], $record['section'],
                    $record['quantity'] ?? 1, $record['fault_description'], $record['received_date'],
                    $record['status'], $record['priority'], $record['assigned_to'] ?? '',
                    $record['notes'] ?? '', $record['completion_time'] ?? null,
                    $record['created_at'] ?? date('Y-m-d H:i:s'), $record['updated_at'] ?? date('Y-m-d H:i:s')
                ]);
                $recordCount++;
            }

            // 更新 MySQL 自增ID
            if ($recordCount > 0) {
                $maxId = $records[count($records) - 1]['id'];
                $mysqlPdo->exec("ALTER TABLE repair_records AUTO_INCREMENT = " . ($maxId + 1));
            }
            if ($userCount > 0) {
                $maxUserId = $users[count($users) - 1]['id'];
                $mysqlPdo->exec("ALTER TABLE users AUTO_INCREMENT = " . ($maxUserId + 1));
            }

            $migrationResult = ['direction' => 'sqlite_to_mysql', 'users' => $userCount, 'records' => $recordCount];
            $message = "SQLite → MySQL 迁移成功！共迁移 {$userCount} 个用户和 {$recordCount} 条维修记录。";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'SQLite → MySQL 迁移失败：' . $e->getMessage();
            $messageType = 'danger';
        }

    } elseif ($action === 'switch_to_sqlite') {
        $configFile = __DIR__ . '/config/database.php';
        $content = file_get_contents($configFile);
        $content = preg_replace("/define\('DB_TYPE',\s*'mysql'\)/", "define('DB_TYPE', 'sqlite')", $content);
        file_put_contents($configFile, $content);
        $message = '已切换为 SQLite 数据库！请刷新页面查看效果。';
        $messageType = 'success';

    } elseif ($action === 'switch_to_mysql') {
        $configFile = __DIR__ . '/config/database.php';
        $content = file_get_contents($configFile);
        $content = preg_replace("/define\('DB_TYPE',\s*'sqlite'\)/", "define('DB_TYPE', 'mysql')", $content);
        file_put_contents($configFile, $content);
        $message = '已切换为 MySQL 数据库！请刷新页面查看效果。';
        $messageType = 'success';
    }
}

// 读取当前数据库类型
$currentDbType = 'unknown';
$configFile = __DIR__ . '/config/database.php';
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    if (preg_match("/define\('DB_TYPE',\s*'(\w+)'\)/", $content, $matches)) {
        $currentDbType = $matches[1];
    }
}

// 读取 MySQL 配置
$defaultHost = '127.0.0.1';
$defaultPort = '3306';
$defaultName = 'repair_management';
$defaultUser = 'root';
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $m)) $defaultHost = $m[1];
    if (preg_match("/define\('DB_PORT',\s*'([^']+)'\)/", $content, $m)) $defaultPort = $m[1];
    if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $m)) $defaultName = $m[1];
    if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $m)) $defaultUser = $m[1];
}

$sqliteExists = file_exists(__DIR__ . '/data/repair_management.db');
$sqliteSize = $sqliteExists ? filesize(__DIR__ . '/data/repair_management.db') : 0;

// 读取 SQLite 中的数据统计
$sqliteRecordCount = 0;
$sqliteUserCount = 0;
if ($sqliteExists) {
    try {
        $tmpPdo = new PDO("sqlite:" . __DIR__ . '/data/repair_management.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $tmpPdo->query("SELECT COUNT(*) as cnt FROM repair_records");
        $sqliteRecordCount = $stmt->fetch()['cnt'];
        $stmt = $tmpPdo->query("SELECT COUNT(*) as cnt FROM users");
        $sqliteUserCount = $stmt->fetch()['cnt'];
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库迁移工具 - 维修管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/themes.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .step-number {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 50%;
            background-color: #0d6efd; color: white; font-weight: bold; margin-right: 10px;
        }
        .step-card { transition: all 0.3s ease; }
        .step-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .current-db-badge { font-size: 1.2rem; padding: 0.5rem 1rem; }
        .direction-btn { transition: all 0.2s; }
        .direction-btn.active { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .arrow-icon { font-size: 1.5rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-tools"></i> 维修管理系统</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="bi bi-house"></i> 返回首页</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-arrow-left-right"></i> 数据库双向迁移工具（MySQL ↔ SQLite）</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- 当前状态 -->
                        <div class="card mb-4 border-info">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-info-circle"></i> 当前数据库状态
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>当前使用数据库：</strong>
                                            <span class="badge current-db-badge bg-<?php echo $currentDbType === 'sqlite' ? 'success' : 'primary'; ?>">
                                                <?php echo strtoupper($currentDbType); ?>
                                            </span>
                                        </p>
                                        <p><strong>SQLite 数据库：</strong>
                                            <?php if ($sqliteExists): ?>
                                                <span class="badge bg-success">已存在</span>
                                                <small class="text-muted">(<?php echo number_format($sqliteSize / 1024, 2); ?> KB, <?php echo $sqliteUserCount; ?> 用户, <?php echo $sqliteRecordCount; ?> 记录)</small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">未创建</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>配置文件路径：</strong><code>config/database.php</code></p>
                                        <p><strong>SQLite 数据路径：</strong><code>data/repair_management.db</code></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($migrationResult): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-check-circle"></i> 迁移结果
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <h3 class="text-primary"><?php echo $migrationResult['users']; ?></h3>
                                        <p>用户数据</p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h3 class="text-success"><?php echo $migrationResult['records']; ?></h3>
                                        <p>维修记录</p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <?php if (isset($migrationResult['file_size'])): ?>
                                            <h3 class="text-info"><?php echo number_format($migrationResult['file_size'] / 1024, 2); ?> KB</h3>
                                            <p>SQLite 文件大小</p>
                                        <?php else: ?>
                                            <h3 class="text-info"><?php echo strtoupper($migrationResult['direction'] === 'mysql_to_sqlite' ? 'MySQL → SQLite' : 'SQLite → MySQL'); ?></h3>
                                            <p>迁移方向</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 步骤1：选择迁移方向 -->
                        <div class="card step-card mb-4">
                            <div class="card-header">
                                <span class="step-number">1</span>
                                <strong>选择迁移方向</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <button type="button" class="btn btn-outline-primary w-100 py-3 direction-btn <?php echo (!isset($_POST['direction']) || ($_POST['direction'] ?? '') !== 'sqlite_to_mysql') ? 'active' : ''; ?>" id="dirMysqlToSqlite" onclick="selectDirection('mysql_to_sqlite')">
                                            <span class="arrow-icon"><i class="bi bi-database"></i> MySQL</span>
                                            <i class="bi bi-arrow-right mx-2"></i>
                                            <span class="arrow-icon"><i class="bi bi-file-earmark"></i> SQLite</span>
                                            <div class="mt-1"><small>将 MySQL 数据导出到 SQLite</small></div>
                                        </button>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <button type="button" class="btn btn-outline-success w-100 py-3 direction-btn <?php echo (($_POST['direction'] ?? '') === 'sqlite_to_mysql') ? 'active' : ''; ?>" id="dirSqliteToMysql" onclick="selectDirection('sqlite_to_mysql')">
                                            <span class="arrow-icon"><i class="bi bi-file-earmark"></i> SQLite</span>
                                            <i class="bi bi-arrow-right mx-2"></i>
                                            <span class="arrow-icon"><i class="bi bi-database"></i> MySQL</span>
                                            <div class="mt-1"><small>将 SQLite 数据导出到 MySQL</small></div>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="migrationDirection" value="mysql_to_sqlite">
                            </div>
                        </div>

                        <!-- 步骤2：MySQL 连接配置 -->
                        <div class="card step-card mb-4" id="mysqlConfigCard">
                            <div class="card-header">
                                <span class="step-number">2</span>
                                <strong>MySQL 连接配置</strong>
                                <span class="text-danger" id="mysqlRequiredHint">（必须先测试连接成功再迁移）</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">数据库主机</label>
                                        <input type="text" class="form-control" id="db_host" value="<?php echo htmlspecialchars($defaultHost); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">端口</label>
                                        <input type="text" class="form-control" id="db_port" value="<?php echo htmlspecialchars($defaultPort); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">数据库名称</label>
                                        <input type="text" class="form-control" id="db_name" value="<?php echo htmlspecialchars($defaultName); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">用户名</label>
                                        <input type="text" class="form-control" id="db_user" value="<?php echo htmlspecialchars($defaultUser); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">密码</label>
                                        <input type="password" class="form-control" id="db_pass" placeholder="MySQL 密码">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-lg" id="testMysqlBtn" onclick="testMysqlConnection()">
                                        <i class="bi bi-wifi"></i> 测试 MySQL 连接
                                    </button>
                                </div>
                                <div id="testMysqlResult" class="mt-3" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- 步骤2b：SQLite 检测（SQLite→MySQL 方向时显示） -->
                        <div class="card step-card mb-4" id="sqliteCheckCard" style="display:none;">
                            <div class="card-header">
                                <span class="step-number">2</span>
                                <strong>SQLite 数据源检测</strong>
                            </div>
                            <div class="card-body">
                                <p>检测当前 SQLite 数据库文件中的数据：</p>
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-outline-success btn-lg" id="testSqliteBtn" onclick="testSqliteConnection()">
                                        <i class="bi bi-wifi"></i> 检测 SQLite 数据
                                    </button>
                                </div>
                                <div id="testSqliteResult" class="mt-3" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- 步骤3：执行迁移 -->
                        <div class="card step-card mb-4">
                            <div class="card-header">
                                <span class="step-number" id="step3Number">3</span>
                                <strong>执行数据迁移</strong>
                            </div>
                            <div class="card-body">
                                <p id="migrateDescription">测试连接成功后，点击下方按钮将 MySQL 数据迁移到 SQLite。</p>
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-primary btn-lg" id="migrateBtn" onclick="startMigrate()" disabled>
                                        <i class="bi bi-arrow-right-circle"></i> 开始迁移
                                    </button>
                                </div>
                                <div id="migrateResult" class="mt-3" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- 步骤4：切换数据库 -->
                        <div class="card step-card mb-4">
                            <div class="card-header">
                                <span class="step-number">4</span>
                                <strong>切换数据库类型</strong>
                            </div>
                            <div class="card-body">
                                <p>迁移完成后，切换数据库类型使系统使用目标数据库：</p>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <form method="POST" class="d-inline w-100">
                                            <input type="hidden" name="action" value="switch_to_sqlite">
                                            <button type="submit" class="btn btn-success w-100 <?php echo $currentDbType === 'sqlite' ? 'disabled' : ''; ?>" <?php echo $currentDbType === 'sqlite' ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-circle"></i>
                                                <?php echo $currentDbType === 'sqlite' ? '当前使用 SQLite' : '切换到 SQLite'; ?>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="POST" class="d-inline w-100">
                                            <input type="hidden" name="action" value="switch_to_mysql">
                                            <button type="submit" class="btn btn-outline-primary w-100 <?php echo $currentDbType === 'mysql' ? 'disabled' : ''; ?>" <?php echo $currentDbType === 'mysql' ? 'disabled' : ''; ?>>
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <?php echo $currentDbType === 'mysql' ? '当前使用 MySQL' : '切换到 MySQL'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 注意事项 -->
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle"></i> 注意事项
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>迁移会<strong>清空目标数据库中的同名表</strong>再写入，请确认目标库无重要数据</li>
                                    <li>MySQL → SQLite 时，如 SQLite 文件已存在会自动备份旧文件</li>
                                    <li>SQLite → MySQL 时，需要 MySQL 数据库已创建且表结构会自动建立</li>
                                    <li>迁移完成后，请在步骤4切换 <code>DB_TYPE</code> 使系统使用目标数据库</li>
                                    <li>如需回退，只需再次执行反向迁移并切换 <code>DB_TYPE</code></li>
                                    <li>迁移完成后建议删除此文件 <code>migrate_db.php</code> 以提高安全性</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme-switcher.js"></script>
    <script>
    let mysqlOk = false;
    let sqliteOk = false;
    let currentDirection = 'mysql_to_sqlite';

    function selectDirection(direction) {
        currentDirection = direction;
        document.getElementById('migrationDirection').value = direction;

        // 更新按钮样式
        document.getElementById('dirMysqlToSqlite').classList.toggle('active', direction === 'mysql_to_sqlite');
        document.getElementById('dirSqliteToMysql').classList.toggle('active', direction === 'sqlite_to_mysql');

        if (direction === 'mysql_to_sqlite') {
            document.getElementById('dirMysqlToSqlite').classList.add('btn-primary');
            document.getElementById('dirMysqlToSqlite').classList.remove('btn-outline-primary');
            document.getElementById('dirSqliteToMysql').classList.remove('btn-success');
            document.getElementById('dirSqliteToMysql').classList.add('btn-outline-success');

            document.getElementById('mysqlConfigCard').style.display = '';
            document.getElementById('sqliteCheckCard').style.display = 'none';
            document.getElementById('mysqlRequiredHint').textContent = '（必须先测试连接成功再迁移）';
            document.getElementById('migrateDescription').textContent = '测试 MySQL 连接成功后，点击下方按钮将 MySQL 数据迁移到 SQLite。';
            document.getElementById('migrateBtn').innerHTML = '<i class="bi bi-arrow-right-circle"></i> MySQL → SQLite 开始迁移';
        } else {
            document.getElementById('dirSqliteToMysql').classList.add('btn-success');
            document.getElementById('dirSqliteToMysql').classList.remove('btn-outline-success');
            document.getElementById('dirMysqlToSqlite').classList.remove('btn-primary');
            document.getElementById('dirMysqlToSqlite').classList.add('btn-outline-primary');

            document.getElementById('mysqlConfigCard').style.display = '';
            document.getElementById('sqliteCheckCard').style.display = '';
            document.getElementById('mysqlRequiredHint').textContent = '（MySQL 和 SQLite 均需测试成功）';
            document.getElementById('migrateDescription').textContent = '测试 MySQL 和 SQLite 均成功后，点击下方按钮将 SQLite 数据迁移到 MySQL。';
            document.getElementById('migrateBtn').innerHTML = '<i class="bi bi-arrow-right-circle"></i> SQLite → MySQL 开始迁移';
        }

        updateMigrateButton();
    }

    function updateMigrateButton() {
        if (currentDirection === 'mysql_to_sqlite') {
            document.getElementById('migrateBtn').disabled = !mysqlOk;
        } else {
            document.getElementById('migrateBtn').disabled = !(mysqlOk && sqliteOk);
        }
    }

    function getMySQLParams() {
        return {
            db_host: document.getElementById('db_host').value,
            db_port: document.getElementById('db_port').value,
            db_name: document.getElementById('db_name').value,
            db_user: document.getElementById('db_user').value,
            db_pass: document.getElementById('db_pass').value
        };
    }

    function testMysqlConnection() {
        const btn = document.getElementById('testMysqlBtn');
        const resultDiv = document.getElementById('testMysqlResult');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 连接中...';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-info">正在连接 MySQL，请稍候...</div>';

        const params = getMySQLParams();
        params.action = 'test_mysql';

        const formData = new FormData();
        for (const key in params) formData.append(key, params[key]);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        fetch('migrate_db.php', { method: 'POST', body: formData, signal: controller.signal })
        .then(response => response.text())
        .then(html => {
            clearTimeout(timeoutId);
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const alertDiv = doc.querySelector('.alert');
            if (alertDiv) {
                const isSuccess = alertDiv.classList.contains('alert-success');
                resultDiv.innerHTML = '<div class="alert alert-' + (isSuccess ? 'success' : 'danger') + '">' + alertDiv.innerHTML + '</div>';
                mysqlOk = isSuccess;
                updateMigrateButton();
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi"></i> 测试 MySQL 连接';
        })
        .catch(error => {
            clearTimeout(timeoutId);
            resultDiv.innerHTML = '<div class="alert alert-danger">连接失败：' + (error.name === 'AbortError' ? '连接超时' : error.message) + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi"></i> 测试 MySQL 连接';
        });
    }

    function testSqliteConnection() {
        const btn = document.getElementById('testSqliteBtn');
        const resultDiv = document.getElementById('testSqliteResult');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 检测中...';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-info">正在检测 SQLite 数据...</div>';

        const formData = new FormData();
        formData.append('action', 'test_sqlite');

        fetch('migrate_db.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const alertDiv = doc.querySelector('.alert');
            if (alertDiv) {
                const isSuccess = alertDiv.classList.contains('alert-success');
                resultDiv.innerHTML = '<div class="alert alert-' + (isSuccess ? 'success' : 'danger') + '">' + alertDiv.innerHTML + '</div>';
                sqliteOk = isSuccess;
                updateMigrateButton();
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi"></i> 检测 SQLite 数据';
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="alert alert-danger">检测失败：' + error.message + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi"></i> 检测 SQLite 数据';
        });
    }

    function startMigrate() {
        const isM2S = currentDirection === 'mysql_to_sqlite';
        if (isM2S && !mysqlOk) {
            alert('请先测试 MySQL 连接成功后再执行迁移！');
            return;
        }
        if (!isM2S && (!mysqlOk || !sqliteOk)) {
            alert('请先测试 MySQL 和 SQLite 均成功后再执行迁移！');
            return;
        }

        const btn = document.getElementById('migrateBtn');
        const resultDiv = document.getElementById('migrateResult');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 迁移中...';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-info">正在迁移数据，请稍候...</div>';

        const params = getMySQLParams();
        params.action = isM2S ? 'migrate_mysql_to_sqlite' : 'migrate_sqlite_to_mysql';

        const formData = new FormData();
        for (const key in params) formData.append(key, params[key]);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 60000);

        fetch('migrate_db.php', { method: 'POST', body: formData, signal: controller.signal })
        .then(response => response.text())
        .then(html => {
            clearTimeout(timeoutId);
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const alertDiv = doc.querySelector('.alert');
            if (alertDiv) {
                const isSuccess = alertDiv.classList.contains('alert-success');
                resultDiv.innerHTML = '<div class="alert alert-' + (isSuccess ? 'success' : 'danger') + '">' + alertDiv.innerHTML + '</div>';
                if (isSuccess) {
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> 迁移完成';
                    setTimeout(() => location.reload(), 3000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = isM2S ? '<i class="bi bi-arrow-right-circle"></i> MySQL → SQLite 开始迁移' : '<i class="bi bi-arrow-right-circle"></i> SQLite → MySQL 开始迁移';
                }
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning">未收到有效响应。</div>';
                btn.disabled = false;
                btn.innerHTML = isM2S ? '<i class="bi bi-arrow-right-circle"></i> MySQL → SQLite 开始迁移' : '<i class="bi bi-arrow-right-circle"></i> SQLite → MySQL 开始迁移';
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                resultDiv.innerHTML = '<div class="alert alert-danger">迁移超时！请检查服务是否正常。</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">请求失败：' + error.message + '</div>';
            }
            btn.disabled = false;
            btn.innerHTML = isM2S ? '<i class="bi bi-arrow-right-circle"></i> MySQL → SQLite 开始迁移' : '<i class="bi bi-arrow-right-circle"></i> SQLite → MySQL 开始迁移';
        });
    }

    // 初始化方向
    selectDirection('mysql_to_sqlite');
    </script>
</body>
</html>
