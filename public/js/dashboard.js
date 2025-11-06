(function () {
    'use strict';

    function parseJsonElement(id) {
        const element = document.getElementById(id);

        if (!element) {
            return null;
        }

        try {
            const text = element.textContent || element.innerText || '{}';
            return JSON.parse(text) || null;
        } catch (error) {
            console.error('Não foi possível interpretar os dados do elemento "' + id + '".', error);
            return null;
        }
    }

    function buildPalette(style) {
        const accent = (style.getPropertyValue('--color-primary') || '').trim() || '#2563eb';
        const fallback = ['#f97316', '#22c55e', '#a855f7', '#14b8a6', '#ef4444', '#0ea5e9', '#6366f1', '#f59e0b', '#8b5cf6'];

        return [accent].concat(fallback);
    }

    function parsePayload() {
        return parseJsonElement('dashboard-data');
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

        const headerEl = document.createElement('div');
        headerEl.className = 'calendar-tooltip__header';
        tooltip.appendChild(headerEl);

        const headingEl = document.createElement('div');
        headingEl.className = 'calendar-tooltip__heading';
        headerEl.appendChild(headingEl);

        const titleIcon = document.createElement('span');
        titleIcon.className = 'calendar-tooltip__icon';
        titleIcon.setAttribute('aria-hidden', 'true');
        headingEl.appendChild(titleIcon);

        const titleGroup = document.createElement('div');
        titleGroup.className = 'calendar-tooltip__title-group';
        headingEl.appendChild(titleGroup);

        const titleText = document.createElement('span');
        titleText.className = 'calendar-tooltip__title';
        titleGroup.appendChild(titleText);

        const statusEl = document.createElement('span');
        statusEl.className = 'calendar-tooltip__status';
        statusEl.textContent = 'Pago';
        titleGroup.appendChild(statusEl);

        const dateEl = document.createElement('span');
        dateEl.className = 'calendar-tooltip__date';
        headerEl.appendChild(dateEl);

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
                termination: 'bi-exclamation-triangle-fill',
                advance: 'bi-wallet2',
                birthday: 'bi-cake2',
                vacation_plan: 'bi-umbrella-fill'
            };

            const category = typeof info.category === 'string' ? info.category : '';
            const iconClass = iconClassMap[category] || 'bi-cash-coin';
            titleIcon.innerHTML = '<i class="bi ' + iconClass + '"></i>';
            titleText.textContent = info.title || 'Pagamentos';
            statusEl.textContent = info.status || 'Pago';

            if (category) {
                tooltip.setAttribute('data-category', category);
            } else {
                tooltip.removeAttribute('data-category');
            }

            if (info.date) {
                dateEl.textContent = info.date;
                dateEl.style.display = '';
            } else {
                dateEl.textContent = '';
                dateEl.style.display = 'none';
            }

            list.innerHTML = '';
            let itemCount = 0;
            if (Array.isArray(info.items)) {
                info.items.forEach(function (item) {
                    const element = document.createElement('li');
                    element.className = 'calendar-tooltip__item';

                    const person = document.createElement('div');
                    person.className = 'calendar-tooltip__person';
                    element.appendChild(person);

                    const dot = document.createElement('span');
                    dot.className = 'calendar-tooltip__dot';
                    dot.setAttribute('aria-hidden', 'true');
                    person.appendChild(dot);

                    const personInfo = document.createElement('div');
                    personInfo.className = 'calendar-tooltip__person-info';
                    person.appendChild(personInfo);

                    const name = document.createElement('span');
                    name.className = 'calendar-tooltip__name';
                    name.textContent = item.name || 'Colaborador';
                    personInfo.appendChild(name);

                    if (item.details) {
                        const details = document.createElement('span');
                        details.className = 'calendar-tooltip__details';
                        details.textContent = item.details;
                        personInfo.appendChild(details);
                    }

                    if (item.amount) {
                        const amount = document.createElement('span');
                        amount.className = 'calendar-tooltip__amount';
                        amount.textContent = item.amount;
                        element.appendChild(amount);
                    }

                    list.appendChild(element);
                    itemCount += 1;
                });
            }

            if (itemCount > 10) {
                list.classList.add('calendar-tooltip__list--scrollable');
                list.setAttribute('data-scrollable', 'true');
            } else {
                list.classList.remove('calendar-tooltip__list--scrollable');
                list.removeAttribute('data-scrollable');
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

    function setupAlertPopups() {
        const payload = parseJsonElement('dashboard-popups');

        if (!payload || typeof payload !== 'object') {
            return;
        }

        const queue = [];

        if (Array.isArray(payload.birthdays)) {
            payload.birthdays.forEach(function (entry) {
                if (!entry || typeof entry.name !== 'string') {
                    return;
                }

                queue.push({
                    type: 'birthday',
                    icon: 'bi-cake2',
                    title: 'Feliz aniversário!',
                    subtitle: entry.name,
                    message: 'Que o seu dia seja repleto de conquistas e alegria.',
                    meta: entry.label ? 'Hoje • ' + entry.label : 'Hoje',
                    duration: 7000,
                });
            });
        }

        if (Array.isArray(payload.vacations)) {
            payload.vacations.forEach(function (entry) {
                if (!entry || typeof entry.name !== 'string') {
                    return;
                }

                const availableLabel = typeof entry.available_label === 'string' ? entry.available_label : '';
                const noticeLabel = typeof entry.notice_label === 'string' ? entry.notice_label : '';
                const status = typeof entry.status === 'string' && entry.status.trim() !== ''
                    ? entry.status.trim()
                    : null;

                const metaParts = [];
                if (noticeLabel) {
                    metaParts.push('Aviso iniciado em ' + noticeLabel);
                }
                if (status) {
                    metaParts.push(status);
                }

                queue.push({
                    type: 'vacation',
                    icon: 'bi-umbrella-fill',
                    title: 'Férias chegando!',
                    subtitle: entry.name,
                    message: availableLabel
                        ? 'Período previsto a partir de ' + availableLabel + '.'
                        : 'Período de férias em preparação.',
                    meta: metaParts.join(' • '),
                    duration: 9000,
                });
            });
        }

        if (!queue.length) {
            return;
        }

        const container = document.createElement('div');
        container.className = 'dashboard-popups';
        document.body.appendChild(container);

        function buildPopup(item, onComplete) {
            const popup = document.createElement('div');
            popup.className = 'dashboard-popup dashboard-popup--' + item.type;
            popup.setAttribute('role', 'alert');
            popup.setAttribute('aria-live', 'assertive');

            const iconWrap = document.createElement('div');
            iconWrap.className = 'dashboard-popup__icon';
            iconWrap.innerHTML = '<i class="bi ' + item.icon + '"></i>';
            popup.appendChild(iconWrap);

            const content = document.createElement('div');
            content.className = 'dashboard-popup__content';
            popup.appendChild(content);

            const title = document.createElement('h3');
            title.className = 'dashboard-popup__title';
            title.textContent = item.title || '';
            content.appendChild(title);

            if (item.subtitle) {
                const subtitle = document.createElement('p');
                subtitle.className = 'dashboard-popup__subtitle';
                subtitle.textContent = item.subtitle;
                content.appendChild(subtitle);
            }

            if (item.message) {
                const message = document.createElement('p');
                message.className = 'dashboard-popup__message';
                message.textContent = item.message;
                content.appendChild(message);
            }

            if (item.meta) {
                const meta = document.createElement('p');
                meta.className = 'dashboard-popup__meta';
                meta.textContent = item.meta;
                content.appendChild(meta);
            }

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'dashboard-popup__close';
            closeButton.setAttribute('aria-label', 'Fechar aviso');
            closeButton.innerHTML = '<i class="bi bi-x-lg"></i>';
            popup.appendChild(closeButton);

            let dismissed = false;
            let timerId = 0;

            function finalize() {
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }

            function removePopup() {
                popup.remove();
                finalize();
            }

            function dismiss() {
                if (dismissed) {
                    return;
                }
                dismissed = true;
                if (timerId) {
                    window.clearTimeout(timerId);
                    timerId = 0;
                }

                popup.classList.remove('dashboard-popup--visible');
                popup.classList.add('dashboard-popup--hide');

                let completed = false;
                const handleAnimationEnd = function (event) {
                    if (event.animationName !== 'dashboardPopupOut' || completed) {
                        return;
                    }
                    completed = true;
                    popup.removeEventListener('animationend', handleAnimationEnd);
                    removePopup();
                };

                popup.addEventListener('animationend', handleAnimationEnd);

                window.setTimeout(function () {
                    if (completed) {
                        return;
                    }
                    popup.removeEventListener('animationend', handleAnimationEnd);
                    completed = true;
                    removePopup();
                }, 350);
            }

            closeButton.addEventListener('click', function () {
                dismiss();
            });

            timerId = window.setTimeout(dismiss, typeof item.duration === 'number' ? item.duration : 6500);

            return popup;
        }

        function displayNext() {
            if (!queue.length) {
                if (!container.childElementCount && container.parentNode) {
                    container.parentNode.removeChild(container);
                }
                return;
            }

            const item = queue.shift();
            const popup = buildPopup(item, function () {
                if (queue.length > 0) {
                    displayNext();
                    return;
                }

                if (!container.childElementCount && container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            });

            container.appendChild(popup);

            requestAnimationFrame(function () {
                popup.classList.add('dashboard-popup--visible');
            });
        }

        displayNext();
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
        setupAlertPopups();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
})();
