<?php
// 数据库连接调试脚本
// 使用方法：在浏览器中访问此文件，输入数据库信息进行测试

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '3306';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    
    if (!empty($host) && !empty($user)) {
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 测试连接
            $pdo->query('SELECT 1');
            $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
            
            $success = true;
            $message = "连接成功！MySQL版本：{$version}";
        } catch (PDOException $e) {
            $success = false;
            $message = "连接失败：" . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库连接调试</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">数据库连接调试工具</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="host" class="form-label">主机地址</label>
                                <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($_POST['host'] ?? 'localhost'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="port" class="form-label">端口</label>
                                <input type="number" class="form-control" id="port" name="port" value="<?php echo htmlspecialchars($_POST['port'] ?? '3306'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="user" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="user" name="user" value="<?php echo htmlspecialchars($_POST['user'] ?? 'root'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="pass" class="form-label">密码</label>
                                <input type="password" class="form-control" id="pass" name="pass" value="<?php echo htmlspecialchars($_POST['pass'] ?? ''); ?>">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">测试连接</button>
                            </div>
                        </form>
                        
                        <hr>
                        <div class="mt-3">
                            <h6>调试信息：</h6>
                            <ul class="list-unstyled">
                                <li><strong>PHP版本：</strong><?php echo PHP_VERSION; ?></li>
                                <li><strong>PDO MySQL扩展：</strong><?php echo extension_loaded('pdo_mysql') ? '已安装' : '未安装'; ?></li>
                                <li><strong>当前时间：</strong><?php echo date('Y-m-d H:i:s'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
