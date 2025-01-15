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
(8, 'Айфоны', 3),
(9, 'Apple iPhone 12', 8),
(10, 'iPhone 5S', 8),
(11, 'Ноутбуки', 2),
(12, 'ASUS ROG', 11),
(13, 'Lenovo Legion', 11),
(14, 'Бытовая техника', 1),
(15, 'Пылесосы', 14),
(16, 'Роботы-пылесосы', 15),
(17, 'Ручные пылесосы', 15),
(18, 'Кухонные приборы', 14),
(19, 'Микроволновые печи', 18),
(20, 'LG Microwave', 19),
(21, 'Samsung Microwave', 19),
(22, 'Кухонные плиты', 18),
(23, 'Газовые плиты', 22),
(24, 'Электрические плиты', 22);

SELECT setval('neighbor_tree_id_seq', COALESCE((SELECT MAX(id) FROM neighbor_tree), 0));