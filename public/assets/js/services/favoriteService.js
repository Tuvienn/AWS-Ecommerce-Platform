/** Danh sách sản phẩm yêu thích của khách đã đăng nhập. */
(function () {
    window.FavoriteService = {
        getAll: () => window.ApiClient.get('/api/favorites'),
        add: (productId) => window.ApiClient.post(`/api/favorites/${encodeURIComponent(productId)}`),
        remove: (productId) => window.ApiClient.delete(`/api/favorites/${encodeURIComponent(productId)}`),
    };
})();
