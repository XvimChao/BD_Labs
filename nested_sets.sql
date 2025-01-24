CREATE SEQUENCE catalog_tree_id_seq;

CREATE TABLE catalog_tree (
    id INT DEFAULT nextval('catalog_tree_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    lft INT NOT NULL,
    rgt INT NOT NULL
);

INSERT INTO catalog_tree (id, title, lft, rgt) VALUES
(1, 'Товары', 1, 47),
(2, 'Электроника', 2, 23),
(3, 'ПК, ноутбуки, периферия', 3, 17),
(4, 'Ноутбуки и аксессуары', 4, 11),
(5, 'Ноутбуки', 5, 6),
(6, 'Рюкзаки для ноутбуков', 7, 8),
(7, 'Чехлы для ноутбуков', 9, 10),
(8, 'Компьютеры и ПО', 12, 17),
(9, 'Персональные компьютеры', 13, 14),
(10, 'Моноблоки', 15, 16),
(11, 'Смартфоны и гаджеты', 16, 22),
(12, 'Смартфоны', 18, 19),
(13, 'Сотовые телефоны', 20, 21),
(14, 'Бытовая техника', 24, 46),
(15, 'Техника для кухни', 26, 31),
(16, 'Плиты и печи', 27, 28),
(17, 'Холодильное оборудование', 29, 30),
(18, 'Техника для дома', 32, 45);
(19, 'Стирка и сушка', 33, 38),
(20, 'Стиральные машины', 34, 35),
(21, 'Сушильные машины', 36,37),
(22, 'Уборка', 39, 44),
(23, 'Напольные пылесосы', 40, 41),
(24, 'Роботы пылесосы', 42, 43);

SELECT setval('catalog_tree_id_seq', COALESCE((SELECT MAX(id) FROM catalog_tree), 0));