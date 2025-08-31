<?php
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 安全头部设置
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, max-age=30'); 
require_once '../config/database.php';
require_once '../auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

switch ($method) {
    case 'GET':
        // 检查是否是请求生成设备编号
        if (isset($_GET['action']) && $_GET['action'] === 'generate_device_number') {
            try {
                // 生成新的设备编号
                $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM repair_records");
                $stmt->execute();
                $result = $stmt->fetch();
                $new_id = ($result['max_id'] ?? 0) + 1;
                $device_number = 'WX-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
                
                echo json_encode(['success' => true, 'device_number' => $device_number]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => '生成设备编号失败']);
                exit;
            }
        }
        // 获取维修记录列表（支持分页）
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $date = $_GET['date'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20))); // 限制每页记录数，避免卡顿
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(device_number LIKE ? OR device_model LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam]);
        }

        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if (!empty($date)) {
            $where[] = "received_date = ?";
            $params[] = $date;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // 先获取总数
        $countSql = "SELECT COUNT(*) as total FROM repair_records $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // 获取分页数据，按时间倒序排列
        $sql = "SELECT * FROM repair_records $whereClause ORDER BY created_at DESC, updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repairs = $stmt->fetchAll();

        // 计算分页信息
        $totalPages = ceil($total / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        echo json_encode([
            'success' => true,
            'data' => $repairs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $total,
                'per_page' => $limit,
                'has_next' => $hasNext,
                'has_prev' => $hasPrev
            ]
        ]);
        break;

    case 'POST':
        // 添加新维修记录
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO repair_records (
                    device_number, device_model, device_type, device_belong, section, quantity, fault_description,
                    received_date, priority, assigned_to, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['device_number'],
                $data['device_model'],
                $data['device_type'],
                $data['device_belong'],
                $data['section'],
                $data['quantity'] ?? 1,
                $data['fault_description'],
                $data['received_date'],
                $data['priority'],
                $data['assigned_to'],
                $data['notes']
            ]);

            echo json_encode(['success' => true, 'message' => '记录添加成功']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // 更新维修记录状态或完成时间
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $completionTime = $data['completion_time'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少记录ID']);
            break;
        }

        try {
            if ($status !== null && $completionTime !== null) {
                // 同时更新状态和完成时间
                $stmt = $pdo->prepare("UPDATE repair_records SET status = ?, completion_time = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $completionTime, $id]);
                echo json_encode(['success' => true, 'message' => '状态和完成时间更新成功']);
            } elseif ($status !== null) {
                // 只更新状态
                $stmt = $pdo->prepare("UPDATE repair_records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $id]);
                echo json_encode(['success' => true, 'message' => '状态更新成功']);
            } elseif ($completionTime !== null) {
                // 只更新完成时间
                $stmt = $pdo->prepare("UPDATE repair_records SET completion_time = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$completionTime, $id]);
                echo json_encode(['success' => true, 'message' => '完成时间更新成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '缺少更新参数']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // 删除维修记录
        requireAuth();
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少记录ID']);
            break;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM repair_records WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => '记录删除成功']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
        break;
}
?>