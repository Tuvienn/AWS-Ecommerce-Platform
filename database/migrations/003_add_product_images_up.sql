-- Gallery ảnh sản phẩm. Giới hạn tối đa 10 ảnh và đúng một ảnh chính
-- được thực thi ở application layer trong Change 3.
CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_key VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_product_images_product_key UNIQUE (product_id, image_key),
    INDEX idx_product_images_product_sort (product_id, sort_order, id),
    INDEX idx_product_images_primary (product_id, is_primary)
) ENGINE=InnoDB;

INSERT INTO categories (name, icon)
SELECT 'Màn hình', 'fa-desktop'
WHERE NOT EXISTS (
    SELECT 1
    FROM categories
    WHERE name = 'Màn hình'
);
