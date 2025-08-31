<?php
session_start();

// 如果已经安装过，跳转到首页
if (file_exists('config/installed.lock')) {
    header('Location: index.php');
    exit();
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// 处理安装步骤
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // 检查环境要求
        $requirements = checkRequirements();
        if ($requirements['passed']) {
            $step = 2;
        } else {
            $error = '环境检查未通过，请解决上述问题后继续';
        }
    } elseif ($step == 2) {
        // 配置数据库
        $dbConfig = [
            'host' => $_POST['db_host'] ?? 'localhost',
            'name' => $_POST['db_name'] ?? 'repair_management',
            'user' => $_POST['db_user'] ?? 'root',
            'pass' => $_POST['db_pass'] ?? '',
            'port' => $_POST['db_port'] ?? '3306'
        ];
        
        // 验证必填字段
        if (empty($dbConfig['host']) || empty($dbConfig['user'])) {
            $error = '主机地址和用户名不能为空';
        } else {
            $connectionResult = testDatabaseConnection($dbConfig);
            if ($connectionResult === true) {
                $_SESSION['db_config'] = $dbConfig;
                $step = 3;
            } else {
                $error = '数据库连接失败，请检查配置信息。错误详情：' . $connectionResult;
            }
        }
    } elseif ($step == 3) {
        // 安装系统
        if (installSystem($_SESSION['db_config'])) {
            $step = 4;
        } else {
            $error = '系统安装失败，请检查错误信息';
        }
    }
}

// 检查环境要求
function checkRequirements() {
    $requirements = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'mbstring' => extension_loaded('mbstring'),
        'writable' => is_writable('.')
    ];
    
    $passed = array_reduce($requirements, function($carry, $item) {
        return $carry && $item;
    }, true);
    
    return ['passed' => $passed, 'details' => $requirements];
}

// 测试数据库连接
function testDatabaseConnection($config) {
    try {
        // 先尝试连接到MySQL服务器（不指定数据库）
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 测试连接是否有效
        $pdo->query('SELECT 1');
        
        return true;
    } catch (PDOException $e) {
        // 记录错误信息到日志
        error_log("数据库连接测试失败: " . $e->getMessage());
        return $e->getMessage();
    }
}

// 安装系统
function installSystem($dbConfig) {
    try {
        // 创建数据库配置文件
        $configContent = "<?php
// 数据库配置
define('DB_HOST', '{$dbConfig['host']}');
define('DB_PORT', '{$dbConfig['port']}');
define('DB_NAME', '{$dbConfig['name']}');
define('DB_USER', '{$dbConfig['user']}');
define('DB_PASS', '{$dbConfig['pass']}');

// 创建数据库连接
function getDBConnection() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return \$pdo;
    } catch (PDOException \$e) {
        die(\"数据库连接失败: \" . \$e->getMessage());
    }
}

function initDatabase() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";charset=utf8mb4\",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \" . DB_NAME . \" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\");
        \$pdo->exec(\"USE \" . DB_NAME);
        
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )\");
        
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS repair_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            device_number VARCHAR(100) NOT NULL,
            device_model VARCHAR(100) NOT NULL,
            device_type VARCHAR(50) NOT NULL,
            quantity INT DEFAULT 1,
            fault_description TEXT NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            customer_address TEXT,
            received_date DATE NOT NULL,
            status ENUM('待维修', '未维修', '检修中', '已维修', '报废') DEFAULT '待维修',
            priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
            assigned_to VARCHAR(100),
            notes TEXT,
            completion_time TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_updated_at (updated_at),
            INDEX idx_status (status),
            INDEX idx_device_number (device_number),
            INDEX idx_customer_name (customer_name),
            INDEX idx_completion_time (completion_time)
        )\");
        
        \$stmt = \$pdo->prepare(\"SELECT COUNT(*) FROM users WHERE username = 'admin'\");
        \$stmt->execute();
        if (\$stmt->fetchColumn() == 0) {
            \$hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            \$stmt = \$pdo->prepare(\"INSERT INTO users (username, password, email) VALUES (?, ?, ?)\");
            \$stmt->execute(['admin', \$hashedPassword, 'admin@example.com']);
        }
        
        \$stmt = \$pdo->prepare(\"SELECT COUNT(*) FROM repair_records\");
        \$stmt->execute();
        if (\$stmt->fetchColumn() == 0) {
            \$sampleData = [
                ['DEV001', 'ThinkPad X1 Carbon', '电脑', 1, '无法开机，电源指示灯不亮', '张三', '13800138001', '北京市朝阳区', '2024-01-15', '已维修', 'high', '李师傅', '已更换电源适配器', '2024-01-16 14:30:00'],
                ['DEV002', 'iPhone 14 Pro', '手机', 2, '屏幕碎裂，触摸失灵', '李四', '13800138002', '上海市浦东新区', '2024-01-16', '检修中', 'high', '王师傅', '等待屏幕配件', NULL],
                ['DEV003', 'HP LaserJet Pro', '打印机', 1, '打印质量差，有黑线', '王五', '13800138003', '广州市天河区', '2024-01-17', '待维修', 'medium', '赵师傅', '', NULL],
                ['DEV004', 'iPad Air', '平板', 1, '充电口松动，无法充电', '赵六', '13800138004', '深圳市南山区', '2024-01-18', '报废', 'low', '孙师傅', '主板损坏，无法修复', '2024-01-19 10:15:00']
            ];
            
            \$stmt = \$pdo->prepare(\"INSERT INTO repair_records (device_number, device_model, device_type, quantity, fault_description, customer_name, customer_phone, customer_address, received_date, status, priority, assigned_to, notes, completion_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\");
            
            foreach (\$sampleData as \$data) {
                \$stmt->execute(\$data);
            }
        }
        
        return true;
    } catch (PDOException \$e) {
        die(\"数据库初始化失败: \" . \$e->getMessage());
    }
}
?>";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        if (file_put_contents('config/database.php', $configContent) === false) {
            throw new Exception('无法创建数据库配置文件');
        }
        
        require_once 'config/database.php';
        if (!initDatabase()) {
            throw new Exception('数据库初始化失败');
        }
        
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>维修管理系统 - 安装向导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .install-step { display: none; }
        .install-step.active { display: block; }
        .step-indicator { margin-bottom: 2rem; }
        .step-indicator .step { 
            display: inline-block; 
            width: 40px; 
            height: 40px; 
            line-height: 40px; 
            border-radius: 50%; 
            text-align: center; 
            margin: 0 10px; 
            background-color: #e9ecef; 
            color: #6c757d; 
        }
        .step-indicator .step.active { 
            background-color: #007bff; 
            color: white; 
        }
        .step-indicator .step.completed { 
            background-color: #28a745; 
            color: white; 
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-tools"></i> 维修管理系统安装向导
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="step-indicator text-center">
                            <span class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</span>
                            <span class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</span>
                            <span class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</span>
                            <span class="step <?php echo $step >= 4 ? 'completed' : ''; ?>">4</span>
                        </div>

                                                 <?php if ($error): ?>
                             <div class="alert alert-danger">
                                 <h6><i class="bi bi-exclamation-triangle"></i> 错误信息</h6>
                                 <p class="mb-0"><?php echo $error; ?></p>
                                 <hr>
                                 <small class="text-muted">
                                     <strong>常见问题解决方案：</strong><br>
                                     • 检查MySQL服务是否启动<br>
                                     • 确认主机地址和端口号正确<br>
                                     • 验证用户名和密码<br>
                                     • 检查防火墙设置<br>
                                     • 使用"仅测试连接"按钮进行诊断
                                 </small>
                             </div>
                         <?php endif; ?>

                        <!-- 步骤1：环境检查 -->
                        <div class="install-step <?php echo $step == 1 ? 'active' : ''; ?>">
                            <h5>步骤 1：环境检查</h5>
                            <?php $requirements = checkRequirements(); ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>检查项目</th>
                                            <th>要求</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PHP版本</td>
                                            <td>7.4.0 或更高</td>
                                            <td>
                                                <?php if ($requirements['details']['php_version']): ?>
                                                    <span class="badge bg-success">通过</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">失败 (<?php echo PHP_VERSION; ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>PDO MySQL扩展</td>
                                            <td>已安装</td>
                                            <td>
                                                <?php if ($requirements['details']['pdo_mysql']): ?>
                                                    <span class="badge bg-success">通过</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">失败</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>JSON扩展</td>
                                            <td>已安装</td>
                                            <td>
                                                <?php if ($requirements['details']['json']): ?>
                                                    <span class="badge bg-success">通过</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">失败</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>MBString扩展</td>
                                            <td>已安装</td>
                                            <td>
                                                <?php if ($requirements['details']['mbstring']): ?>
                                                    <span class="badge bg-success">通过</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">失败</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>目录写入权限</td>
                                            <td>可写</td>
                                            <td>
                                                <?php if ($requirements['details']['writable']): ?>
                                                    <span class="badge bg-success">通过</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">失败</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($requirements['passed']): ?>
                                <form method="POST" class="text-center">
                                    <button type="submit" class="btn btn-primary">
                                        下一步 <i class="bi bi-arrow-right"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <strong>请解决上述问题后继续安装</strong><br>
                                    如果问题无法解决，请联系系统管理员。
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 步骤2：数据库配置 -->
<div class="install-step <?php echo $step == 2 ? 'active' : ''; ?>">
    <h5>步骤 2：数据库配置</h5>
    <form method="POST">
        <!-- 添加隐藏字段来标识当前步骤 -->
        <input type="hidden" name="step" value="2">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="db_host" class="form-label">数据库主机</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="127.0.0.1" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="db_port" class="form-label">端口</label>
                    <input type="number" class="form-control" id="db_port" name="db_port" value="3306" required>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="db_name" class="form-label">数据库名称</label>
            <input type="text" class="form-control" id="db_name" name="db_name" value="repair_management" required>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="db_user" class="form-label">用户名</label>
                    <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="db_pass" class="form-label">密码</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                </div>
            </div>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">
                测试连接并继续 <i class="bi bi-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-outline-info ms-2" onclick="testConnection()">
                <i class="bi bi-info-circle"></i> 仅测试连接
            </button>
        </div>
    </form>
</div>

                        <!-- 步骤3：安装系统 -->
                        <div class="install-step <?php echo $step == 3 ? 'active' : ''; ?>">
                            <h5>步骤 3：安装系统</h5>
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">安装中...</span>
                                </div>
                                <p>正在安装系统，请稍候...</p>
                                <form method="POST">
                                    <button type="submit" class="btn btn-primary">
                                        开始安装 <i class="bi bi-play-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- 步骤4：安装完成 -->
                        <div class="install-step <?php echo $step == 4 ? 'active' : ''; ?>">
                            <h5>步骤 4：安装完成</h5>
                            <div class="text-center">
                                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="text-success mt-3">恭喜！系统安装成功</h4>
                                <p class="text-muted">维修管理系统已经成功安装到您的服务器上</p>
                                
                                <div class="alert alert-info">
                                    <strong>默认管理员账户：</strong><br>
                                    用户名：<code>admin</code><br>
                                    密码：<code>admin123</code><br>
                                    <small class="text-danger">请立即登录并修改默认密码！</small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="index.php" class="btn btn-success btn-lg">
                                        <i class="bi bi-house"></i> 访问系统首页
                                    </a>
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right"></i> 立即登录
                                    </a>
                                </div>
                                
                                <div class="mt-4">
                                    <small class="text-muted">
                                        安装完成后，您可以删除 install.php 文件以提高安全性
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       // 仅测试数据库连接
function testConnection() {
    const formData = new FormData(document.querySelector('form'));
    const data = {
        host: formData.get('db_host'),
        port: formData.get('db_port'),
        user: formData.get('db_user'),
        pass: formData.get('db_pass')
    };
    
    // 显示测试中状态
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> 测试中...';
    btn.disabled = true;
    
    // 发送AJAX请求测试连接
    fetch('test_connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        // 检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            return response.json();
        } else {
            throw new Error('服务器返回了非JSON响应，请检查服务器配置');
        }
    })
    .then(data => {
        if (data.success) {
            alert('✅ 连接成功！\n\n数据库服务器连接正常，可以继续安装。');
        } else {
            alert('❌ 连接失败！\n\n错误信息：' + data.message + '\n\n请检查：\n1. 主机地址是否正确\n2. 端口号是否正确\n3. 用户名密码是否正确\n4. MySQL服务是否启动');
        }
    })
    .catch(error => {
        alert('❌ 测试失败！\n\n网络错误：' + error.message);
    })
    .finally(() => {
        // 恢复按钮状态
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
    </script>
</body>
</html>
