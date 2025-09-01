<?php
session_start();
require_once 'config/database.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$format = $_GET['format'] ?? 'csv';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$exportAll = $_GET['export_all'] ?? 'false'; // 是否导出所有数据

try {
    $pdo = getDBConnection();

    // 构建查询条件
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(device_number LIKE ? OR device_model LIKE ? OR customer_name LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }

    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    if (!empty($date)) {
        $where[] = "received_date = ?";
        $params[] = $date;
    }
    
    // 添加日期范围筛选
    if (!empty($startDate) && !empty($endDate)) {
        $where[] = "received_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    } elseif (!empty($startDate)) {
        $where[] = "received_date >= ?";
        $params[] = $startDate;
    } elseif (!empty($endDate)) {
        $where[] = "received_date <= ?";
        $params[] = $endDate;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // 如果导出所有数据，则不限制数量；否则限制最大导出数量避免卡顿
    if ($exportAll === 'true') {
        $sql = "SELECT * FROM repair_records $whereClause ORDER BY created_at DESC, updated_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repairs = $stmt->fetchAll();
    } else {
        // 限制导出数量，避免大数据量导致卡顿
        $sql = "SELECT * FROM repair_records $whereClause ORDER BY created_at DESC, updated_at DESC LIMIT 1000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repairs = $stmt->fetchAll();

        if (count($repairs) >= 1000) {
            // 如果达到限制，提示用户
            echo '<div class="alert alert-warning">数据量较大，已限制导出前1000条记录。如需导出全部数据，请使用筛选条件缩小范围。</div>';
        }
    }

    if ($format === 'csv') {
        // 导出CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="repair_records_' . date('Y-m-d') . '.csv"');

        // 输出BOM以支持中文
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // 写入表头
        fputcsv($output, [
            '设备编号',
            '设备型号',
            '设备类型',
            '设备属于',
            '工段',
            '数量',
            '故障描述',
            '接收日期',
            '维修状态',
            '优先级',
            '维修员',
            '备注',
            '完成时间',
            '创建时间'
        ]);

        // 写入数据
        foreach ($repairs as $repair) {
            $statusText = [
                '待维修' => '待维修',
                '未维修' => '未维修',
                '检修中' => '检修中',
                '已维修' => '已维修',
                '报废' => '报废'
            ];

            $priorityText = [
                'high' => '高',
                'medium' => '中',
                'low' => '低'
            ];

            fputcsv($output, [
                $repair['device_number'],
                $repair['device_model'],
                $repair['device_type'],
                $repair['device_belong'],
                $repair['section'],
                $repair['quantity'] ?? 1,
                $repair['fault_description'],
                $repair['received_date'],
                $statusText[$repair['status']] ?? $repair['status'],
                $priorityText[$repair['priority']] ?? $repair['priority'],
                $repair['assigned_to'],
                $repair['notes'],
                $repair['completion_time'] ?? '',
                $repair['created_at']
            ]);
        }

        fclose($output);
    } elseif ($format === 'excel') {
        // 导出Excel (HTML格式，可以用Excel打开)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="repair_records_' . date('Y-m-d') . '.xls"');

        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000; padding: 5px; text-align: left; }';
        echo 'th { background-color: #f0f0f0; font-weight: bold; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<table>';

        // 表头
        echo '<tr>';
        echo '<th>设备编号</th>';
        echo '<th>设备型号</th>';
        echo '<th>设备类型</th>';
        echo '<th>设备属于</th>';
        echo '<th>工段</th>';
        echo '<th>数量</th>';
        echo '<th>故障描述</th>';
        echo '<th>接收日期</th>';
        echo '<th>维修状态</th>';
        echo '<th>优先级</th>';
        echo '<th>维修员</th>';
        echo '<th>备注</th>';
        echo '<th>完成时间</th>';
        echo '<th>创建时间</th>';
        echo '</tr>';

        // 数据行
        foreach ($repairs as $repair) {
            $statusText = [
                '待维修' => '待维修',
                '未维修' => '未维修',
                '检修中' => '检修中',
                '已维修' => '已维修',
                '报废' => '报废'
            ];

            $priorityText = [
                'high' => '高',
                'medium' => '中',
                'low' => '低'
            ];

            echo '<tr>';
            echo '<td>' . htmlspecialchars($repair['device_number']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['device_model']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['device_type']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['device_belong']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['section']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['quantity'] ?? 1) . '</td>';
            echo '<td>' . htmlspecialchars($repair['fault_description']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['received_date']) . '</td>';
            echo '<td>' . htmlspecialchars($statusText[$repair['status']] ?? $repair['status']) . '</td>';
            echo '<td>' . htmlspecialchars($priorityText[$repair['priority']] ?? $repair['priority']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['assigned_to']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['notes']) . '</td>';
            echo '<td>' . htmlspecialchars($repair['completion_time'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($repair['created_at']) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
} catch (PDOException $e) {
    echo '导出失败：' . $e->getMessage();
}