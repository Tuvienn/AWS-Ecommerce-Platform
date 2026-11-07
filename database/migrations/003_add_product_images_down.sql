-- CHỈ rehearsal trên clone/backup trước khi có dữ liệu gallery phụ thuộc schema.
DROP TABLE IF EXISTS product_images;

DELETE FROM categories
WHERE name = 'Màn hình'
  AND NOT EXISTS (
      SELECT 1
      FROM products
      WHERE products.category_id = categories.id
  );

DELETE FROM schema_migrations
WHERE version = '003_add_product_images';
