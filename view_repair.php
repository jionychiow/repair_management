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
} catch (PDOException $e) {
    die('数据库错误: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>维修记录详情 - 维修管理系统</title>
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
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-eye"></i> 维修记录详情
                        </h5>
                        <a href="index.php" class="btn btn-sm btn-light">
                            <i class="bi bi-arrow-left"></i> 返回列表
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>设备编号:</th>
                                        <td><?php echo htmlspecialchars($repair['device_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>设备型号:</th>
                                        <td><?php echo htmlspecialchars($repair['device_model']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>设备类型:</th>
                                        <td><?php echo htmlspecialchars($repair['device_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>设备属于:</th>
                                        <td><?php echo htmlspecialchars($repair['device_belong']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>工段:</th>
                                        <td><?php echo htmlspecialchars($repair['section']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>数量:</th>
                                        <td><?php echo htmlspecialchars($repair['quantity']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>接收日期:</th>
                                        <td><?php echo htmlspecialchars($repair['received_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>维修状态:</th>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($repair['status']) {
                                                    case '待维修': echo 'warning'; break;
                                                    case '未维修': echo 'secondary'; break;
                                                    case '检修中': echo 'info'; break;
                                                    case '已维修': echo 'success'; break;
                                                    case '报废': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($repair['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>优先级:</th>
                                        <td>
                                            <span class="<?php
                                                switch ($repair['priority']) {
                                                    case 'high': echo 'text-danger fw-bold'; break;
                                                    case 'medium': echo 'text-warning fw-bold'; break;
                                                    case 'low': echo 'text-success fw-bold'; break;
                                                    default: echo '';
                                                }
                                            ?>">
                                                <?php
                                                    switch ($repair['priority']) {
                                                        case 'high': echo '高'; break;
                                                        case 'medium': echo '中'; break;
                                                        case 'low': echo '低'; break;
                                                        default: echo '未知';
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>负责人:</th>
                                        <td><?php echo htmlspecialchars($repair['assigned_to'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>完成时间:</th>
                                        <td>
                                            <?php if ($repair['completion_time']): ?>
                                                <span class="text-success fw-bold">
                                                    <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($repair['completion_time']))); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-warning">未记录</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>创建时间:</th>
                                        <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($repair['created_at']))); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h6>故障描述:</h6>
                                <p class="border p-3 bg-white rounded"><?php echo htmlspecialchars($repair['fault_description']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($repair['notes'])): ?>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h6>备注:</h6>
                                <p class="border p-3 bg-white rounded"><?php echo htmlspecialchars($repair['notes']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>