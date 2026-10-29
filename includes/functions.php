<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/storage.php';

function projectConfig($section = null) {
    $configuration = $GLOBALS['project_config'] ?? [];

    if ($section === null) {
        return $configuration;
    }

    return $configuration[$section] ?? [];
}

function appUrl($path = '') {
    $appConfiguration = projectConfig('app');
    $basePath = $appConfiguration['base_path'] ?? '';
    $path = trim((string) $path);

    if ($path === '' || $path === '/') {
        return $basePath !== '' ? $basePath . '/' : '/';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if ($basePath !== '' && ($path === $basePath || strpos($path, $basePath . '/') === 0)) {
        return $path;
    }

    return $basePath . '/' . ltrim($path, '/');
}

function assetUrl($path) {
    return appUrl($path);
}

function contentUrl($url) {
    $url = trim((string) $url);

    if ($url === '' || $url === '#') {
        return $url === '' ? '#' : $url;
    }

    if (preg_match('#^(?:https?:)?//#i', $url)) {
        return $url;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return '#';
    }

    $path = $parts['path'] ?? '/';

    // Banner seed cũ có thể chứa một deployment prefix trước products.php.
    if (preg_match('#/products\.php$#', $path)) {
        $path = '/products.php';
    }

    $portableUrl = $path;
    if (isset($parts['query'])) {
        $portableUrl .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
        $portableUrl .= '#' . $parts['fragment'];
    }

    return appUrl($portableUrl);
}

// Format giá tiền VND
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Tính phần trăm giảm giá
function discountPercent($price, $sale_price) {
    if ($price <= 0 || $sale_price <= 0 || $sale_price >= $price) return 0;
    return round(($price - $sale_price) / $price * 100);
}

// Lấy số lượng sản phẩm trong giỏ hàng
function getCartCount() {
    if (!isset($_SESSION['cart'])) return 0;
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function loginUser($user) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'];
}

function getAvatarUrl($avatar, $fallbackName = 'User') {
    $avatar = trim((string) $avatar);
    if ($avatar !== '') {
        if (preg_match('#^https?://#i', $avatar)) {
            return $avatar;
        }

        return runtimeImageUrl($avatar, 'uploads/avatars');
    }

    return 'https://ui-avatars.com/api/?name=' . urlencode($fallbackName) . '&background=010101&color=fff';
}

// Lọc input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Hiển thị thông báo flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

function redirectTo($path) {
    redirect(appUrl($path));
}
