(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.search-bar');
        const input = document.getElementById('header-search-input');
        const panel = document.getElementById('header-ai-suggestions');
        if (!form || !input || !panel) return;

        let timer = null;
        let requestController = null;

        const escape = function (value) {
            if (window.Utils?.escapeHtml) return window.Utils.escapeHtml(value);
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
            });
        };
        const price = function (value) {
            return window.Utils?.formatPrice
                ? window.Utils.formatPrice(value)
                : Number(value || 0).toLocaleString('vi-VN') + ' ₫';
        };
        const close = function () {
            panel.hidden = true;
            panel.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        };

        const render = function (query, data) {
            const items = data.items || [];
            if (!items.length) {
                close();
                return;
            }

            const cards = items.map(function (product) {
                const image = product.primary_image
                    ? '<img src="' + escape(product.primary_image) + '" alt="" loading="lazy">'
                    : '<i class="fa-solid fa-box"></i>';
                return '<a class="header-ai-item" href="/products/' + encodeURIComponent(product.slug) + '">' +
                    '<span class="header-ai-image">' + image + '</span>' +
                    '<span class="header-ai-content"><strong>' + escape(product.name) + '</strong>' +
                    '<small>' + escape(product.recommendation_reason) + '</small>' +
                    '<b>' + price(product.min_price) + '</b></span>' +
                    '<i class="fa-solid fa-chevron-right"></i></a>';
            }).join('');

            panel.innerHTML = '<div class="header-ai-heading"><span><i class="fa-solid fa-wand-magic-sparkles"></i> Gợi ý thông minh</span>' +
                '<small>' + escape(data.summary) + '</small></div>' +
                '<div class="header-ai-list">' + cards + '</div>' +
                '<a class="header-ai-all-results" href="/products?q=' + encodeURIComponent(query) + '">Xem toàn bộ kết quả <i class="fa-solid fa-arrow-right"></i></a>';
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        const load = async function () {
            const query = input.value.trim();
            if (query.length < 2) {
                close();
                return;
            }

            if (requestController) requestController.abort();
            requestController = new AbortController();

            try {
                const response = await fetch('/api/recommendations/products?q=' + encodeURIComponent(query), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: requestController.signal
                });
                const payload = await response.json();
                if (!response.ok || !payload.success || input.value.trim() !== query) return;
                render(query, payload.data);
            } catch (error) {
                if (error.name !== 'AbortError') close();
            }
        };

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(load, 240);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= 2) load();
        });

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) close();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
    });
})();
