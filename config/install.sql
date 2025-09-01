-- 创建数据库
CREATE DATABASE IF NOT EXISTS repair_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE repair_management;

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100),  -- 添加中文名字字段
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- 创建维修记录表
CREATE TABLE IF NOT EXISTS repair_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_number VARCHAR(100) NOT NULL,
    device_model VARCHAR(100) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    device_belong ENUM('一期', '二期', '一期和二期','其它') NOT NULL,
    section ENUM('电窑', '配料', 'B工序', '粉碎', '包装', '后勤', '其它') NOT NULL,
    quantity INT DEFAULT 1,
    fault_description TEXT NOT NULL,
    received_date DATE NOT NULL,
    status ENUM('待维修', '未维修', '检修中', '已维修', '报废') DEFAULT '待维修',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    assigned_to VARCHAR(100),
    notes TEXT,
    completion_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at),
    INDEX idx_status (status),
    INDEX idx_device_number (device_number),
    INDEX idx_completion_time (completion_time),
    INDEX idx_status_date (status, received_date)
);

-- 插入默认管理员用户
INSERT INTO users (username, name, password, email) VALUES ('admin', '管理员', '$2y$10$71bfxXxG076XCqAQ30/xj.EOjSvWItP0w3RFLtD4iDac.vc8XDGeK', 'admin@example.com');
