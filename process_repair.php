<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $device_model = trim($_POST['device_model'] ?? '');
    $device_type = trim($_POST['device_type'] ?? '');
    $device_belong = trim($_POST['device_belong'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $fault_description = trim($_POST['fault_description'] ?? '');
    $received_date = $_POST['received_date'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $assigned_to = trim($_POST['assigned_to'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // 验证必填字段
    if (
        empty($device_model) || empty($device_type) ||
        empty($device_belong) || empty($section) || empty($fault_description) ||
        empty($received_date)
    ) {
        $_SESSION['error'] = '请填写所有必填字段';
        header('Location: add_repair.php');
        exit();
    }

    try {
        $pdo = getDBConnection();
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 生成设备编号前缀
        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM repair_records");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_id = ($result['max_id'] ?? 0) + 1;
        
        // 创建指定数量的记录
        $success_count = 0;
        for ($i = 0; $i < $quantity; $i++) {
            // 为每个记录生成唯一的设备编号
            $device_number = 'WX-' . str_pad($new_id + $i, 3, '0', STR_PAD_LEFT);
            
            // 检查设备编号是否已存在
            $checkStmt = $pdo->prepare("SELECT id FROM repair_records WHERE device_number = ?");
            $checkStmt->execute([$device_number]);
            if ($checkStmt->fetch()) {
                // 如果编号已存在，跳过这个编号
                $i--;
                continue;
            }
            
            // 插入新记录，每条记录数量都为1
            $insertStmt = $pdo->prepare("
                INSERT INTO repair_records (
                    device_number, device_model, device_type, device_belong, section, quantity, fault_description,
                    received_date, priority, assigned_to, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $device_number,
                $device_model,
                $device_type,
                $device_belong,
                $section,
                1, // 每条记录的数量都为1
                $fault_description,
                $received_date,
                $priority,
                $assigned_to,
                $notes
            ]);
            
            $success_count++;
        }
        
        // 提交事务
        $pdo->commit();
        
        $_SESSION['success'] = "成功创建 {$success_count} 条维修记录！";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        // 回滚事务
        $pdo->rollback();
        $_SESSION['error'] = '保存失败，请稍后重试: ' . $e->getMessage();
        header('Location: add_repair.php');
        exit();
    }
} else {
    header('Location: add_repair.php');
    exit();
}