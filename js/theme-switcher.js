/**
 * 主题切换器
 * 支持：科技、温馨、阳光、少女、护眼
 */
(function() {
    const THEMES = {
        'tech':    { name: '科技蓝', dot: 'linear-gradient(135deg, #4f46e5, #7c3aed)', icon: 'bi-cpu' },
        'warm':    { name: '温馨橙', dot: 'linear-gradient(135deg, #ea580c, #f97316)', icon: 'bi-fire' },
        'sunshine':{ name: '阳光金', dot: 'linear-gradient(135deg, #d97706, #fbbf24)', icon: 'bi-sun' },
        'girly':   { name: '少女粉', dot: 'linear-gradient(135deg, #db2777, #f472b6)', icon: 'bi-heart' },
        'eyecare': { name: '护眼绿', dot: 'linear-gradient(135deg, #4d7c0f, #84cc16)', icon: 'bi-leaf' }
    };

    function getCurrentTheme() {
        return localStorage.getItem('app-theme') || 'tech';
    }

    function setTheme(theme) {
        if (theme === 'tech') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
        localStorage.setItem('app-theme', theme);
        updateActiveState(theme);
        // 触发自定义事件，让图表更新颜色
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    function updateActiveState(theme) {
        document.querySelectorAll('.theme-switcher .dropdown-item').forEach(item => {
            item.classList.toggle('active', item.dataset.theme === theme);
        });
    }

    function createSwitcher() {
        const html = `
        <div class="theme-switcher">
            <div class="dropdown dropup">
                <button class="theme-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="切换主题">
                    <i class="bi bi-palette"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    ${Object.entries(THEMES).map(([key, val]) => `
                    <li><a class="dropdown-item" href="#" data-theme="${key}">
                        <span class="theme-dot" style="background: ${val.dot}"></span>
                        <i class="bi ${val.icon}"></i> ${val.name}
                    </a></li>
                    `).join('')}
                </ul>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', html);

        // 绑定点击事件
        document.querySelectorAll('.theme-switcher .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                setTheme(this.dataset.theme);
            });
        });
    }

    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
        createSwitcher();
        setTheme(getCurrentTheme());
    });
})();
