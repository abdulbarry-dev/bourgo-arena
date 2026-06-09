import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const colors = {
    blue: { light: '#3b82f6', dark: '#60a5fa' },
    emerald: { light: '#10b981', dark: '#34d399' },
    amber: { light: '#f59e0b', dark: '#fbbf24' },
    rose: { light: '#f43f5e', dark: '#fb7185' },
    violet: { light: '#8b5cf6', dark: '#a78bfa' },
    indigo: { light: '#6366f1', dark: '#818cf8' },
    cyan: { light: '#06b6d4', dark: '#22d3ee' },
    zinc: { light: '#71717a', dark: '#a1a1aa' },
    grid: { light: '#e4e4e7', dark: '#3f3f46' },
};

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function getColor(name) {
    return isDark() ? colors[name].dark : colors[name].light;
}

Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = getColor('zinc');
Chart.defaults.plugins.legend.labels.usePointStyle = true;

function updateChartTheme(chart) {
    const dark = isDark();
    if (chart.options.scales) {
        Object.values(chart.options.scales).forEach((scale) => {
            if (scale.grid) {
                scale.grid.color = dark ? colors.grid.dark : colors.grid.light;
            }
            if (scale.ticks) {
                scale.ticks.color = dark ? colors.zinc.dark : colors.zinc.light;
            }
        });
    }
    if (chart.options.plugins?.legend?.labels) {
        chart.options.plugins.legend.labels.color = dark ? colors.zinc.dark : colors.zinc.light;
    }
    chart.update('none');
}

let activeCharts = [];

export function createLineChart(canvas, data, options = {}) {
    const dark = isDark();
    const gradient = canvas.parentElement
        ? canvas.getContext('2d').createLinearGradient(0, 0, 0, 300)
        : null;
    if (gradient) {
        gradient.addColorStop(0, dark ? 'rgba(96, 165, 250, 0.25)' : 'rgba(59, 130, 246, 0.2)');
        gradient.addColorStop(1, dark ? 'rgba(96, 165, 250, 0)' : 'rgba(59, 130, 246, 0)');
    }

    const config = {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label || 'Revenue',
                data: data.values,
                borderColor: getColor('blue'),
                backgroundColor: gradient || getColor('blue'),
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: getColor('blue'),
                pointBorderColor: dark ? '#27272a' : '#ffffff',
                pointBorderWidth: 2,
                borderWidth: 2.5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart',
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: dark ? '#27272a' : '#ffffff',
                    titleColor: dark ? '#f4f4f5' : '#18181b',
                    bodyColor: dark ? '#a1a1aa' : '#71717a',
                    borderColor: dark ? '#3f3f46' : '#e4e4e7',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 4,
                },
            },
            scales: {
                x: {
                    grid: { color: dark ? colors.grid.dark : colors.grid.light, drawBorder: false },
                    ticks: { color: dark ? colors.zinc.dark : colors.zinc.light, maxTicksLimit: 8 },
                },
                y: {
                    grid: { color: dark ? colors.grid.dark : colors.grid.light, drawBorder: false },
                    ticks: {
                        color: dark ? colors.zinc.dark : colors.zinc.light,
                        callback: (v) => '$' + v.toLocaleString(),
                    },
                    beginAtZero: true,
                },
            },
        },
    };

    const chart = new Chart(canvas, config);
    activeCharts.push(chart);
    return chart;
}

export function createDoughnutChart(canvas, data, options = {}) {
    const defaultColors = [
        getColor('blue'),
        getColor('rose'),
        getColor('violet'),
        getColor('amber'),
    ];

    const config = {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: data.colors || defaultColors,
                borderColor: isDark() ? '#27272a' : '#ffffff',
                borderWidth: 3,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            animation: {
                animateRotate: true,
                duration: 800,
                easing: 'easeInOutQuart',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        color: isDark() ? colors.zinc.dark : colors.zinc.light,
                    },
                },
                tooltip: {
                    backgroundColor: isDark() ? '#27272a' : '#ffffff',
                    titleColor: isDark() ? '#f4f4f5' : '#18181b',
                    bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                    borderColor: isDark() ? '#3f3f46' : '#e4e4e7',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((ctx.parsed / total) * 100).toFixed(1);
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        },
                    },
                },
            },
        },
        plugins: [{
            id: 'centerText',
            beforeDraw(chart) {
                const { width, height, ctx } = chart;
                ctx.save();
                const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                ctx.font = '700 28px Inter, system-ui, sans-serif';
                ctx.fillStyle = isDark() ? '#f4f4f5' : '#18181b';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(total, width / 2, height / 2 - 6);
                ctx.font = '12px Inter, system-ui, sans-serif';
                ctx.fillStyle = isDark() ? '#a1a1aa' : '#71717a';
                ctx.fillText('Total', width / 2, height / 2 + 18);
                ctx.restore();
            },
        }],
    };

    const chart = new Chart(canvas, config);
    activeCharts.push(chart);
    return chart;
}

export function createBarChart(canvas, data, options = {}) {
    const defaultColors = data.colors || data.values.map((_, i) => {
        const palette = [getColor('blue'), getColor('emerald'), getColor('violet'), getColor('indigo'), getColor('cyan')];
        return palette[i % palette.length];
    });

    const isHorizontal = options.orientation === 'horizontal';

    const config = {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: data.label || '',
                data: data.values,
                backgroundColor: defaultColors,
                borderColor: isDark() ? '#27272a' : '#ffffff',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.65,
                categoryPercentage: 0.8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: isHorizontal ? 'y' : 'x',
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart',
                ...(data.values.length > 3 ? {
                    delay(ctx) {
                        return ctx.dataIndex * 100;
                    },
                } : {}),
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark() ? '#27272a' : '#ffffff',
                    titleColor: isDark() ? '#f4f4f5' : '#18181b',
                    bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                    borderColor: isDark() ? '#3f3f46' : '#e4e4e7',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { color: isDark() ? colors.grid.dark : colors.grid.light, drawBorder: false },
                    ticks: { color: isDark() ? colors.zinc.dark : colors.zinc.light },
                },
                y: {
                    grid: { color: isDark() ? colors.grid.dark : colors.grid.light, drawBorder: false },
                    ticks: { color: isDark() ? colors.zinc.dark : colors.zinc.light },
                    beginAtZero: true,
                },
            },
        },
    };

    const chart = new Chart(canvas, config);
    activeCharts.push(chart);
    return chart;
}

export function createPieChart(canvas, data, options = {}) {
    const defaultColors = [
        getColor('blue'), getColor('emerald'), getColor('amber'),
        getColor('violet'), getColor('rose'), getColor('cyan'),
        getColor('indigo'),
    ];

    const config = {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: data.colors || defaultColors,
                borderColor: isDark() ? '#27272a' : '#ffffff',
                borderWidth: 2,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateRotate: true,
                duration: 800,
                easing: 'easeInOutQuart',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 14,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        color: isDark() ? colors.zinc.dark : colors.zinc.light,
                    },
                },
                tooltip: {
                    backgroundColor: isDark() ? '#27272a' : '#ffffff',
                    titleColor: isDark() ? '#f4f4f5' : '#18181b',
                    bodyColor: isDark() ? '#a1a1aa' : '#71717a',
                    borderColor: isDark() ? '#3f3f46' : '#e4e4e7',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((ctx.parsed / total) * 100).toFixed(1);
                            return ` ${ctx.label}: $${ctx.parsed.toLocaleString()} (${pct}%)`;
                        },
                    },
                },
            },
        },
    };

    const chart = new Chart(canvas, config);
    activeCharts.push(chart);
    return chart;
}

export function destroyAllCharts() {
    activeCharts.forEach((chart) => chart.destroy());
    activeCharts = [];
}

document.addEventListener('livewire:navigating', destroyAllCharts);

const observer = new MutationObserver(() => {
    activeCharts.forEach(updateChartTheme);
});
observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

const resizeObserver = new ResizeObserver((entries) => {
    entries.forEach((entry) => {
        activeCharts.forEach((chart) => {
            if (chart.canvas && entry.target.contains(chart.canvas)) {
                chart.resize();
            }
        });
    });
});
resizeObserver.observe(document.body);
