-- Chỉ rehearsal trên clone/backup; không dùng như rollback production.
ALTER TABLE products
    DROP FOREIGN KEY fk_products_category,
    ADD CONSTRAINT products_ibfk_1
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE;

ALTER TABLE order_details
    DROP FOREIGN KEY fk_order_details_product,
    MODIFY product_name VARCHAR(255) NULL,
    ADD CONSTRAINT order_details_ibfk_2
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

DELETE FROM schema_migrations WHERE version = '002_finalize_historical_constraints';

