-- Migration additive: chưa đổi foreign key cascade và chưa drop cột legacy.
ALTER TABLE products
    ADD COLUMN brand_id INT NULL AFTER category_id,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER stock,
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER is_active;

ALTER TABLE order_details
    ADD COLUMN product_name VARCHAR(255) NULL AFTER product_id;

UPDATE products AS p
LEFT JOIN brands AS b ON b.name = p.brand
SET p.brand_id = b.id
WHERE p.brand <> '';

UPDATE order_details AS od
INNER JOIN products AS p ON p.id = od.product_id
SET od.product_name = p.name
WHERE od.product_name IS NULL;

ALTER TABLE products
    ADD CONSTRAINT fk_products_brand
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    ADD INDEX idx_products_active_catalog (is_active, category_id, brand_id);

ALTER TABLE orders
    ADD INDEX idx_orders_user_created (user_id, created_at),
    ADD INDEX idx_orders_status_created (status, created_at);
