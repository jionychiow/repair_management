# 维修管理系统 - 快速安装指南

## 一键自动安装

### 第一步：环境检查
确保您的服务器满足以下要求：
- PHP 7.4 或更高版本（需开启 SQLite3 扩展）
- MySQL 5.7 或更高版本（可选，也可使用 SQLite）
- Web服务器（Apache/Nginx）

### 第二步：下载项目
```bash
# 方法1：直接下载ZIP文件
# 解压到Web服务器目录

# 方法2：使用Git克隆
git clone [项目地址]
cd repair-management-system
```

### 第三步：上传到Web服务器
1. 将项目文件放到Web服务器目录
2. 确保PHP有写入权限

### 第四步：运行自动安装
1. 打开浏览器访问：`http://您的域名/install.php`
2. 按照安装向导的步骤操作：
   - **步骤1：环境检查** - 系统自动检查PHP版本、扩展等
   - **步骤2：数据库配置** - 选择 MySQL 或 SQLite
   - **步骤3：系统安装** - 自动创建数据库和表
   - **步骤4：安装完成** - 显示成功信息和默认账户

### 第五步：开始使用
1. 安装完成后访问：`http://您的域名/index.php`
2. 使用默认账户登录：
   - 用户名：`admin`
   - 密码：`admin123`

## 数据库选择

### MySQL 模式
- 适合生产环境、多用户并发场景
- 需要单独安装和配置 MySQL 服务
- 支持更复杂的数据查询和索引

### SQLite 模式
- 适合小型团队、单机部署
- 无需额外安装数据库服务
- 数据库文件位于 `data/repair_management.db`
- 零配置，开箱即用

### 数据库迁移
访问 `migrate_db.php` 可在两种数据库之间双向迁移：
1. 选择迁移方向（MySQL → SQLite 或 SQLite → MySQL）
2. 测试源数据库和目标数据库连接
3. 点击迁移按钮，一键完成数据迁移

## 详细配置说明

### 数据库配置（MySQL模式）
在安装向导中填写以下信息：
- **数据库主机**：通常是 `localhost`
- **端口**：MySQL默认端口 `3306`
- **数据库名称**：建议使用 `repair_management`
- **用户名**：MySQL用户名，通常是 `root`
- **密码**：MySQL用户密码

### Web服务器配置

#### Apache配置
在 `.htaccess` 文件中添加：
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx配置
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 权限设置
确保以下目录有写入权限：
```bash
chmod 755 .
chmod 755 config/
chmod 755 api/
chmod 755 data/
```

## 安全建议

### 生产环境部署
1. **修改默认密码**：首次登录后立即修改admin密码
2. **数据库安全**：使用强密码，限制数据库访问IP
3. **文件权限**：设置最小必要权限
4. **HTTPS**：启用SSL证书
5. **防火墙**：配置Web服务器防火墙规则
6. **删除安装文件**：安装完成后删除 `install.php` 文件

### 定期维护
1. 定期备份数据库
2. 更新PHP和MySQL版本
3. 监控系统日志
4. 定期检查安全漏洞

---

**安装完成后，请务必修改默认管理员密码！**
