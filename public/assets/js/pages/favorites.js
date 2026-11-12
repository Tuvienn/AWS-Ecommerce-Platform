document.addEventListener('DOMContentLoaded', async () => {
    const page = document.getElementById('favoritesPage');
    if (!page) return;
    const loading = document.getElementById('favoritesLoading');
    const grid = document.getElementById('favoritesGrid');
    const empty = document.getElementById('favoritesEmpty');

    const escapeHtml = (value) => window.Utils?.escapeHtml ? window.Utils.escapeHtml(value || '') : String(value || '');
    const formatPrice = (value) => window.Utils?.formatPrice ? window.Utils.formatPrice(value) : `${Number(value || 0).toLocaleString('vi-VN')} ₫`;

    try {
        // AppAuth khởi tạo bất đồng bộ từ layout. Phải chờ hoàn tất trước khi
        // kiểm tra phiên, nếu không người dùng vừa đăng nhập có thể bị chuyển
        // nhầm về trang login.
        if (window.AppAuth?.init) {
            await window.AppAuth.init();
        }

        if (!window.AppAuth?.isAuthenticated()) {
            window.location.href = `/login?redirect=${encodeURIComponent('/favorites')}`;
            return;
        }
        const response = await window.FavoriteService.getAll();
        const items = Array.isArray(response?.data) ? response.data : [];
        loading.classList.add('d-none');
        if (!items.length) { empty.classList.remove('d-none'); return; }
        grid.classList.remove('d-none');
        grid.innerHTML = items.map((item) => `
            <article class="product-card favorite-product-card">
                <button class="favorite-remove-button" data-product-id="${escapeHtml(item.id)}" aria-label="Bỏ ${escapeHtml(item.name)} khỏi yêu thích"><i class="fa-solid fa-heart"></i></button>
                <a href="/products/${encodeURIComponent(item.slug)}" class="product-card-link">
                    <div class="product-img">${item.primary_image ? `<img src="${escapeHtml(item.primary_image)}" alt="${escapeHtml(item.name)}" loading="lazy">` : '<i class="fa-solid fa-image"></i>'}</div>
                    <div class="product-info"><div class="product-brand">${escapeHtml(item.brand || item.category?.name)}</div><div class="product-name">${escapeHtml(item.name)}</div><div class="product-price"><span class="price-sale">${formatPrice(item.min_price)}</span>${item.has_sale ? `<span class="price-original">${formatPrice(item.min_original_price)}</span>` : ''}</div></div>
                </a>
            </article>`).join('');
        grid.querySelectorAll('.favorite-remove-button').forEach((button) => button.addEventListener('click', async () => {
            button.disabled = true;
            try { await window.FavoriteService.remove(button.dataset.productId); button.closest('.favorite-product-card').remove(); if (!grid.children.length) { grid.classList.add('d-none'); empty.classList.remove('d-none'); } window.Utils.showToast('Đã bỏ khỏi yêu thích.', 'success'); }
            catch (error) { button.disabled = false; window.Utils.showToast(error?.message || 'Không thể cập nhật yêu thích.', 'error'); }
        }));
    } catch (error) {
        loading.classList.add('d-none');
        empty.classList.remove('d-none');
        window.Utils.showToast(error?.message || 'Không thể tải danh sách yêu thích.', 'error');
    }
});
