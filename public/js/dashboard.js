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

    function createLineChart(canvasId, dataset, label) {
        if (!dataset || !Array.isArray(dataset.labels) || dataset.labels.length === 0) {
            return null;
        }

        const canvas = document.getElementById(canvasId);
        if (!(canvas instanceof HTMLCanvasElement)) {
            return null;
        }

        const style = window.getComputedStyle(document.body);
        const palette = buildPalette(style);
        const primary = palette[0];
        const axisColor = (style.getPropertyValue('--color-text-muted') || '#94a3b8').trim() || '#94a3b8';
        const ctx = canvas.getContext('2d');
        let gradient = primary;

        if (ctx) {
            gradient = ctx.createLinearGradient(0, 0, 0, canvas.height || 300);
            gradient.addColorStop(0, primary + 'dd');
            gradient.addColorStop(1, primary + '11');
        }

        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: dataset.labels,
                datasets: [{
                    label: label,
                    data: dataset.values || [],
                    fill: true,
                    tension: 0.35,
                    borderColor: primary,
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    pointBackgroundColor: primary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 6,
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

    function setupCalendarMarkers() {
        const markers = document.querySelectorAll('.calendar-day__marker[data-marker-info]');
        if (!markers.length) {
            return;
        }

        const tooltip = document.createElement('div');
        tooltip.className = 'calendar-tooltip';
        tooltip.setAttribute('role', 'dialog');
        tooltip.setAttribute('aria-hidden', 'true');

        const titleEl = document.createElement('div');
        titleEl.className = 'calendar-tooltip__title';

        const titleIcon = document.createElement('span');
        titleIcon.className = 'calendar-tooltip__icon';
        titleIcon.setAttribute('aria-hidden', 'true');
        titleEl.appendChild(titleIcon);

        const titleText = document.createElement('span');
        titleText.className = 'calendar-tooltip__title-text';
        titleEl.appendChild(titleText);

        tooltip.appendChild(titleEl);

        const subtitleEl = document.createElement('div');
        subtitleEl.className = 'calendar-tooltip__subtitle';
        tooltip.appendChild(subtitleEl);

        const list = document.createElement('ul');
        list.className = 'calendar-tooltip__list';
        tooltip.appendChild(list);

        document.body.appendChild(tooltip);

        let activeMarker = null;

        function hideTooltip() {
            if (!activeMarker) {
                return;
            }
            activeMarker.classList.remove('is-active');
            activeMarker.setAttribute('aria-expanded', 'false');
            activeMarker = null;
            tooltip.classList.remove('calendar-tooltip--visible');
            tooltip.setAttribute('aria-hidden', 'true');
        }

        function parseInfo(marker) {
            try {
                const raw = marker.getAttribute('data-marker-info') || '{}';
                const info = JSON.parse(raw);
                if (!info || typeof info !== 'object') {
                    return null;
                }
                return info;
            } catch (error) {
                console.warn('Não foi possível interpretar as informações do pagamento.', error);
                return null;
            }
        }

        function renderTooltip(info) {
            const iconClassMap = {
                regular: 'bi-cash-coin',
                thirteenth: 'bi-gift-fill',
                vacation: 'bi-umbrella-fill',
                termination: 'bi-exclamation-triangle-fill'
            };

            const category = typeof info.category === 'string' ? info.category : '';
            const iconClass = iconClassMap[category] || 'bi-cash-coin';
            titleIcon.innerHTML = '<i class="bi ' + iconClass + '"></i>';
            titleText.textContent = info.title || 'Pagamentos';

            if (category) {
                tooltip.setAttribute('data-category', category);
            } else {
                tooltip.removeAttribute('data-category');
            }

            if (info.date) {
                subtitleEl.textContent = info.date;
                subtitleEl.style.display = '';
            } else {
                subtitleEl.textContent = '';
                subtitleEl.style.display = 'none';
            }

            list.innerHTML = '';
            if (Array.isArray(info.items)) {
                info.items.forEach(function (item) {
                    const element = document.createElement('li');
                    element.className = 'calendar-tooltip__item';

                    const name = document.createElement('span');
                    name.className = 'calendar-tooltip__name';
                    name.textContent = item.name || 'Colaborador';
                    element.appendChild(name);

                    if (item.details) {
                        const details = document.createElement('span');
                        details.className = 'calendar-tooltip__details';
                        details.textContent = item.details;
                        element.appendChild(details);
                    }

                    if (item.amount) {
                        const amount = document.createElement('span');
                        amount.className = 'calendar-tooltip__amount';
                        amount.textContent = item.amount;
                        element.appendChild(amount);
                    }

                    list.appendChild(element);
                });
            }
        }

        function positionTooltip(marker) {
            const rect = marker.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const scrollY = window.scrollY || window.pageYOffset;
            const scrollX = window.scrollX || window.pageXOffset;
            const gutter = 16;
            const top = scrollY + rect.bottom + 10;
            let left = scrollX + rect.left + rect.width / 2 - tooltipRect.width / 2;
            const viewportWidth = document.documentElement.clientWidth;
            if (left < scrollX + gutter) {
                left = scrollX + gutter;
            }
            const maxLeft = scrollX + viewportWidth - tooltipRect.width - gutter;
            if (left > maxLeft) {
                left = Math.max(scrollX + gutter, maxLeft);
            }
            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';
        }

        function showTooltip(marker, info) {
            if (!info) {
                return;
            }

            if (activeMarker && activeMarker !== marker) {
                hideTooltip();
            }

            renderTooltip(info);
            tooltip.setAttribute('aria-hidden', 'false');
            tooltip.style.visibility = 'hidden';
            tooltip.classList.add('calendar-tooltip--visible');
            positionTooltip(marker);
            tooltip.style.visibility = '';

            marker.classList.add('is-active');
            marker.setAttribute('aria-expanded', 'true');
            activeMarker = marker;
        }

        markers.forEach(function (marker) {
            marker.addEventListener('mouseenter', function () {
                const info = parseInfo(marker);
                showTooltip(marker, info);
            });

            marker.addEventListener('mouseleave', function (event) {
                if (!activeMarker || activeMarker !== marker) {
                    return;
                }
                const related = event.relatedTarget;
                if (related instanceof Node && tooltip.contains(related)) {
                    return;
                }
                hideTooltip();
            });

            marker.addEventListener('focus', function () {
                const info = parseInfo(marker);
                showTooltip(marker, info);
            });

            marker.addEventListener('blur', function (event) {
                if (!activeMarker || activeMarker !== marker) {
                    return;
                }
                const related = event.relatedTarget;
                if (related instanceof Node && tooltip.contains(related)) {
                    return;
                }
                hideTooltip();
            });

            marker.addEventListener('click', function (event) {
                event.preventDefault();
                const info = parseInfo(marker);
                if (!info) {
                    hideTooltip();
                    return;
                }
                if (activeMarker === marker) {
                    hideTooltip();
                } else {
                    showTooltip(marker, info);
                }
            });
        });

        tooltip.addEventListener('mouseleave', function () {
            hideTooltip();
        });

        document.addEventListener('click', function (event) {
            if (!activeMarker) {
                return;
            }
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }
            if (tooltip.contains(target) || activeMarker.contains(target)) {
                return;
            }
            hideTooltip();
        }, { capture: true });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideTooltip();
            }
        });

        window.addEventListener('scroll', function () {
            if (!activeMarker) {
                return;
            }
            positionTooltip(activeMarker);
        });

        window.addEventListener('resize', function () {
            if (!activeMarker) {
                return;
            }
            positionTooltip(activeMarker);
        });
    }

    function bootstrap() {
        const payload = parsePayload();

        if (payload && typeof Chart !== 'undefined') {
            createDoughnutChart('monthlyChart', payload.monthly);
            createDoughnutChart('yearlyChart', payload.yearly);
            createLineChart('monthlyValeProductsChart', payload.monthlyValeProducts, 'Vales de produtos - mensal');
            createLineChart('monthlyValeTransportChart', payload.monthlyValeTransport, 'Vale-transporte - mensal');
            createLineChart('yearlyValeProductsChart', payload.yearlyValeProducts, 'Vales de produtos - anual');
            createLineChart('yearlyValeTransportChart', payload.yearlyValeTransport, 'Vale-transporte - anual');
        }

        setupCalendarMarkers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
})();
