<?php
session_start();

// 安全头部设置
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 移除X-Powered-By头部
header_remove('X-Powered-By');
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>维修管理系统 byJionychiow-韦</title>
    <!-- 修改index.php中的静态资源引用 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet" crossorigin="anonymous">
    <!-- 添加Chart.js库 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/main.js?v=<?php echo time(); ?>" crossorigin="anonymous"></script>
    <style>
        /* 添加CSS兼容性样式 */
        body {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        th {
            text-align: -webkit-match-parent;
            text-align: match-parent;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            html {
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }

            .row .col-md-3 {
                margin-bottom: 1rem;
            }
        }

        /* 状态标签样式 - 自动颜色识别 */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }

        /* 根据状态自动设置颜色 */
        .status-badge[data-status="待维修"] {
            background-color: #ffc107;
            color: #000;
        }

        .status-badge[data-status="未维修"] {
            background-color: #6c757d;
            color: #fff;
        }

        .status-badge[data-status="检修中"] {
            background-color: #17a2b8;
            color: #fff;
        }

        .status-badge[data-status="已维修"] {
            background-color: #28a745;
            color: #fff;
        }

        .status-badge[data-status="报废"] {
            background-color: #dc3545;
            color: #fff;
        }

        /* 优先级样式 */
        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }

        .priority-medium {
            color: #ffc107;
            font-weight: bold;
        }

        .priority-low {
            color: #28a745;
            font-weight: bold;
        }

        /* 数量记录样式 */
        .quantity-badge {
            background-color: #6c757d;
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        /* 时间样式 */
        .time-info {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .completion-time {
            color: #28a745;
            font-weight: bold;
        }

        .no-completion-time {
            color: #ffc107;
            font-style: italic;
        }

        /* 筛选按钮组 */
        .filter-buttons {
            margin-bottom: 1rem;
        }

        .filter-buttons .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* 表格优化 */
        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }

        .table td {
            vertical-align: middle;
        }

        /* 在中等及以上屏幕上优化表格显示 */
        @media (min-width: 768px) {
            .table-responsive {
                overflow-x: visible;
            }

            /* 为操作列设置固定宽度 */
            #repairTable th:last-child,
            #repairTable td:last-child {
                width: 180px;
                min-width: 180px;
            }

            /* 为其他列设置合理的最小宽度 */
            #repairTable th:nth-child(1),
            #repairTable td:nth-child(1) {
                /* 设备编号 */
                min-width: 100px;
            }

            #repairTable th:nth-child(2),
            #repairTable td:nth-child(2) {
                /* 设备型号 */
                min-width: 120px;
            }

            #repairTable th:nth-child(4),
            #repairTable td:nth-child(4) {
                /* 故障描述 */
                min-width: 150px;
            }

            #repairTable th:nth-child(5),
            #repairTable td:nth-child(5) {
                /* 设备属于 */
                min-width: 100px;
            }

            #repairTable th:nth-child(6),
            #repairTable td:nth-child(6) {
                /* 工段 */
                min-width: 100px;
            }
        }

        /* 在大屏幕上进一步优化 */
        @media (min-width: 992px) {

            #repairTable th:nth-child(4),
            #repairTable td:nth-child(4) {
                /* 故障描述 */
                min-width: 200px;
            }

            #repairTable th:nth-child(5),
            #repairTable td:nth-child(5) {
                /* 设备属于 */
                min-width: 120px;
            }

            #repairTable th:nth-child(6),
            #repairTable td:nth-child(6) {
                /* 工段 */
                min-width: 120px;
            }
        }

        /* 移动端优化 */
        @media (max-width: 576px) {
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            /* 为设备属于和工段列设置合适的最小宽度 */
            #repairTable th:nth-child(5),
            #repairTable td:nth-child(5) {
                /* 设备属于 */
                min-width: 80px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #repairTable th:nth-child(6),
            #repairTable td:nth-child(6) {
                /* 工段 */
                min-width: 80px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* 调整操作列宽度 */
            #repairTable th:last-child,
            #repairTable td:last-child {
                width: 160px;
                min-width: 160px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tools"></i> 维修管理系统
                <small class="text-muted">开发者：Jionychiow-韦</small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="切换导航">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo '<a class="nav-link" href="add_repair.php"><i class="bi bi-plus-circle"></i> 添加维修记录</a>';
                        echo '<a class="nav-link" href="change_password.php"><i class="bi bi-key"></i> 修改密码</a>';
                        echo '<a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> 退出登录</a>';
                    } else {
                        echo '<a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> 登录</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">总设备数</h5>
                        <h3 id="totalDevices">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">已完成</h5>
                        <h3 id="completedRepairs">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">检修中</h5>
                        <h3 id="repairingCount">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">无法修复</h5>
                        <h3 id="unrepairableCount">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- 快速筛选按钮 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">快速筛选</h6>
            </div>
            <div class="card-body">
                <div class="filter-buttons">
                    <button class="btn btn-outline-primary" onclick="filterByStatus('')">
                        <i class="bi bi-list-ul"></i> 全部
                    </button>
                    <button class="btn btn-outline-warning" onclick="filterByStatus('待维修')">
                        <i class="bi bi-clock"></i> 待维修
                    </button>
                    <button class="btn btn-outline-secondary" onclick="filterByStatus('未维修')">
                        <i class="bi bi-exclamation-triangle"></i> 未维修
                    </button>
                    <button class="btn btn-outline-info" onclick="filterByStatus('检修中')">
                        <i class="bi bi-tools"></i> 检修中
                    </button>
                    <button class="btn btn-outline-success" onclick="filterByStatus('已维修')">
                        <i class="bi bi-check-circle"></i> 已维修
                    </button>
                    <button class="btn btn-outline-danger" onclick="filterByStatus('报废')">
                        <i class="bi bi-x-circle"></i> 报废
                    </button>
                    <button class="btn btn-outline-secondary" onclick="filterNoCompletionTime()">
                        <i class="bi bi-exclamation-triangle"></i> 无结束时间
                    </button>
                </div>
            </div>
        </div>

        <!-- 搜索和筛选 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-2">
                        <input type="text" class="form-control" id="searchDevice" placeholder="搜索设备编号或型号">
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2">
                        <select class="form-select" id="statusFilter" aria-label="按状态筛选">
                            <option value="">所有状态</option>
                            <option value="待维修">待维修</option>
                            <option value="未维修">未维修</option>
                            <option value="检修中">检修中</option>
                            <option value="已维修">已维修</option>
                            <option value="报废">报废</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2">
                        <input type="date" class="form-control" id="dateFilter" aria-label="按日期筛选">
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <button class="btn btn-primary" onclick="searchRepairs()">
                            <i class="bi bi-search"></i> 搜索
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            <i class="bi bi-arrow-clockwise"></i> 重置
                        </button>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2">
                        <div class="btn-group">
                            <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> 导出数据
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv')">导出CSV</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('excel')">导出Excel</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#" onclick="showExportModal()">按日期范围导出</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 维修记录表格 -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">维修记录</h5>
                <small class="text-muted">按时间倒序排列，支持分页浏览</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="repairTable">
                        <thead>
                            <tr>
                                <th>设备编号</th>
                                <th>设备型号</th>
                                <th>数量</th>
                                <th>故障描述</th>
                                <th>设备属于</th>
                                <th>工段</th>
                                <th>接收日期</th>
                                <th>维修状态</th>
                                <th>优先级</th>
                                <th>维修员</th>
                                <th>完成时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="repairTableBody">
                            <!-- 数据将通过JavaScript动态加载 -->
                        </tbody>
                    </table>
                </div>
                <!-- 分页控件将通过JavaScript动态生成 -->
            </div>
        </div>

        <!-- 统计图表 -->
        <div class="row mt-4">
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">维修状态分布</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">设备属于分布</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="belongChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">设备类型占比</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="deviceTypeChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">工段分布</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="sectionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- 开发者信息 -->
        <div class="row mt-4">
            <div class="col-12 text-center text-muted">
                <small>开发者：Jionychiow-韦</small>
            </div>
        </div>

        <!-- 日期范围导出模态框 -->
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">按日期范围导出数据</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="startDate" class="form-label">开始日期</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">结束日期</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <div class="mb-3">
                            <label for="exportFormat" class="form-label">导出格式</label>
                            <select class="form-select" id="exportFormat">
                                <option value="csv">CSV格式</option>
                                <option value="excel">Excel格式</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="exportAll" checked>
                            <label class="form-check-label" for="exportAll">
                                导出所有匹配数据（可能较慢）
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" onclick="exportByDateRange()">导出</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // 页面加载时重新加载数据
            window.addEventListener('load', function() {
                // 重新加载数据
                loadRepairData(1);
                loadStatistics();
            });

            // 重新加载数据
            loadRepairData(1);
            loadStatistics();
        </script>

</body>

</html>