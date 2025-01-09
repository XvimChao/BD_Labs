CREATE SEQUENCE neighbor_tree_id_seq;

CREATE TABLE neighbor_tree (
    id INT DEFAULT nextval('neighbor_tree_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    parent_id INT REFERENCES neighbor_tree(id) ON DELETE CASCADE
);


INSERT INTO neighbor_tree (id, title, parent_id) VALUES
(1, 'Товары', NULL),
(2, 'Электроника', 1),
(3, 'Телефоны', 2),
(4, 'Смартфоны', 3),
(5, 'Apple iPhone', 4),
(6, 'Samsung Galaxy', 4),
(7, 'Huawei P30', 4),
(8, 'Ноутбуки', 2),
(9, 'ASUS ROG', 8),
(10, 'Lenovo Legion', 8),
(11, 'Бытовая техника', 1),
(12, 'Холодильники', 11),
(13, 'LG Refrigerator', 12),
(14, 'Samsung Refrigerator', 12),
(15, 'Кухонные приборы', 11),
(16, 'Микроволновые печи', 15),
(17, 'LG Microwave', 16),
(18, 'Samsung Microwave', 16),
(19, 'Ножи', 15),
(20, 'Нож для пиццы', 19),
(21, 'Филейный нож', 19);

SELECT setval('neighbor_tree_id_seq', COALESCE((SELECT MAX(id) FROM neighbor_tree), 0));