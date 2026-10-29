-- Finalize constraint sau khi preflight orphan và NULL snapshot đều bằng 0.
ALTER TABLE order_details
    MODIFY product_name VARCHAR(255) NOT NULL,
    DROP FOREIGN KEY order_details_ibfk_2,
    ADD CONSTRAINT fk_order_details_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;

ALTER TABLE products
    DROP FOREIGN KEY products_ibfk_1,
    ADD CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT;

