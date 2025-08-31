<?php
// 登录过程详细调试脚本
session_start();
require_once 'config/database.php';

echo "<h2>登录过程详细调试</h2>";

echo "<h3>1. 请求信息：</h3>";
echo "请求方法: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "请求URI: " . $_SERVER['REQUEST_URI'] . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>2. POST数据：</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    echo "<h3>3. 数据处理：</h3>";
    echo "用户名: '" . htmlspecialchars($username) . "'<br>";
    echo "密码长度: " . strlen($password) . " 字符<br>";

    if (empty($username) || empty($password)) {
        echo "<p style='color: red;'>错误：用户名或密码为空</p>";
        exit();
    }

    echo "<h3>4. 数据库连接：</h3>";
    try {
        $pdo = getDBConnection();
        echo "<p style='color: green;'>数据库连接成功</p>";

        echo "<h3>5. 数据库查询：</h3>";
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        echo "SQL语句: SELECT id, username, password FROM users WHERE username = ?<br>";
        echo "绑定参数: '" . htmlspecialchars($username) . "'<br>";

        $stmt->execute([$username]);
        $user = $stmt->fetch();

        echo "查询结果: <br>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";

        if ($user) {
            echo "<h3>6. 密码验证：</h3>";
            echo "数据库中的密码哈希: " . htmlspecialchars($user['password']) . "<br>";
            echo "输入的密码: " . htmlspecialchars($password) . "<br>";

            $verify_result = password_verify($password, $user['password']);
            echo "密码验证结果: " . ($verify_result ? "<span style='color: green;'>成功</span>" : "<span style='color: red;'>失败</span>") . "<br>";

            if ($verify_result) {
                echo "<p style='color: green; font-weight: bold;'>登录应该成功！</p>";
                echo "<p>用户信息：<br>";
                echo "ID: " . $user['id'] . "<br>";
                echo "用户名: " . $user['username'] . "<br>";
                echo "</p>";
            } else {
                echo "<p style='color: red;'>密码验证失败，登录会被拒绝</p>";
            }
        } else {
            echo "<p style='color: red;'>未找到用户，登录会被拒绝</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>数据库连接失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>这不是POST请求，请通过登录表单提交数据</p>";

    echo "<h3>登录表单：</h3>";
    echo '<form method="POST">';
    echo '用户名: <input type="text" name="username" value="admin"><br><br>';
    echo '密码: <input type="password" name="password" value="admin123"><br><br>';
    echo '<input type="submit" value="调试登录">';
    echo '</form>';
}

echo "<hr>";
echo "<a href='login.php'>返回登录页面</a>";
