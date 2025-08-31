<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "密码: " . $password . "\n";
echo "哈希值: " . $hash . "\n";

// 验证密码
if (password_verify($password, $hash)) {
    echo "密码验证成功！\n";
} else {
    echo "密码验证失败！\n";
}
?>