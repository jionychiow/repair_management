<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = '请填写所有字段';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '新密码和确认密码不匹配';
    } elseif (strlen($newPassword) < 6) {
        $error = '新密码长度至少为6位';
    } else {
        try {
            $pdo = getDBConnection();

            // 验证旧密码
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($oldPassword, $user['password'])) {
                // 更新密码
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

                $success = '密码修改成功！';
            } else {
                $error = '旧密码不正确';
            }
        } catch (PDOException $e) {
            $error = '操作失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 维修管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tools"></i> 维修管理系统
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="bi bi-house"></i> 返回首页</a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> 退出登录</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-key"></i> 修改密码
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="old_password" class="form-label">旧密码 *</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码 *</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">密码长度至少6位</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码 *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> 修改密码
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> 返回首页
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>