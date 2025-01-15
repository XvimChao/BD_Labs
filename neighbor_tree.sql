CREATE SEQUENCE neighbor_tree_id_seq;

CREATE TABLE neighbor_tree (
    id INT DEFAULT nextval('neighbor_tree_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    parent_id INT,
    FOREIGN KEY (parent_id) REFERENCES neighbor_tree(id) ON DELETE CASCADE  -- Устанавливаем внешний ключ
);



INSERT INTO neighbor_tree (id, title, parent_id) VALUES
    (1, 'Товары', NULL),
    (2, 'Электроника', 1),
    (3, 'ПК, ноутбуки, периферия', 2),
    (4, 'Ноутбуки и аксессуары', 3),
    (5, 'Ноутбуки', 4),
    (6, 'Рюкзаки для ноутбуков', 4),
    (7, 'Чехлы для ноутбуков', 4),
    (8, 'Компьютеры и ПО', 3),
    (9, 'Персональные компьютеры', 8),
    (10, 'Моноблоки', 8),
    (11, 'Смартфоны и гаджеты', 2),
    (12, 'Смартфоны', 11),
    (13, 'Сотовые телефоны', 11),
    (14, 'Бытовая техника', 1),
    (15, 'Техника для кухни', 14),
    (16, 'Плиты и печи', 15),
    (17, 'Холодильное оборудование', 15),
    (18, 'Техника для дома', 14),
    (19, 'Стирка и сушка', 18),
    (20, 'Стиральные машины', 19),
    (21, 'Сушильные машины', 19),
    (22, 'Уборка', 18),
    (23, 'Напольные пылесосы', 22),
    (24, 'Роботы пылесосы', 22);

SELECT setval('neighbor_tree_id_seq', COALESCE((SELECT MAX(id) FROM neighbor_tree), 0));