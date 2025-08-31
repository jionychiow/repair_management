<?php
header('Content-Type: application/json');

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => '无效的请求数据']);
    exit;
}

$host = $input['host'] ?? '';
$port = $input['port'] ?? '3306';
$user = $input['user'] ?? '';
$pass = $input['pass'] ?? '';

if (empty($host) || empty($user)) {
    echo json_encode(['success' => false, 'message' => '主机地址和用户名不能为空']);
    exit;
}

try {
    // 尝试连接到MySQL服务器
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 测试连接是否有效
    $pdo->query('SELECT 1');
    
    // 获取MySQL版本信息
    $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
    
    echo json_encode([
        'success' => true, 
        'message' => "连接成功！MySQL版本：{$version}",
        'version' => $version
    ]);
    
} catch (PDOException $e) {
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    // 根据错误代码提供更友好的错误信息
    $friendlyMessage = '';
    switch ($errorCode) {
        case 2002:
            $friendlyMessage = '无法连接到MySQL服务器，请检查主机地址和端口号';
            break;
        case 2003:
            $friendlyMessage = '无法连接到MySQL服务器，请检查MySQL服务是否启动';
            break;
        case 1045:
            $friendlyMessage = '用户名或密码错误，请检查登录凭据';
            break;
        case 1049:
            $friendlyMessage = '数据库不存在，但这不影响连接测试';
            break;
        default:
            $friendlyMessage = '数据库连接失败';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $friendlyMessage . "\n\n详细错误：" . $errorMessage,
        'error_code' => $errorCode
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '未知错误：' . $e->getMessage()
    ]);
}
?>
