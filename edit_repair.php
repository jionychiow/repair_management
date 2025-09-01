<?php
session_start();
require_once 'config/database.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 获取维修记录ID
$repair_id = $_GET['id'] ?? null;

if (!$repair_id) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // 获取维修记录详情
    $stmt = $pdo->prepare("SELECT * FROM repair_records WHERE id = ?");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        header('Location: index.php');
        exit();
    }
    
    // 获取所有用户信息用于维修员选择
    $stmt = $pdo->prepare("SELECT id, username, name FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('数据库错误: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑维修记录 - 维修管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tools"></i> 维修管理系统
                <small class="text-muted">开发者：Jionychiow-韦</small>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="bi bi-house"></i> 返回首页</a>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> 退出登录</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-pencil-square"></i> 编辑维修记录
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                            unset($_SESSION['error']);
                        }
                        if (isset($_SESSION['success'])) {
                            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                            unset($_SESSION['success']);
                        }
                        ?>
                        <form action="update_repair.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($repair['id']); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="device_number" class="form-label">设备编号 *</label>
                                        <input type="text" class="form-control" id="device_number" name="device_number" value="<?php echo htmlspecialchars($repair['device_number']); ?>" readonly>
                                        <div class="form-text">设备编号不可更改</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="device_model" class="form-label">设备型号 *</label>
                                        <input type="text" class="form-control" id="device_model" name="device_model" value="<?php echo htmlspecialchars($repair['device_model']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="device_type" class="form-label">设备类型 *</label>
                                        <select class="form-select" id="device_type" name="device_type" required>
                                            <option value="">请选择设备类型</option>
                                            <option value="变频器" <?php echo $repair['device_type'] == '变频器' ? 'selected' : ''; ?>>变频器</option>
                                            <option value="调功器" <?php echo $repair['device_type'] == '调功器' ? 'selected' : ''; ?>>调功器</option>
                                            <option value="伺服器" <?php echo $repair['device_type'] == '伺服器' ? 'selected' : ''; ?>>伺服器</option>
                                            <option value="PLC设备" <?php echo $repair['device_type'] == 'PLC设备' ? 'selected' : ''; ?>>PLC设备</option>
                                            <option value="其他电子设备" <?php echo $repair['device_type'] == '其他电子设备' ? 'selected' : ''; ?>>其他电子设备</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="device_belong" class="form-label">设备属于 *</label>
                                        <select class="form-select" id="device_belong" name="device_belong" required>
                                            <option value="">请选择设备属于</option>
                                            <option value="一期" <?php echo $repair['device_belong'] == '一期' ? 'selected' : ''; ?>>一期</option>
                                            <option value="二期" <?php echo $repair['device_belong'] == '二期' ? 'selected' : ''; ?>>二期</option>
                                            <option value="其它" <?php echo $repair['device_belong'] == '其它' ? 'selected' : ''; ?>>其它</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="section" class="form-label">工段 *</label>
                                        <select class="form-select" id="section" name="section" required>
                                            <option value="">请选择工段</option>
                                            <option value="电窑" <?php echo $repair['section'] == '电窑' ? 'selected' : ''; ?>>电窑</option>
                                            <option value="配料" <?php echo $repair['section'] == '配料' ? 'selected' : ''; ?>>配料</option>
                                            <option value="B工序" <?php echo $repair['section'] == 'B工序' ? 'selected' : ''; ?>>B工序</option>
                                            <option value="粉碎" <?php echo $repair['section'] == '粉碎' ? 'selected' : ''; ?>>粉碎</option>
                                            <option value="包装" <?php echo $repair['section'] == '包装' ? 'selected' : ''; ?>>包装</option>
                                            <option value="后勤" <?php echo $repair['section'] == '后勤' ? 'selected' : ''; ?>>后勤</option>
                                            <option value="其它" <?php echo $repair['section'] == '其它' ? 'selected' : ''; ?>>其它</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">数量 *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($repair['quantity']); ?>" min="1" required>
                                        <div class="form-text">维修设备的数量</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="fault_description" class="form-label">故障描述 *</label>
                                <textarea class="form-control" id="fault_description" name="fault_description" rows="3" required><?php echo htmlspecialchars($repair['fault_description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="priority" class="form-label">优先级 *</label>
                                        <select class="form-select" id="priority" name="priority" required>
                                            <option value="">请选择优先级</option>
                                            <option value="high" <?php echo $repair['priority'] == 'high' ? 'selected' : ''; ?>>高</option>
                                            <option value="medium" <?php echo $repair['priority'] == 'medium' ? 'selected' : ''; ?>>中</option>
                                            <option value="low" <?php echo $repair['priority'] == 'low' ? 'selected' : ''; ?>>低</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="received_date" class="form-label">接收日期 *</label>
                                        <input type="date" class="form-control" id="received_date" name="received_date" value="<?php echo htmlspecialchars($repair['received_date']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">维修员 *</label>
                                <select class="form-select" id="assigned_to" name="assigned_to" required>
                                    <option value="">请选择维修员</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>" <?php echo $repair['assigned_to'] == ($user['name'] ?? $user['username']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">备注</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($repair['notes']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle"></i> 取消
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> 保存更改
                                </button>
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