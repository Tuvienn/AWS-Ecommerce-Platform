-- CHỈ dùng trên database clone trước khi application ghi dữ liệu Phase 3.
ALTER TABLE orders
    DROP INDEX idx_orders_status_created,
    DROP INDEX idx_orders_user_created;

ALTER TABLE products
    DROP FOREIGN KEY fk_products_brand,
    DROP INDEX idx_products_active_catalog,
    DROP COLUMN archived_at,
    DROP COLUMN is_active,
    DROP COLUMN brand_id;

ALTER TABLE order_details
    DROP COLUMN product_name;

DELETE FROM schema_migrations
WHERE version = '001_additive_order_catalog_integrity';
