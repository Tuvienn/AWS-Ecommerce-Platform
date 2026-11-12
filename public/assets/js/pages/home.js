/**
 * Trang chủ TechShop: campaign dùng dữ liệu sản phẩm thật và các dải Catalog API.
 */
document.addEventListener('DOMContentLoaded', function () {
    initEditorialCampaigns();
    loadCategories();
    loadFlashSaleProducts();
    loadNewProducts();
    loadPremiumProducts();
});

const formatCurrency = (amount) => {
    if (window.Utils && typeof window.Utils.formatPrice === 'function') {
        return window.Utils.formatPrice(amount);
    }
    return new Intl.NumberFormat('vi-VN').format(amount || 0) + '₫';
};

const calcDiscount = (original, current) => {
    if (window.Utils && typeof window.Utils.discountPercent === 'function') {
        return window.Utils.discountPercent(original, current);
    }
    if (!original || !current || original <= current) return 0;
    return Math.round(((original - current) / original) * 100);
};

const escapeHtmlText = (value) => {
    if (window.Utils && typeof window.Utils.escapeHtml === 'function') {
        return window.Utils.escapeHtml(value || '');
    }
    const element = document.createElement('div');
    element.textContent = value || '';
    return element.innerHTML;
};

async function getProductDetail(slug) {
    try {
        if (window.CatalogService && typeof window.CatalogService.getProductDetail === 'function') {
            const response = await window.CatalogService.getProductDetail(slug);
            return response?.data || null;
        }

        const response = await fetch(`/api/products/${encodeURIComponent(slug)}`);
        const data = await response.json();
        return data?.data || null;
    } catch (error) {
        console.warn(`Không thể nạp campaign ${slug}:`, error);
        return null;
    }
}

function getCampaignModel(product) {
    if (!product) return null;

    const images = Array.isArray(product.images) ? product.images : [];
    const image = images.find((item) => item.is_primary)?.url || images[0]?.url || null;
    const variants = Array.isArray(product.variants) ? product.variants : [];
    const prices = variants
        .map((variant) => Number(variant.effective_price))
        .filter((price) => Number.isFinite(price) && price > 0);

    if (!image || prices.length === 0) return null;

    return {
        name: product.name,
        slug: product.slug,
        image,
        price: Math.min(...prices),
        inStock: variants.some((variant) => variant.in_stock),
    };
}

function createProductImage(source, alt, loading = 'lazy') {
    const image = document.createElement('img');
    image.src = source;
    image.alt = alt;
    image.loading = loading;
    image.decoding = 'async';
    image.addEventListener('error', function () {
        image.remove();
    }, { once: true });
    return image;
}

async function initEditorialCampaigns() {
    const campaigns = window.HomeCampaigns;
    if (!campaigns) return;

    const hero = document.getElementById('editorialHero');
    const [spotlightProduct, ...secondaryProducts] = await Promise.all([
        getProductDetail(campaigns.spotlight?.slug),
        ...(campaigns.secondary || []).map((campaign) => getProductDetail(campaign.slug)),
    ]);

    const spotlight = getCampaignModel(spotlightProduct);
    if (!spotlight) {
        if (hero) hero.hidden = true;
    } else {
        renderSpotlightCampaign(spotlight, campaigns.spotlight);
    }

    (campaigns.secondary || []).forEach((campaign, index) => {
        const model = getCampaignModel(secondaryProducts[index]);
        renderSecondaryCampaign(model, campaign);
    });
}

function renderSpotlightCampaign(product, campaign) {
    const title = document.getElementById('editorialHeroTitle');
    const eyebrow = document.getElementById('editorialHeroEyebrow');
    const description = document.getElementById('editorialHeroDescription');
    const price = document.getElementById('editorialHeroPrice');
    const primaryCta = document.getElementById('editorialHeroPrimaryCta');
    const secondaryCta = document.getElementById('editorialHeroSecondaryCta');
    const visual = document.getElementById('editorialHeroVisual');

    if (title) title.textContent = campaign.headline || product.name;
    if (eyebrow) eyebrow.textContent = campaign.eyebrow || product.name;
    if (description) description.textContent = campaign.description || product.name;
    if (price) price.textContent = `Từ ${formatCurrency(product.price)}`;

    if (primaryCta) {
        primaryCta.href = `/products/${encodeURIComponent(product.slug)}`;
        primaryCta.textContent = 'Xem sản phẩm';
    }

    if (secondaryCta) {
        secondaryCta.href = campaign.secondaryHref || '/products';
        secondaryCta.textContent = campaign.secondaryLabel || 'Khám phá thêm';
    }

    if (visual) {
        const image = createProductImage(campaign.heroImage || product.image, '');
        image.fetchPriority = 'high';
        image.style.objectPosition = campaign.imagePosition || 'center';
        visual.replaceChildren(image);
    }
}

function renderSecondaryCampaign(product, campaign) {
    const card = document.getElementById(campaign.elementId);
    if (!card) return;

    if (!product) {
        card.hidden = true;
        return;
    }

    const imageArea = card.querySelector('.editorial-campaign-image');
    const link = card.querySelector('.editorial-text-link');
    if (imageArea) {
        const image = createProductImage(product.image, '');
        image.style.objectPosition = campaign.imagePosition || 'center';
        imageArea.replaceChildren(image);
    }
    if (link) {
        link.href = campaign.href || `/products/${encodeURIComponent(product.slug)}`;
        link.childNodes[0].textContent = `${campaign.label || 'Khám phá'} `;
    }
}

async function loadCategories() {
    const grid = document.getElementById('categoryGrid');
    if (!grid) return;

    const iconMap = {
        'dien-thoai': 'fa-mobile-screen',
        laptop: 'fa-laptop',
        'may-tinh-bang': 'fa-tablet-screen-button',
        'am-thanh': 'fa-headphones',
        'phu-kien': 'fa-keyboard',
        'dong-ho-thong-minh': 'fa-clock',
    };

    try {
        let categories = [];
        if (window.CatalogService && typeof window.CatalogService.getCategories === 'function') {
            const response = await window.CatalogService.getCategories();
            if (response?.success && Array.isArray(response.data)) categories = response.data;
        } else {
            const response = await fetch('/api/categories');
            const data = await response.json();
            if (data?.success && Array.isArray(data.data)) categories = data.data;
        }

        if (!categories.length) {
            grid.innerHTML = '<p class="editorial-empty-state">Chưa có danh mục nào để hiển thị.</p>';
            return;
        }

        grid.innerHTML = categories.map((category) => {
            const iconClass = iconMap[category.slug] || 'fa-tags';
            return `
                <a href="/products?category=${encodeURIComponent(category.slug)}" class="category-card">
                    <div class="category-icon"><i class="fa-solid ${iconClass}"></i></div>
                    <span class="category-name">${escapeHtmlText(category.name)}</span>
                    <i class="fa-solid fa-arrow-right category-arrow" aria-hidden="true"></i>
                </a>
            `;
        }).join('');
    } catch (error) {
        console.error('Lỗi khi tải danh mục:', error);
        grid.innerHTML = '<p class="editorial-empty-state">Không thể tải danh mục sản phẩm.</p>';
    }
}

async function loadFlashSaleProducts() {
    await loadProductCollection('flashSaleGrid', { on_sale: 1, page_size: 8 }, 'Hiện chưa có sản phẩm giảm giá.');
}

async function loadNewProducts() {
    await loadProductCollection('newProductsGrid', { sort: 'newest', page_size: 8 }, 'Chưa có sản phẩm mới.');
}

async function loadPremiumProducts() {
    await loadProductCollection('premiumProductsGrid', { sort: 'price_desc', page_size: 8 }, 'Chưa có sản phẩm nổi bật.');
}

async function loadProductCollection(elementId, params, emptyMessage) {
    const grid = document.getElementById(elementId);
    if (!grid) return;

    showLoadingSkeleton(grid, 4);
    try {
        let products = [];
        if (window.CatalogService && typeof window.CatalogService.getProducts === 'function') {
            const response = await window.CatalogService.getProducts(params);
            if (response?.success && Array.isArray(response.data)) products = response.data;
        } else {
            const search = new URLSearchParams(params);
            const response = await fetch(`/api/products?${search.toString()}`);
            const data = await response.json();
            if (data?.success && Array.isArray(data.data)) products = data.data;
        }
        renderProducts(grid, products, emptyMessage);
    } catch (error) {
        console.error('Lỗi khi tải sản phẩm:', error);
        grid.innerHTML = '<p class="editorial-empty-state">Không thể tải sản phẩm lúc này.</p>';
    }
}

function showLoadingSkeleton(container, count = 4) {
    container.innerHTML = Array.from({ length: count }, () => `
        <div class="product-card skeleton-card" style="min-height: 310px;">
            <div class="skeleton-icon" style="width: 100%; height: 160px; border-radius: 8px;"></div>
            <div class="skeleton-text" style="width: 40%; height: 10px; margin-top: 12px;"></div>
            <div class="skeleton-text" style="width: 85%; height: 14px; margin-top: 8px;"></div>
            <div class="skeleton-text" style="width: 60%; height: 16px; margin-top: 14px;"></div>
        </div>
    `).join('');
}

function renderProducts(container, products, emptyMessage) {
    if (!products?.length) {
        container.innerHTML = `<p class="editorial-empty-state">${escapeHtmlText(emptyMessage)}</p>`;
        return;
    }

    container.innerHTML = products.map((product) => {
        const hasSale = Boolean(product.has_sale && product.min_original_price > product.min_price);
        const discount = hasSale ? calcDiscount(product.min_original_price, product.min_price) : 0;
        const image = product.primary_image
            ? `<img src="${escapeHtmlText(product.primary_image)}" alt="${escapeHtmlText(product.name)}" loading="lazy" onerror="this.remove()">`
            : '<i class="fa-solid fa-image product-image-fallback" aria-hidden="true"></i>';

        return `
            <a href="/products/${encodeURIComponent(product.slug)}" class="product-card">
                ${hasSale && discount > 0 ? `<span class="discount-badge">-${discount}%</span>` : ''}
                ${!product.in_stock ? '<span class="stock-badge out-of-stock">Hết hàng</span>' : ''}
                <div class="product-img">${image}</div>
                <div class="product-info">
                    ${product.brand ? `<div class="product-brand">${escapeHtmlText(product.brand)}</div>` : ''}
                    <div class="product-name" title="${escapeHtmlText(product.name)}">${escapeHtmlText(product.name)}</div>
                    <div class="product-price">
                        <span class="price-sale">${formatCurrency(product.min_price)}</span>
                        ${hasSale ? `<span class="price-original">${formatCurrency(product.min_original_price)}</span>` : ''}
                    </div>
                </div>
            </a>
        `;
    }).join('');
}
