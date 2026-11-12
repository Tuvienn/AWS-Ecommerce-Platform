@extends('layouts.app')

@section('title', 'Sản phẩm yêu thích - TechShop')

@push('styles')
<link rel="stylesheet" href="/assets/css/storefront-pages.css">
@endpush

@section('content')
<section class="favorites-page" id="favoritesPage">
    <div class="breadcrumb"><a href="/"><i class="fa-solid fa-house"></i> Trang chủ</a><span>/</span><strong>Yêu thích</strong></div>
    <div class="favorites-heading">
        <div><p class="editorial-eyebrow">Danh sách của bạn</p><h1>Sản phẩm yêu thích</h1><p>Lưu lại những sản phẩm để xem và mua sau.</p></div>
        <a href="/products" class="editorial-button editorial-button-outline">Khám phá sản phẩm</a>
    </div>
    <div id="favoritesLoading" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>
    <div id="favoritesGrid" class="product-grid d-none"></div>
    <div id="favoritesEmpty" class="favorites-empty d-none"><i class="fa-regular fa-heart"></i><h2>Chưa có sản phẩm yêu thích</h2><p>Hãy mở một sản phẩm và bấm “Lưu vào yêu thích”.</p><a href="/products" class="editorial-button editorial-button-primary">Xem sản phẩm</a></div>
</section>
@endsection

@push('scripts')
<script src="/assets/js/pages/favorites.js"></script>
@endpush
