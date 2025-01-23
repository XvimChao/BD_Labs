CREATE SEQUENCE catalog_tree_id_seq;

CREATE TABLE catalog_tree (
    id INT DEFAULT nextval('catalog_tree_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    lft INT NOT NULL,
    rgt INT NOT NULL
);

INSERT INTO catalog_tree (id, title, lft, rgt) VALUES
(1, 'Товары', 1, 44),
(2, 'Электроника', 2, 21),
(3, 'ПК и ноутбуки', 22, 43),
(4, 'Ноутбуки', 3, 8),
(5, 'Рюкзаки для ноутбуков', 4, 5),
(6, 'Чехлы для ноутбуков', 6, 7),
(7, 'Компьютеры', 9, 12),
(8, 'Смартфоны', 11, 20),
(9, 'Бытовая техника', 14, 41),
(10, 'Техника для кухни', 15, 20),
(11, 'Плиты', 16, 17),
(12, 'Холодильники', 18, 19),
(13, 'Техника для дома', 22, 27),
(14, 'Стиральные машины', 23, 24),
(15, 'Сушильные машины', 25, 26),
(16, 'Книги', 28, 33),
(17, 'Фантастика', 29, 30),
(18, 'Классика', 31, 32);

SELECT setval('catalog_tree_id_seq', COALESCE((SELECT MAX(id) FROM catalog_tree), 0));