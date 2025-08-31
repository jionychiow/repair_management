<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
   // $device_number = trim($_POST['device_number'] ?? '');
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
        /*empty($device_number) || */empty($device_model) || empty($device_type) ||
        empty($device_belong) || empty($section) || empty($fault_description) ||
        empty($received_date)
    ) {
        $_SESSION['error'] = '请填写所有必填字段';
        header('Location: add_repair.php');
        exit();
    }

    try {
        $pdo = getDBConnection();

        // 生成新的设备编号
        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM repair_records");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_id = ($result['max_id'] ?? 0) + 1;
        $device_number = 'WX-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);

        // 检查设备编号是否已存在
        $stmt = $pdo->prepare("SELECT id FROM repair_records WHERE device_number = ?");
        $stmt->execute([$device_number]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '设备编号已存在，请使用不同的编号';
            header('Location: add_repair.php');
            exit();
        }

        // 插入新记录
        $stmt = $pdo->prepare("
            INSERT INTO repair_records (
                device_number, device_model, device_type, device_belong, section, quantity, fault_description,
                received_date, priority, assigned_to, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $device_number,
            $device_model,
            $device_type,
            $device_belong,
            $section,
            $quantity,
            $fault_description,
            $received_date,
            $priority,
            $assigned_to,
            $notes
        ]);

        $_SESSION['success'] = '维修记录添加成功！';
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = '保存失败，请稍后重试';
        header('Location: add_repair.php');
        exit();
    }
} else {
    header('Location: add_repair.php');
    exit();
}
