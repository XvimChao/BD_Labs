CREATE TABLE products (
    product_id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    parent_id INT REFERENCES products(product_id) ON DELETE CASCADE,
    description TEXT,
    price DECIMAL(10, 2),
    depth INT NOT NULL
);


INSERT INTO products (name, parent_id, description, price, depth) VALUES
('Товары', NULL, 'Корневая категория', NULL, 0),
('Электроника', 1, 'Категория электроники', NULL, 1),
('Телефоны', 2, 'Смартфоны и мобильные телефоны', NULL, 2),
('Смартфоны', 3, 'Современные смартфоны', NULL, 3),
('Apple iPhone', 4, 'Смартфон от Apple', 999.99, 4),
('Samsung Galaxy', 4, 'Смартфон от Samsung', 899.99, 4),
('Huawei P30', 4, 'Смартфон от Huawei', 799.99, 4),
('Ноутбуки', 2, 'Ноутбуки и ультрабуки', NULL, 2),
('Игровые ноутбуки', 8, 'Ноутбуки для игр', NULL, 3),
('ASUS ROG', 9, 'Игровой ноутбук ASUS', 1499.99, 4),
('Lenovo Legion', 9, 'Игровой ноутбук Lenovo', 1399.99, 4),
('Бытовая техника', 1, 'Категория бытовой техники', NULL, 1),
('Холодильники', 12, 'Холодильники и морозильники', NULL, 2),
('LG Refrigerator', 13, 'Холодильник от LG', 599.99, 3),
('Samsung Refrigerator', 13, 'Холодильник от Samsung', 579.99, 3),
('Кухонные приборы', 12, 'Приборы для кухни', NULL, 2),
('Микроволновые печи', 16, 'Микроволновые печи различных моделей', NULL, 3),
('LG Microwave', 17, 'Микроволновая печь LG', 199.99, 4),
('Samsung Microwave', 17, 'Микроволновая печь Samsung', 179.99, 4);