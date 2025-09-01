<?php
// 用户数据调试脚本
require_once 'config/database.php';

echo "<h2>用户数据调试</h2>";

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

        // 检查users表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>表 'users' 存在</p>";

            // 获取所有用户记录
            $stmt = $pdo->query("SELECT * FROM users");
            $users = $stmt->fetchAll();

            if (count($users) > 0) {
                echo "<h3>用户记录：</h3>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>ID</th><th>用户名</th><th>中文名字</th><th>密码哈希</th><th>邮箱</th><th>创建时间</th></tr>";

                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['password']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p style='color: orange;'>用户表为空</p>";
            }
        } else {
            echo "<p style='color: red;'>表 'users' 不存在</p>";
        }
    } else {
        echo "<p style='color: red;'>数据库 'repair_management' 不存在</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>数据库连接失败: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='login.php'>返回登录页面</a>";
?>