<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 维修管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/themes.css" rel="stylesheet">
    <style>
        .login-card { border-radius: 20px !important; overflow: hidden; }
        .login-card .card-header { padding: 1.5rem; }
        .login-card .card-header h4 { font-weight: 700; }
        .login-card .card-body { padding: 2rem; }
        .login-icon { font-size: 2.5rem; opacity: 0.15; position: absolute; right: 20px; top: 20px; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow login-card">
                    <div class="card-header text-center bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-tools"></i> 维修管理系统
                        </h4>
                        <p class="mb-0 mt-1" style="opacity:0.8">用户登录</p>
                    </div>
                    <div class="card-body">
                        <?php
                        session_start();
                        if (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                            unset($_SESSION['error']);
                        }
                        if (isset($_SESSION['success'])) {
                            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                            unset($_SESSION['success']);
                        }
                        ?>
                        <form action="auth.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> 登录
                                </button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> 返回首页
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme-switcher.js"></script>
</body>
</html>
