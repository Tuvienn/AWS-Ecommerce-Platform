document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('ai-recommendation-panel');
    const summary = document.getElementById('ai-recommendation-summary');
    const signals = document.getElementById('ai-recommendation-signals');
    const list = document.getElementById('ai-recommendation-list');
    const query = new URLSearchParams(window.location.search).get('q')?.trim() || '';

    if (!panel || query.length < 2) return;

    const escape = (value) => window.Utils?.escapeHtml
        ? window.Utils.escapeHtml(value)
        : String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    const price = (value) => window.Utils?.formatPrice
        ? window.Utils.formatPrice(value)
        : Number(value || 0).toLocaleString('vi-VN') + ' ₫';

    async function loadRecommendations() {
        panel.classList.remove('d-none');
        try {
            const response = await fetch('/api/recommendations/products?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload?.error?.message || 'Không thể tạo gợi ý.');

            const data = payload.data;
            summary.textContent = data.summary;

            const tags = Object.values(data.signals || {}).filter(Boolean);
            signals.innerHTML = tags.map(function (tag) {
                return '<span class="ai-recommendation-signal">' + escape(tag) + '</span>';
            }).join('');

            if (!data.items?.length) {
                panel.classList.add('d-none');
                return;
            }

            list.innerHTML = data.items.map(function (product) {
                const image = product.primary_image
                    ? '<img src="' + escape(product.primary_image) + '" alt="' + escape(product.name) + '" loading="lazy">'
                    : '<i class="fa-solid fa-box"></i>';
                return '<a class="ai-recommendation-item" href="/products/' + encodeURIComponent(product.slug) + '">' +
                    '<span class="ai-recommendation-image">' + image + '</span>' +
                    '<span class="ai-recommendation-content">' +
                    '<strong>' + escape(product.name) + '</strong>' +
                    '<small>' + escape(product.recommendation_reason) + '</small>' +
                    '<b>' + price(product.min_price) + '</b>' +
                    '</span>' +
                    '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>' +
                    '</a>';
            }).join('');
        } catch (error) {
            panel.classList.add('d-none');
            console.warn('Không thể tải gợi ý thông minh:', error);
        }
    }

    loadRecommendations();
});
