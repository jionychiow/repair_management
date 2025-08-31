<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $id = intval($_POST['id']);
    $device_model = trim($_POST['device_model'] ?? '');
    $device_type = trim($_POST['device_type'] ?? '');
    $device_belong = trim($_POST['device_belong'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $fault_description = trim($_POST['fault_description'] ?? '');
    $received_date = $_POST['received_date'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $notes = trim($_POST['notes'] ?? '');

    // 验证必填字段
    if (
        empty($device_model) || empty($device_type) ||
        empty($device_belong) || empty($section) || empty($fault_description) ||
        empty($received_date)
    ) {
        $_SESSION['error'] = '请填写所有必填字段';
        header('Location: edit_repair.php?id=' . $id);
        exit();
    }

    try {
        $pdo = getDBConnection();

        // 更新记录
        $stmt = $pdo->prepare(
            "UPDATE repair_records SET 
                device_model = ?, 
                device_type = ?, 
                device_belong = ?, 
                section = ?, 
                quantity = ?, 
                fault_description = ?,
                received_date = ?, 
                priority = ?, 
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?"
        );

        $stmt->execute([
            $device_model,
            $device_type,
            $device_belong,
            $section,
            $quantity,
            $fault_description,
            $received_date,
            $priority,
            $notes,
            $id
        ]);

        $_SESSION['success'] = '维修记录更新成功！';
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = '更新失败，请稍后重试';
        header('Location: edit_repair.php?id=' . $id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>