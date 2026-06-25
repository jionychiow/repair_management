// 维修管理系统主要JavaScript功能
let statusChart, deviceTypeChart, belongChart, sectionChart;
let currentPage = 1;
let totalPages = 1;
let totalRecords = 0;
let chartCache = {}; // 添加图表缓存

// 主题颜色映射
const THEME_COLORS = {
    tech:     { primary: '#4f46e5', gradient: ['#4f46e5','#7c3aed','#818cf8','#a78bfa','#c4b5fd'] },
    warm:     { primary: '#ea580c', gradient: ['#ea580c','#f97316','#fb923c','#fdba74','#fed7aa'] },
    sunshine: { primary: '#d97706', gradient: ['#d97706','#f59e0b','#fbbf24','#fde68a','#fef3c7'] },
    girly:    { primary: '#db2777', gradient: ['#db2777','#ec4899','#f472b6','#f9a8d4','#fbcfe8'] },
    eyecare:  { primary: '#4d7c0f', gradient: ['#4d7c0f','#65a30d','#84cc16','#a3e635','#d9f99d'] }
};

function getCurrentTheme() {
    return localStorage.getItem('app-theme') || 'tech';
}

function getThemeColors() {
    return THEME_COLORS[getCurrentTheme()] || THEME_COLORS.tech;
}

// 图表通用颜色
const CHART_PALETTE = [
    'rgba(79, 70, 229, 0.85)',   // 靛蓝
    'rgba(16, 185, 129, 0.85)',  // 翡翠绿
    'rgba(245, 158, 11, 0.85)',  // 琥珀
    'rgba(239, 68, 68, 0.85)',   // 红色
    'rgba(6, 182, 212, 0.85)',   // 青色
    'rgba(168, 85, 247, 0.85)',  // 紫色
    'rgba(236, 72, 153, 0.85)',  // 粉色
    'rgba(34, 197, 94, 0.85)',   // 绿色
    'rgba(251, 146, 60, 0.85)',  // 橙色
    'rgba(99, 102, 241, 0.85)'   // 蓝紫
];

const STATUS_COLORS = {
    '待维修': { bg: 'rgba(245, 158, 11, 0.85)', border: '#f59e0b' },
    '未维修': { bg: 'rgba(148, 163, 184, 0.85)', border: '#94a3b8' },
    '检修中': { bg: 'rgba(6, 182, 212, 0.85)', border: '#06b6d4' },
    '已维修': { bg: 'rgba(16, 185, 129, 0.85)', border: '#10b981' },
    '报废':   { bg: 'rgba(239, 68, 68, 0.85)', border: '#ef4444' }
};

// ========== 自定义弹窗系统（替代 alert/confirm，避免浏览器拦截） ==========

// 自定义 Alert 弹窗
function CustomAlert(message, title = '提示') {
    return new Promise(resolve => {
        let modal = document.getElementById('customAlertDialog');
        if (!modal) {
            const html = `
            <style>
                #customAlertDialog .modal-content {
                    border: none;
                    border-radius: 16px;
                    box-shadow: 0 16px 48px rgba(0,0,0,0.2);
                    overflow: hidden;
                }
                #customAlertDialog .modal-header {
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    color: #fff;
                    border: none;
                    padding: 0.75rem 1.25rem;
                }
                #customAlertDialog .modal-title { font-size: 1rem; font-weight: 600; }
                #customAlertDialog .modal-body { padding: 1.25rem; font-size: 0.95rem; color: #1e293b; }
                #customAlertDialog .modal-footer { padding: 0.5rem 1.25rem; border-top: 1px solid #e2e8f0; }
                #customAlertDialog .btn-primary {
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    padding: 0.4rem 1.5rem;
                }
                #customConfirmDialog .modal-content {
                    border: none;
                    border-radius: 16px;
                    box-shadow: 0 16px 48px rgba(0,0,0,0.2);
                    overflow: hidden;
                }
                #customConfirmDialog .modal-header {
                    background: linear-gradient(135deg, #f59e0b, #fbbf24);
                    color: #1e293b;
                    border: none;
                    padding: 0.75rem 1.25rem;
                }
                #customConfirmDialog .modal-title { font-size: 1rem; font-weight: 600; }
                #customConfirmDialog .modal-body { padding: 1.25rem; font-size: 0.95rem; color: #1e293b; }
                #customConfirmDialog .modal-footer { padding: 0.5rem 1.25rem; border-top: 1px solid #e2e8f0; }
                #customConfirmDialog .btn-primary {
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    padding: 0.4rem 1.5rem;
                    color: #fff;
                }
                #customConfirmDialog .btn-secondary {
                    border-radius: 8px;
                    font-weight: 600;
                    padding: 0.4rem 1.5rem;
                }
            </style>
            <div class="modal fade" id="customAlertDialog" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header text-white py-1">
                            <h6 class="modal-title" id="customAlertTitle"><i class="bi bi-info-circle me-1"></i>${title}</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="transform:scale(0.8)"></button>
                        </div>
                        <div class="modal-body" id="customAlertBody"></div>
                        <div class="modal-footer py-1">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" id="customAlertOk"><i class="bi bi-check-lg"></i> 确定</button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            modal = document.getElementById('customAlertDialog');
        }
        document.getElementById('customAlertTitle').innerHTML = '<i class="bi bi-info-circle me-1"></i>' + title;
        document.getElementById('customAlertBody').textContent = message;
        const bsModal = new bootstrap.Modal(modal);
        const handler = () => { resolve(); };
        document.getElementById('customAlertOk').addEventListener('click', handler, { once: true });
        modal.addEventListener('hidden.bs.modal', () => {
            document.getElementById('customAlertOk').removeEventListener('click', handler);
            resolve();
        }, { once: true });
        bsModal.show();
    });
}

// 自定义 Confirm 弹窗
function CustomConfirm(message, title = '确认') {
    return new Promise(resolve => {
        let modal = document.getElementById('customConfirmDialog');
        if (!modal) {
            const html = `
            <div class="modal fade" id="customConfirmDialog" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-1">
                            <h6 class="modal-title" id="customConfirmTitle"><i class="bi bi-question-circle me-1"></i>${title}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="transform:scale(0.8)"></button>
                        </div>
                        <div class="modal-body" id="customConfirmBody"></div>
                        <div class="modal-footer py-1">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="customConfirmCancel"><i class="bi bi-x-lg"></i> 取消</button>
                            <button type="button" class="btn btn-primary btn-sm" id="customConfirmOk"><i class="bi bi-check-lg"></i> 确定</button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            modal = document.getElementById('customConfirmDialog');
        }
        document.getElementById('customConfirmTitle').innerHTML = '<i class="bi bi-question-circle me-1"></i>' + title;
        document.getElementById('customConfirmBody').textContent = message;
        const bsModal = new bootstrap.Modal(modal);
        let resolved = false;
        const onOk = () => { resolved = true; bsModal.hide(); resolve(true); };
        const onCancel = () => { if (!resolved) { resolved = true; resolve(false); } };
        document.getElementById('customConfirmOk').addEventListener('click', onOk, { once: true });
        document.getElementById('customConfirmCancel').addEventListener('click', onCancel, { once: true });
        modal.addEventListener('hidden.bs.modal', () => {
            if (!resolved) { resolved = true; resolve(false); }
            document.getElementById('customConfirmOk').removeEventListener('click', onOk);
            document.getElementById('customConfirmCancel').removeEventListener('click', onCancel);
        }, { once: true });
        bsModal.show();
    });
}

// ========== 自定义弹窗系统结束 ==========

// 加载统计数据
function loadStatistics() {
    // 检查缓存
    if (chartCache.statistics) {
        updateStatistics(chartCache.statistics);
        updateCharts(chartCache.statistics);
        return;
    }

    fetch('api/statistics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('网络响应错误: ' + response.status);
            }
            return response.text(); // 先获取文本内容
        })
        .then(text => {
            try {
                const data = JSON.parse(text); // 尝试解析JSON
                if (data.success) {
                    // 缓存数据
                    chartCache.statistics = data.data;
                    updateStatistics(data.data);
                    updateCharts(data.data);
                } else {
                    console.error('加载统计信息失败:', data.message);
                }
            } catch (e) {
                console.error('JSON解析错误:', e);
                console.error('服务器返回的原始内容:', text);
            }
        })
        .catch(error => {
            console.error('请求失败:', error);
        });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function () {
    loadRepairData();
    loadStatistics();
    initCharts();
    initPagination();
});

// 初始化分页控件
function initPagination() {
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'd-flex justify-content-between align-items-center mt-3';
    paginationContainer.id = 'paginationContainer';

    const paginationInfo = document.createElement('div');
    paginationInfo.className = 'text-muted';
    paginationInfo.id = 'paginationInfo';

    const paginationControls = document.createElement('div');
    paginationControls.className = 'btn-group';
    paginationControls.id = 'paginationControls';

    paginationContainer.appendChild(paginationInfo);
    paginationContainer.appendChild(paginationControls);

    // 插入到表格后面
    const tableCard = document.querySelector('#repairTable').closest('.card');
    tableCard.appendChild(paginationContainer);
}

// 加载维修记录数据
function loadRepairData(page = 1) {
    currentPage = page;

    const search = document.getElementById('searchDevice').value;
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (date) params.append('date', date);
    params.append('page', currentPage);
    params.append('limit', 20); // 每页20条记录

    fetch(`api/repairs.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRepairData(data.data);
                updatePagination(data.pagination);
            } else {
                console.error('加载数据失败:', data.message);
            }
        })
        .catch(error => {
            console.error('请求失败:', error);
        });
}

// 显示维修记录数据
function displayRepairData(repairs) {
    const tbody = document.getElementById('repairTableBody');
    tbody.innerHTML = '';

    if (repairs.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="12" class="text-center text-muted">暂无数据</td>';
        tbody.appendChild(row);
        return;
    }

    repairs.forEach(repair => {
        const row = document.createElement('tr');

        const statusText = {
            '待维修': '待维修',
            '未维修': '未维修',
            '检修中': '检修中',
            '已维修': '已维修',
            '报废': '报废'
        };

        const priorityText = {
            'high': '高',
            'medium': '中',
            'low': '低'
        };

        // 格式化完成时间显示
        let completionTimeDisplay = '-';
        if (repair.completion_time) {
            const completionTime = new Date(repair.completion_time);
            completionTimeDisplay = `<span class="completion-time">${completionTime.toLocaleDateString('zh-CN')} ${completionTime.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })}</span>`;
        } else if (repair.status === '已维修' || repair.status === '报废') {
            completionTimeDisplay = '<span class="no-completion-time">未记录</span>';
        }

        row.innerHTML = `
            <td>${repair.device_number}</td>
            <td>${repair.device_model}</td>
            <td>
                <span class="quantity-badge">${repair.quantity || 1}</span>
            </td>
            <td>${repair.fault_description}</td>
            <td>${repair.device_belong}</td>
            <td>${repair.section}</td>
            <td>${repair.received_date}</td>
            <td>
                <span class="badge status-badge" data-status="${repair.status}">
                    ${statusText[repair.status]}
                </span>
            </td>
            <td>
                <span class="priority-${repair.priority}">
                    ${priorityText[repair.priority]}
                </span>
            </td>
            <td>${repair.assigned_to || '-'}</td>
            <td>${completionTimeDisplay}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="viewRepair(${repair.id})" title="查看详情">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" 
                            onclick="editRepair(${repair.id})" title="编辑记录">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="deleteRepair(${repair.id})" title="删除记录">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="showStatusModal(${repair.id}, '${repair.status}')" title="更改状态">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" 
                            onclick="showCompletionTimeModal(${repair.id})" title="设置完成时间">
                        <i class="bi bi-clock"></i>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(row);
    });
}

// 更新分页控件
function updatePagination(pagination) {
    totalPages = pagination.total_pages;
    totalRecords = pagination.total_records;

    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    // 更新分页信息
    paginationInfo.innerHTML = `共 ${totalRecords} 条记录，第 ${currentPage} / ${totalPages} 页`;

    // 更新分页按钮
    paginationControls.innerHTML = '';

    // 上一页按钮
    if (pagination.has_prev) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'btn btn-outline-primary btn-sm';
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i> 上一页';
        prevBtn.onclick = () => loadRepairData(currentPage - 1);
        paginationControls.appendChild(prevBtn);
    }

    // 页码按钮
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = i === currentPage ? 'btn btn-primary btn-sm' : 'btn btn-outline-primary btn-sm';
        pageBtn.innerHTML = i;
        pageBtn.onclick = () => loadRepairData(i);
        paginationControls.appendChild(pageBtn);
    }

    // 下一页按钮
    if (pagination.has_next) {
        const nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn-outline-primary btn-sm';
        nextBtn.innerHTML = '下一页 <i class="bi bi-chevron-right"></i>';
        nextBtn.onclick = () => loadRepairData(currentPage + 1);
        paginationControls.appendChild(nextBtn);
    }
}

// 加载统计数据
function loadStatistics() {
    fetch('api/statistics.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('网络响应错误: ' + response.status);
            }
            return response.text(); // 先获取文本内容
        })
        .then(text => {
            try {
                const data = JSON.parse(text); // 尝试解析JSON
                if (data.success) {
                    updateStatistics(data.data);
                    updateCharts(data.data);
                } else {
                    console.error('加载统计信息失败:', data.message);
                }
            } catch (e) {
                console.error('JSON解析错误:', e);
                console.error('服务器返回的原始内容:', text);
            }
        })
        .catch(error => {
            console.error('请求失败:', error);
        });
}

// 更新统计卡片
function updateStatistics(stats) {
    // 更新总设备数
    document.getElementById('totalDevices').textContent = stats.total;
    
    // 从status_distribution数组中提取各状态的数量
    let statusCounts = {
        '已维修': 0,
        '检修中': 0,
        '报废': 0
    };
    
    // 遍历status_distribution数组，提取需要的状态数量
    stats.status_distribution.forEach(item => {
        if (item.status === '已维修') {
            statusCounts['已维修'] = item.count;
        } else if (item.status === '检修中') {
            statusCounts['检修中'] = item.count;
        } else if (item.status === '报废') {
            statusCounts['报废'] = item.count;
        }
    });
    
    // 更新已完成、检修中、无法修复的数量
    document.getElementById('completedRepairs').textContent = statusCounts['已维修'];
    document.getElementById('repairingCount').textContent = statusCounts['检修中'];
    document.getElementById('unrepairableCount').textContent = statusCounts['报废'];
}

// 初始化图表
function initCharts() {
    const tc = getThemeColors();

    // 维修状态分布饼图
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['待维修', '未维修', '检修中', '已维修', '报废'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: [
                    STATUS_COLORS['待维修'].bg,
                    STATUS_COLORS['未维修'].bg,
                    STATUS_COLORS['检修中'].bg,
                    STATUS_COLORS['已维修'].bg,
                    STATUS_COLORS['报废'].bg
                ],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12 } }
                }
            }
        }
    });

    // 设备属于分布饼图
    const belongCtx = document.getElementById('belongChart').getContext('2d');
    belongChart = new Chart(belongCtx, {
        type: 'doughnut',
        data: {
            labels: ['一期', '二期', '一期和二期', '其它'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: [CHART_PALETTE[0], CHART_PALETTE[1], CHART_PALETTE[2], CHART_PALETTE[3]],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12 } }
                }
            }
        }
    });

    // 工段分布柱状图
    const sectionCtx = document.getElementById('sectionChart').getContext('2d');
    sectionChart = new Chart(sectionCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: '设备数量',
                data: [],
                backgroundColor: CHART_PALETTE[0],
                borderColor: tc.primary,
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 设备类型分布饼图
    const deviceTypeCtx = document.getElementById('deviceTypeChart').getContext('2d');
    deviceTypeChart = new Chart(deviceTypeCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: CHART_PALETTE,
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12 } }
                }
            }
        }
    });
}

// 监听主题变化，更新图表颜色
window.addEventListener('themeChanged', function(e) {
    if (statusChart) {
        statusChart.update();
        belongChart.update();
        sectionChart.update();
        deviceTypeChart.update();
    }
});

// 更新图表数据
function updateCharts(stats) {
    // 更新状态分布图
    statusChart.data.datasets[0].data = [
        stats.status_counts['待维修'] || 0,
        stats.status_counts['未维修'] || 0,
        stats.status_counts['检修中'] || 0,
        stats.status_counts['已维修'] || 0,
        stats.status_counts['报废'] || 0
    ];
    statusChart.update();

    // 更新设备属于分布图
    const belongData = {};
    stats.device_belong_distribution.forEach(item => {
        belongData[item.device_belong] = item.count;
    });

    belongChart.data.datasets[0].data = [
        belongData['一期'] || 0,
        belongData['二期'] || 0,
        belongData['一期和二期'] || 0,
        belongData['其它'] || 0
    ];
    belongChart.update();

    // 更新工段分布图
    const sections = stats.section_distribution;
    sectionChart.data.labels = sections.map(item => item.section);
    sectionChart.data.datasets[0].data = sections.map(item => item.count);
    sectionChart.update();

    // 更新设备类型图
    const deviceTypes = stats.device_type_distribution;
    deviceTypeChart.data.labels = deviceTypes.map(item => item.device_type);
    deviceTypeChart.data.datasets[0].data = deviceTypes.map(item => item.count);
    deviceTypeChart.update();
}

// 快速筛选功能
function filterByStatus(status) {
    document.getElementById('statusFilter').value = status;
    currentPage = 1; // 重置到第一页
    loadRepairData(currentPage);
}

// 筛选没有完成时间的记录
function filterNoCompletionTime() {
    // 这里可以添加特殊的筛选逻辑
    // 暂时使用状态筛选，后续可以在API中添加专门的筛选
    currentPage = 1;
    loadRepairData(currentPage);
}

// 搜索维修记录
function searchRepairs() {
    currentPage = 1; // 重置到第一页
    loadRepairData(currentPage);
}

// 重置筛选条件
function resetFilters() {
    document.getElementById('searchDevice').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFilter').value = '';
    currentPage = 1; // 重置到第一页
    loadRepairData(currentPage);
}
// 删除维修记录
async function deleteRepair(id) {
    const confirmed = await CustomConfirm('确定要删除这条维修记录吗？此操作不可恢复！', '删除确认');
    if (!confirmed) {
        return;
    }

    fetch('api/repairs.php?id=' + id, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(async data => {
            if (data.success) {
                await CustomAlert('记录删除成功！', '成功');
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                await CustomAlert('删除失败：' + data.message, '错误');
            }
        })
        .catch(async error => {
            console.error('删除失败:', error);
            await CustomAlert('删除失败，请稍后重试', '错误');
        });
}

// 更新维修状态
async function updateStatus(id, newStatus) {
    const confirmed = await CustomConfirm('确定要更新维修状态吗？', '状态更新确认');
    if (!confirmed) {
        return;
    }

    fetch('api/repairs.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            status: newStatus
        })
    })
        .then(response => response.json())
        .then(async data => {
            if (data.success) {
                await CustomAlert('状态更新成功！', '成功');
                loadRepairData(currentPage); // 保持在当前页
                loadStatistics();
            } else {
                await CustomAlert('更新失败：' + data.message, '错误');
            }
        })
        .catch(async error => {
            console.error('更新失败:', error);
            await CustomAlert('更新失败，请稍后重试', '错误');
        });
}

// 导出数据
async function exportData(format) {
    const search = document.getElementById('searchDevice').value;
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (date) params.append('date', date);

    // 导出格式
    params.append('format', format);

    // 询问是否导出所有数据
    const exportAll = await CustomConfirm('选择导出范围：\n确定 = 导出所有匹配数据（可能较慢）\n取消 = 仅导出前1000条记录（推荐）', '导出范围');

    params.append('export_all', exportAll ? 'true' : 'false');

    if (exportAll && totalRecords > 1000) {
        const continueExport = await CustomConfirm(`当前有 ${totalRecords} 条记录，导出所有数据可能需要较长时间，确定继续吗？`, '大量数据导出');
        if (!continueExport) {
            return;
        }
    }

    window.open(`export.php?${params.toString()}`, '_blank');
}

/// 显示日期范围导出模态框
function showExportModal() {
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    // 设置默认开始日期为一个月前
    const oneMonthAgo = new Date();
    oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
    const oneMonthAgoStr = oneMonthAgo.toISOString().split('T')[0];

    document.getElementById('startDate').value = oneMonthAgoStr;
    document.getElementById('endDate').value = todayStr;

    // 显示模态框
    const exportModalElement = document.getElementById('exportModal');
    const exportModal = new bootstrap.Modal(exportModalElement);
    exportModal.show();

    // 在模态框完全显示后设置焦点
    exportModalElement.addEventListener('shown.bs.modal', function () {
        document.getElementById('startDate').focus();
    }, { once: true }); // 使用 once: true 确保事件监听器只触发一次
}

// 按日期范围导出数据
async function exportByDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const format = document.getElementById('exportFormat').value;
    const exportAll = document.getElementById('exportAll').checked;

    if (!startDate || !endDate) {
        await CustomAlert('请选择开始日期和结束日期', '提示');
        return;
    }

    // 检查日期范围是否有效
    if (new Date(startDate) > new Date(endDate)) {
        await CustomAlert('开始日期不能晚于结束日期', '提示');
        return;
    }

    const params = new URLSearchParams();
    params.append('start_date', startDate);
    params.append('end_date', endDate);
    params.append('format', format);
    params.append('export_all', exportAll ? 'true' : 'false');

    // 关闭模态框
    const exportModalElement = document.getElementById('exportModal');
    const exportModal = bootstrap.Modal.getInstance(exportModalElement);
    exportModal.hide();

    // 执行导出
    window.open(`export.php?${params.toString()}`, '_blank');

    // 模态框关闭后将焦点返回到导出按钮
    exportModalElement.addEventListener('hidden.bs.modal', function () {
        document.getElementById('exportDropdown').focus();
    }, { once: true }); // 使用 once: true 确保事件监听器只触发一次
}

// 更新维修状态
async function updateStatus(id, newStatus) {
    const confirmed = await CustomConfirm('确定要更新维修状态吗？', '状态更新确认');
    if (!confirmed) {
        return;
    }

    fetch('api/repairs.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            status: newStatus
        })
    })
        .then(response => response.json())
        .then(async data => {
            if (data.success) {
                await CustomAlert('状态更新成功！', '成功');
                loadRepairData(currentPage); // 保持在当前页
                loadStatistics();
            } else {
                await CustomAlert('更新失败：' + data.message, '错误');
            }
        })
        .catch(async error => {
            console.error('更新失败:', error);
            await CustomAlert('更新失败，请稍后重试', '错误');
        });
}

// 显示状态更改模态框
function showStatusModal(id, currentStatus) {
    const statusOptions = ['待维修', '未维修', '检修中', '已维修', '报废'];
    let optionsHtml = '';
    statusOptions.forEach(status => {
        const selected = status === currentStatus ? 'selected' : '';
        optionsHtml += `<option value="${status}" ${selected}>${status}</option>`;
    });

    const modalHtml = `
        <div class="modal fade" id="statusModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.2);overflow:hidden">
                    <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;padding:0.6rem 1rem">
                        <h6 class="modal-title" style="font-weight:600"><i class="bi bi-pencil me-1"></i>更改状态</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="transform:scale(0.8)"></button>
                    </div>
                    <div class="modal-body" style="padding:0.8rem 1rem">
                        <select class="form-select form-select-sm" id="newStatus">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="modal-footer" style="padding:0.4rem 1rem;border-top:1px solid #e2e8f0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px">取消</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="updateStatus(${id})" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;border-radius:8px;font-weight:600">确认</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('statusModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// 显示完成时间设置模态框
function showCompletionTimeModal(id) {
    const modalHtml = `
        <div class="modal fade" id="completionTimeModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.2);overflow:hidden">
                    <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;padding:0.6rem 1rem">
                        <h6 class="modal-title" style="font-weight:600"><i class="bi bi-clock me-1"></i>完成时间</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="transform:scale(0.8)"></button>
                    </div>
                    <div class="modal-body" style="padding:0.8rem 1rem">
                        <input type="datetime-local" class="form-control form-control-sm" id="completionTime">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="clearCompletionTime">
                            <label class="form-check-label" for="clearCompletionTime" style="font-size:0.85rem">清除完成时间</label>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding:0.4rem 1rem;border-top:1px solid #e2e8f0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px">取消</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="updateCompletionTime(${id})" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;border-radius:8px;font-weight:600">确认</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('completionTimeModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('completionTime').value = localDateTime;

    const modal = new bootstrap.Modal(document.getElementById('completionTimeModal'));
    modal.show();
}

// 更新维修状态
async function updateStatus(id) {
    const newStatus = document.getElementById('newStatus').value;

    fetch('api/repairs.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            status: newStatus
        })
    })
        .then(response => response.json())
        .then(async data => {
            if (data.success) {
                await CustomAlert('状态更新成功！', '成功');
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                modal.hide();
                // 刷新数据
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                await CustomAlert('更新失败：' + data.message, '错误');
            }
        })
        .catch(async error => {
            console.error('更新失败:', error);
            await CustomAlert('更新失败，请稍后重试', '错误');
        });
}

// 更新完成时间
async function updateCompletionTime(id) {
    const completionTime = document.getElementById('completionTime').value;
    const clearTime = document.getElementById('clearCompletionTime').checked;

    // 构建请求数据
    const requestData = {
        id: id
    };

    // 根据是否清除时间来设置completion_time字段
    if (clearTime) {
        requestData.completion_time = '__CLEAR__';  // 发送特殊标识表示清除
    } else if (completionTime) {
        requestData.completion_time = completionTime.replace('T', ' ') + ':00';
    } else {
        // 如果既没有设置时间也没有勾选清除，则不发送completion_time字段
        // 但这应该不会发生，因为模态框中默认设置了当前时间
    }

       fetch('api/repairs.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            await CustomAlert('完成时间设置成功！', '成功');
            // 关闭模态框
            const modal = bootstrap.Modal.getInstance(document.getElementById('completionTimeModal'));
            modal.hide();
            // 刷新数据
            loadRepairData(currentPage);
            loadStatistics();
        } else {
            await CustomAlert('设置失败：' + data.message, '错误');
        }
    })
    .catch(async error => {
        console.error('设置失败:', error);
        await CustomAlert('设置失败，请稍后重试', '错误');
    });
}

// 查看维修详情
function viewRepair(id) {
    // 跳转到详情页面
    window.location.href = 'view_repair.php?id=' + id;
}

// 编辑维修记录
function editRepair(id) {
    // 跳转到编辑页面
    window.location.href = 'edit_repair.php?id=' + id;
}

// 删除维修记录
async function deleteRepair(id) {
    const confirmed = await CustomConfirm('确定要删除这条维修记录吗？此操作不可恢复！', '删除确认');
    if (!confirmed) {
        return;
    }

    fetch('api/repairs.php?id=' + id, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(async data => {
            if (data.success) {
                await CustomAlert('记录删除成功！', '成功');
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                await CustomAlert('删除失败：' + data.message, '错误');
            }
        })
        .catch(async error => {
            console.error('删除失败:', error);
            await CustomAlert('删除失败，请稍后重试', '错误');
        });
}

// ========== 重置数据功能（双重认证 + 自动备份） ==========
function showResetDataModal() {
    const modalHtml = `
    <div class="modal fade" id="resetDataModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.2);overflow:hidden">
                <div class="modal-header" style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;padding:0.7rem 1.25rem">
                    <h6 class="modal-title" style="font-weight:700"><i class="bi bi-exclamation-triangle me-1"></i>重置数据</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="transform:scale(0.8)"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem">
                    <div class="alert alert-danger mb-3" style="border-radius:10px;font-size:0.88rem;padding:0.6rem 0.8rem">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>危险操作！</strong>此操作将清空所有维修记录，用户账号将保留。系统会自动备份数据。
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.85rem">
                            <i class="bi bi-lock me-1"></i>第一步：输入登录密码
                        </label>
                        <input type="password" class="form-control form-control-sm" id="resetPassword" placeholder="请输入当前登录密码">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-weight:600;font-size:0.85rem">
                            <i class="bi bi-shield-check me-1"></i>第二步：输入确认文本
                        </label>
                        <input type="text" class="form-control form-control-sm" id="resetConfirmText" placeholder='请输入"确认重置数据"'>
                        <div class="form-text" style="font-size:0.78rem;margin-top:4px">
                            请输入 <code style="color:#dc2626;font-weight:700">确认重置数据</code> 以确认操作
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:0.5rem 1.25rem;border-top:1px solid #e2e8f0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px;font-weight:600">
                        <i class="bi bi-x-lg me-1"></i>取消
                    </button>
                    <button type="button" class="btn btn-sm" id="executeResetBtn" onclick="executeResetData()" disabled
                        style="background:linear-gradient(135deg,#dc2626,#ef4444);border:none;border-radius:8px;font-weight:600;color:#fff;opacity:0.5">
                        <i class="bi bi-trash me-1"></i>确认重置
                    </button>
                </div>
            </div>
        </div>
    </div>`;

    const existing = document.getElementById('resetDataModal');
    if (existing) existing.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // 监听确认文本输入，匹配后启用按钮
    const confirmInput = document.getElementById('resetConfirmText');
    const resetBtn = document.getElementById('executeResetBtn');
    confirmInput.addEventListener('input', function() {
        const matched = this.value === '确认重置数据' && document.getElementById('resetPassword').value.length > 0;
        resetBtn.disabled = !matched;
        resetBtn.style.opacity = matched ? '1' : '0.5';
    });
    document.getElementById('resetPassword').addEventListener('input', function() {
        const matched = confirmInput.value === '确认重置数据' && this.value.length > 0;
        resetBtn.disabled = !matched;
        resetBtn.style.opacity = matched ? '1' : '0.5';
    });

    const modal = new bootstrap.Modal(document.getElementById('resetDataModal'));
    modal.show();
}

async function executeResetData() {
    const password = document.getElementById('resetPassword').value;
    const confirmText = document.getElementById('resetConfirmText').value;

    if (!password) {
        await CustomAlert('请输入登录密码', '提示');
        return;
    }
    if (confirmText !== '确认重置数据') {
        await CustomAlert('确认文本不匹配', '提示');
        return;
    }

    const resetBtn = document.getElementById('executeResetBtn');
    resetBtn.disabled = true;
    resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>处理中...';

    try {
        const response = await fetch('api/reset_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password, confirm_text: confirmText })
        });
        const data = await response.json();

        if (data.success) {
            const resetModal = bootstrap.Modal.getInstance(document.getElementById('resetDataModal'));
            resetModal.hide();
            await CustomAlert(
                `数据重置成功！\n\n备份文件：${data.backup_file}\n备份大小：${data.backup_size}\n\n维修记录已清空，用户账号已保留。`,
                '重置完成'
            );
            loadRepairData(1);
            loadStatistics();
        } else {
            await CustomAlert(data.message, '重置失败');
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="bi bi-trash me-1"></i>确认重置';
            resetBtn.style.opacity = '1';
        }
    } catch (error) {
        console.error('重置失败:', error);
        await CustomAlert('重置失败，请稍后重试', '错误');
        resetBtn.disabled = false;
        resetBtn.innerHTML = '<i class="bi bi-trash me-1"></i>确认重置';
        resetBtn.style.opacity = '1';
    }
}