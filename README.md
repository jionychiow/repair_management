# 维修管理系统 (Repair Management System)

一个基于PHP的维修管理系统，支持 MySQL/SQLite 双数据库、5种主题切换、设备维修记录管理、状态跟踪、数据导出和统计图表等功能。

## 功能特性

### 核心功能
- **设备接收管理**：记录设备型号、故障描述、客户信息
- **维修状态跟踪**：待维修 → 检修中 → 已维修 / 报废
- **数据导出**：支持CSV和Excel格式导出
- **统计图表**：维修状态分布、设备类型占比、工段分布等可视化数据
- **数据库迁移**：支持 MySQL ↔ SQLite 双向迁移

### 主题系统
系统内置5种精美主题，右下角调色板按钮一键切换：
- **科技蓝**（默认）：靛蓝紫渐变，现代科技感
- **温馨橙**：暖橙渐变，温馨舒适
- **阳光金**：金黄渐变，明亮活泼
- **少女粉**：粉色渐变，甜美可爱
- **护眼绿**：绿色渐变，长时间使用不疲劳

### 用户权限
- **无需登录**：查看所有设备维修信息
- **需要登录**：添加、修改维修记录（管理员权限）

### 数据管理
- 维修工单生成与管理
- 实时状态更新（自动记录更新时间）
- 维修进度跟踪
- 历史记录查询与筛选
- 分页浏览（避免大数据量卡顿）
- 按维修编号数字排序

## 技术架构

- **后端**：PHP 7.4+
- **数据库**：MySQL 5.7+ / SQLite 3（双数据库支持）
- **前端**：Bootstrap 5 + Chart.js + Bootstrap Icons
- **架构**：MVC模式，RESTful API
- **主题**：CSS自定义属性实现主题切换

## 安装部署

### 环境要求
- PHP 7.4 或更高版本（需开启 SQLite3 扩展以使用 SQLite）
- MySQL 5.7 或更高版本（可选，也可使用 SQLite）
- Web服务器（Apache/Nginx）

### 一键自动安装

1. **下载项目文件**
   ```bash
   git clone [项目地址]
   cd repair-management-system
   ```

2. **上传到Web服务器**
   - 将项目文件放到Web服务器目录
   - 确保PHP有写入权限

3. **运行自动安装**
   - 打开浏览器访问：`http://您的域名/install.php`
   - 按照安装向导的步骤操作：
     - 步骤1：环境检查
     - 步骤2：数据库配置（支持 MySQL 或 SQLite）
     - 步骤3：系统安装
     - 步骤4：安装完成

4. **开始使用**
   - 安装完成后访问：`http://您的域名/index.php`
   - 默认管理员账户：`admin` / `admin123`

### 数据库迁移

访问 `migrate_db.php` 可在 MySQL 和 SQLite 之间双向迁移数据：
- 选择迁移方向（MySQL → SQLite 或 SQLite → MySQL）
- 测试源数据库和目标数据库连接
- 一键迁移所有数据

## 使用说明

### 查看维修记录
- 访问首页可查看所有维修记录
- 使用搜索和筛选功能快速查找
- 按维修编号数字排序

### 添加维修记录
1. 点击"登录"按钮
2. 使用管理员账户登录
3. 点击"添加维修记录"
4. 填写设备信息和客户信息
5. 保存记录

### 更新维修状态
- 在维修记录表格中点击状态按钮
- 支持状态流转：待维修 → 检修中 → 已维修/报废

### 切换主题
- 点击右下角调色板按钮
- 选择喜欢的主题
- 主题选择自动保存到浏览器

### 数据导出
- 点击"导出数据"按钮
- 选择CSV或Excel格式
- 支持按筛选条件导出

## 数据库结构

### 用户表 (users)
- id: 用户ID
- username: 用户名
- password: 密码（加密存储）
- email: 邮箱
- created_at: 创建时间

### 维修记录表 (repair_records)
- id: 记录ID
- device_number: 维修编号
- device_model: 设备型号
- device_type: 设备类型
- fault_description: 故障描述
- customer_name: 客户姓名
- customer_phone: 客户电话
- customer_address: 客户地址
- received_date: 接收日期
- status: 维修状态
- priority: 优先级
- assigned_to: 负责人
- notes: 备注
- created_at: 创建时间
- updated_at: 更新时间

## 文件结构

```
repair-management-system/
├── install.php              # 自动安装向导
├── index.php                # 主页面
├── login.php                # 登录页面
├── add_repair.php           # 添加维修记录
├── edit_repair.php          # 编辑维修记录
├── view_repair.php          # 查看维修详情
├── export.php               # 数据导出
├── migrate_db.php           # 数据库迁移工具
├── change_password.php      # 修改密码
├── logout.php               # 退出登录
├── config/
│   └── database.php         # 数据库配置
├── api/
│   ├── repairs.php          # 维修记录API
│   └── statistics.php       # 统计信息API
├── css/
│   └── themes.css           # 主题样式系统
├── js/
│   ├── main.js              # 主要JavaScript功能
│   └── theme-switcher.js    # 主题切换器
├── data/                    # SQLite数据库目录
└── README.md                # 项目说明
```

## 常见问题

### Q: 无法连接数据库？
A: 检查数据库配置信息，确保MySQL服务正在运行，或切换到SQLite模式

### Q: 导出功能不工作？
A: 确保PHP有写入权限，检查浏览器是否阻止弹窗

### Q: 图表不显示？
A: 检查Chart.js是否正确加载，查看浏览器控制台错误信息

### Q: 如何切换数据库？
A: 访问 migrate_db.php 进行 MySQL ↔ SQLite 双向迁移

## 许可证

本项目采用MIT许可证，详见LICENSE文件。

---

**注意**：首次使用请务必修改默认管理员密码！
