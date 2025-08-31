<?php
// 维修记录数据调试脚本
require_once 'config/database.php';

echo "<h2>维修记录数据调试</h2>";

echo "<h3>数据库连接信息：</h3>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_PORT: " . DB_PORT . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (DB_PASS ? '已设置' : '未设置') . "<br>";

echo "<h3>尝试连接数据库...</h3>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>数据库连接成功！</p>";

    // 检查数据库是否存在
    $stmt = $pdo->query("SHOW DATABASES LIKE 'repair_management'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>数据库 'repair_management' 存在</p>";

        // 选择数据库
        $pdo->query("USE repair_management");

        // 检查repair_records表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'repair_records'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>表 'repair_records' 存在</p>";

            // 检查表结构
            $stmt = $pdo->query("SHOW COLUMNS FROM repair_records LIKE 'device_belong'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>字段 'device_belong' 存在</p>";
            } else {
                echo "<p style='color: red;'>字段 'device_belong' 不存在</p>";
            }
            
            $stmt = $pdo->query("SHOW COLUMNS FROM repair_records LIKE 'section'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>字段 'section' 存在</p>";
            } else {
                echo "<p style='color: red;'>字段 'section' 不存在</p>";
            }

            // 获取所有维修记录
            $stmt = $pdo->query("SELECT * FROM repair_records");
            $repairs = $stmt->fetchAll();

            if (count($repairs) > 0) {
                echo "<h3>维修记录：</h3>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>ID</th><th>设备编号</th><th>设备型号</th><th>设备属于</th><th>工段</th><th>故障描述</th><th>接收日期</th><th>状态</th></tr>";

                foreach ($repairs as $repair) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($repair['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['device_number']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['device_model']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['device_belong']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['section']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['fault_description']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['received_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($repair['status']) . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p style='color: orange;'>维修记录表为空</p>";
            }
        } else {
            echo "<p style='color: red;'>表 'repair_records' 不存在</p>";
        }
    } else {
        echo "<p style='color: red;'>数据库 'repair_management' 不存在</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>数据库连接失败: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='index.html'>返回主页</a>";
?>