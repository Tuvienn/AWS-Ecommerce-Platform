/**
 * Campaign trang chủ TechShop.
 * Chỉ dùng slug của sản phẩm đang Active trong Catalog API và ảnh catalog tương ứng.
 */
window.HomeCampaigns = Object.freeze({
    spotlight: {
        slug: 'iphone-duo',
        eyebrow: 'Lựa chọn nổi bật',
        headline: 'iPhone Duo. Mở rộng trải nghiệm.',
        description: 'Khám phá iPhone Duo với màn hình gập rộng rãi và hiệu năng mạnh mẽ tại TechShop.',
        secondaryLabel: 'Khám phá điện thoại',
        secondaryHref: '/products?category=dien-thoai',
        imagePosition: 'center',
    },
    secondary: [
        {
            elementId: 'editorialCampaignLaptop',
            slug: 'dell-xps-14-9440',
            label: 'Khám phá laptop',
            href: '/products?category=laptop',
            imagePosition: 'center',
        },
        {
            elementId: 'editorialCampaignAudio',
            slug: 'airpods-pro-2',
            label: 'Khám phá âm thanh',
            href: '/products?category=am-thanh',
            imagePosition: 'center',
        },
    ],
});
