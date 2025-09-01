// 维修管理系统主要JavaScript功能
let statusChart, deviceTypeChart, belongChart, sectionChart;
let currentPage = 1;
let totalPages = 1;
let totalRecords = 0;
let chartCache = {}; // 添加图表缓存

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
    document.getElementById('totalDevices').textContent = stats.total;
    document.getElementById('completedRepairs').textContent = stats.status_counts.completed;
    document.getElementById('repairingCount').textContent = stats.status_counts.repairing;
    document.getElementById('unrepairableCount').textContent = stats.status_counts.unrepairable;
}

// 初始化图表
function initCharts() {
    // 维修状态分布饼图
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['待维修', '未维修', '检修中', '已维修', '报废'],
            datasets: [{
                data: [0, 0, 0, 0, 0],
                backgroundColor: ['#ffc107', '#6c757d', '#17a2b8', '#28a745', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // 设备属于分布饼图
    const belongCtx = document.getElementById('belongChart').getContext('2d');
    belongChart = new Chart(belongCtx, {
        type: 'pie',
        data: {
            labels: ['一期', '二期', '一期和二期', '其它'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#007bff', '#28a745', '#ffc107'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
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
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // 设备类型分布饼图
    const deviceTypeCtx = document.getElementById('deviceTypeChart').getContext('2d');
    deviceTypeChart = new Chart(deviceTypeCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

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
function deleteRepair(id) {
    if (!confirm('确定要删除这条维修记录吗？此操作不可恢复！')) {
        return;
    }

    fetch('api/repairs.php?id=' + id, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('记录删除成功！');
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('删除失败:', error);
            alert('删除失败，请稍后重试');
        });
}

// 更新维修状态
function updateStatus(id, newStatus) {
    if (!confirm('确定要更新维修状态吗？')) {
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
        .then(data => {
            if (data.success) {
                alert('状态更新成功！');
                loadRepairData(currentPage); // 保持在当前页
                loadStatistics();
            } else {
                alert('更新失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('更新失败:', error);
            alert('更新失败，请稍后重试');
        });
}

// 导出数据
function exportData(format) {
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
    const exportAll = confirm('选择导出范围：\n确定 = 导出所有匹配数据（可能较慢）\n取消 = 仅导出前1000条记录（推荐）');

    params.append('export_all', exportAll ? 'true' : 'false');

    if (exportAll && totalRecords > 1000) {
        if (!confirm(`当前有 ${totalRecords} 条记录，导出所有数据可能需要较长时间，确定继续吗？`)) {
            return;
        }
    }

    window.open(`export.php?${params.toString()}`, '_blank');
}

/// 显示日期范围导出模态框
function showExportModal() {
    console.log('showExportModal function called');
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
function exportByDateRange() {
    console.log('exportByDateRange function called');
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const format = document.getElementById('exportFormat').value;
    const exportAll = document.getElementById('exportAll').checked;

    if (!startDate || !endDate) {
        alert('请选择开始日期和结束日期');
        return;
    }

    // 检查日期范围是否有效
    if (new Date(startDate) > new Date(endDate)) {
        alert('开始日期不能晚于结束日期');
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
function updateStatus(id, newStatus) {
    if (!confirm('确定要更新维修状态吗？')) {
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
        .then(data => {
            if (data.success) {
                alert('状态更新成功！');
                loadRepairData(currentPage); // 保持在当前页
                loadStatistics();
            } else {
                alert('更新失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('更新失败:', error);
            alert('更新失败，请稍后重试');
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">更改维修状态</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">选择新状态</label>
                            <select class="form-select" id="newStatus">
                                ${optionsHtml}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" onclick="updateStatus(${id})">确认更改</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // 移除已存在的模态框
    const existingModal = document.getElementById('statusModal');
    if (existingModal) {
        existingModal.remove();
    }

    // 添加新模态框到页面
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // 显示模态框
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// 显示完成时间设置模态框
function showCompletionTimeModal(id) {
    const modalHtml = `
        <div class="modal fade" id="completionTimeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">设置维修完成时间</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="completionTime" class="form-label">完成时间</label>
                            <input type="datetime-local" class="form-control" id="completionTime">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="clearCompletionTime">
                            <label class="form-check-label" for="clearCompletionTime">
                                清除完成时间
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" onclick="updateCompletionTime(${id})">确认设置</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // 移除已存在的模态框
    const existingModal = document.getElementById('completionTimeModal');
    if (existingModal) {
        existingModal.remove();
    }

    // 添加新模态框到页面
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // 设置当前时间作为默认值
    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('completionTime').value = localDateTime;

    // 显示模态框
    const modal = new bootstrap.Modal(document.getElementById('completionTimeModal'));
    modal.show();
}

// 更新维修状态
function updateStatus(id) {
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
        .then(data => {
            if (data.success) {
                alert('状态更新成功！');
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                modal.hide();
                // 刷新数据
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                alert('更新失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('更新失败:', error);
            alert('更新失败，请稍后重试');
        });
}

// 更新完成时间
function updateCompletionTime(id) {
    const completionTime = document.getElementById('completionTime').value;
    const clearTime = document.getElementById('clearCompletionTime').checked;

    let timeValue = null;
    if (!clearTime && completionTime) {
        timeValue = completionTime.replace('T', ' ') + ':00';
    }

    fetch('api/repairs.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            completion_time: timeValue
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('完成时间设置成功！');
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('completionTimeModal'));
                modal.hide();
                // 刷新数据
                loadRepairData(currentPage);
            } else {
                alert('设置失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('设置失败:', error);
            alert('设置失败，请稍后重试');
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
function deleteRepair(id) {
    if (!confirm('确定要删除这条维修记录吗？此操作不可恢复！')) {
        return;
    }

    fetch('api/repairs.php?id=' + id, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('记录删除成功！');
                loadRepairData(currentPage);
                loadStatistics();
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('删除失败:', error);
            alert('删除失败，请稍后重试');
        });
}