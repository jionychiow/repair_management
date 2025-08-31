<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); 
require_once '../config/database.php';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败：' . $e->getMessage()]);
    exit;
}

try {
    // 获取总体统计
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM repair_records");
    $total = $stmt->fetch()['total'];

    // 获取各状态统计
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM repair_records 
        GROUP BY status
    ");
    $statusStats = $stmt->fetchAll();

    // 获取设备类型统计
    $stmt = $pdo->query("
        SELECT device_type, COUNT(*) as count 
        FROM repair_records 
        GROUP BY device_type
    ");
    $deviceTypeStats = $stmt->fetchAll();

    // 获取优先级统计
    $stmt = $pdo->query("
        SELECT priority, COUNT(*) as count 
        FROM repair_records 
        GROUP BY priority
    ");
    $priorityStats = $stmt->fetchAll();

    // 获取月度统计
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(received_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM repair_records 
        GROUP BY DATE_FORMAT(received_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthlyStats = $stmt->fetchAll();

    // 获取完成率
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = '已维修' THEN 1 END) as completed,
            COUNT(*) as total
        FROM repair_records
    ");
    $completionStats = $stmt->fetch();
    $completionRate = $total > 0 ? round(($completionStats['completed'] / $total) * 100, 2) : 0;

    // 获取平均维修时间（已完成的项目）
    $stmt = $pdo->query("
        SELECT 
            AVG(DATEDIFF(updated_at, received_date)) as avg_repair_days
        FROM repair_records 
        WHERE status = '已维修'
    ");
    $avgRepairTime = $stmt->fetch()['avg_repair_days'];

        // 获取设备属于统计
    $stmt = $pdo->query("
        SELECT device_belong, COUNT(*) as count 
        FROM repair_records 
        GROUP BY device_belong
    ");
    $deviceBelongStats = $stmt->fetchAll();
    
    // 获取工段统计
    $stmt = $pdo->query("
        SELECT section, COUNT(*) as count 
        FROM repair_records 
        GROUP BY section
    ");
    $sectionStats = $stmt->fetchAll();
    
    // 获取设备类型统计
    $stmt = $pdo->query("
        SELECT device_type, COUNT(*) as count 
        FROM repair_records 
        GROUP BY device_type
    ");
    $deviceTypeStats = $stmt->fetchAll();
    
    $statistics = [
        'total' => $total,
        'status_distribution' => $statusStats,
        'device_belong_distribution' => $deviceBelongStats,
        'section_distribution' => $sectionStats,
        'device_type_distribution' => $deviceTypeStats,
        'priority_distribution' => $priorityStats,
        'monthly_trend' => $monthlyStats,
        'completion_rate' => $completionRate,
        'avg_repair_days' => $avgRepairTime !== null ? round($avgRepairTime, 1) : 0,
        'status_counts' => [
            '待维修' => 0,
            '未维修' => 0,
            '检修中' => 0,
            '已维修' => 0,
            '报废' => 0
        ]
    ];

    // 填充状态计数
    foreach ($statusStats as $stat) {
        $statistics['status_counts'][$stat['status']] = (int)$stat['count'];
    }

    echo json_encode(['success' => true, 'data' => $statistics]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '获取统计信息失败：' . $e->getMessage()]);
}
