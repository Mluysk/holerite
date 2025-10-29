(function () {
    'use strict';

    function buildPalette(style) {
        const accent = (style.getPropertyValue('--color-primary') || '').trim() || '#2563eb';
        const fallback = ['#f97316', '#22c55e', '#a855f7', '#14b8a6', '#ef4444', '#0ea5e9', '#6366f1', '#f59e0b', '#8b5cf6'];

        return [accent].concat(fallback);
    }

    function parsePayload() {
        const element = document.getElementById('dashboard-data');
        if (!element) {
            return null;
        }

        try {
            const text = element.textContent || element.innerText || '{}';
            return JSON.parse(text) || null;
        } catch (error) {
            console.error('Não foi possível carregar os dados do dashboard.', error);
            return null;
        }
    }

    function createDoughnutChart(canvasId, dataset) {
        if (!dataset || !Array.isArray(dataset.labels) || dataset.labels.length === 0) {
            return null;
        }

        const canvas = document.getElementById(canvasId);
        if (!(canvas instanceof HTMLCanvasElement)) {
            return null;
        }

        const style = window.getComputedStyle(document.body);
        const palette = buildPalette(style);
        const colors = dataset.labels.map((_, index) => palette[index % palette.length]);
        const borderColor = (style.getPropertyValue('--color-surface') || '#ffffff').trim() || '#ffffff';

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: dataset.labels,
                datasets: [{
                    data: dataset.values || [],
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: borderColor
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const rawValue = typeof context.raw === 'number' ? context.raw : 0;
                                const total = dataset.total || 1;
                                const percent = total > 0 ? ((rawValue / total) * 100).toFixed(1) : '0.0';
                                return `${context.label}: R$ ${rawValue.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} (${percent}%)`;
                            }
                        }
                    }
                },
                cutout: '55%'
            }
        });
    }

    function createBarChart(canvasId, dataset, label) {
        if (!dataset || !Array.isArray(dataset.labels) || dataset.labels.length === 0) {
            return null;
        }

        const canvas = document.getElementById(canvasId);
        if (!(canvas instanceof HTMLCanvasElement)) {
            return null;
        }

        const style = window.getComputedStyle(document.body);
        const palette = buildPalette(style);
        const background = palette[0];
        const axisColor = (style.getPropertyValue('--color-text-muted') || '#94a3b8').trim() || '#94a3b8';

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: dataset.labels,
                datasets: [{
                    label: label,
                    data: dataset.values || [],
                    backgroundColor: background,
                    borderColor: background,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    maxBarThickness: 48,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const rawValue = typeof context.raw === 'number' ? context.raw : 0;
                                return `R$ ${rawValue.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: axisColor,
                        },
                        grid: {
                            display: false,
                        }
                    },
                    y: {
                        ticks: {
                            color: axisColor,
                            callback: function (value) {
                                if (typeof value !== 'number') {
                                    return value;
                                }
                                return `R$ ${value.toLocaleString('pt-BR')}`;
                            }
                        },
                        grid: {
                            color: axisColor + '22',
                        }
                    }
                }
            }
        });
    }

    function bootstrap() {
        const payload = parsePayload();
        if (!payload || typeof Chart === 'undefined') {
            return;
        }

        createDoughnutChart('monthlyChart', payload.monthly);
        createDoughnutChart('yearlyChart', payload.yearly);
        createBarChart('monthlyValeChart', payload.monthlyVale, 'Descontos mensais');
        createBarChart('yearlyValeChart', payload.yearlyVale, 'Descontos anuais');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
})();
