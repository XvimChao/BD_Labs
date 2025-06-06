CREATE SEQUENCE path_enum_id_seq;

CREATE TABLE path_enum (
    id INT DEFAULT nextval('path_enum_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL
);

INSERT INTO path_enum (id, title, path) VALUES 
(1, 'Товары', '1/'),
(2, 'Электроника', '1/2/'),
(3, 'ПК, ноутбуки, периферия', '1/2/3/'),
(4, 'Ноутбуки и аксессуары', '1/2/3/4/'),
(5, 'Ноутбуки', '1/2/3/4/5/'),
(6, 'Рюкзаки для ноутбуков', '1/2/3/4/6/'),
(7, 'Чехлы для ноутбуков', '1/2/3/4/7/'),
(8, 'Компьютеры и ПО', '1/2/3/8/'),
(9, 'Персональные компьютеры', '1/2/3/8/9/'),
(10, 'Моноблоки', '1/2/3/8/10/'),
(11, 'Смартфоны и гаджеты', '1/2/11/'),
(12, 'Смартфоны', '1/2/11/12/'),
(13, 'Сотовые телефоны', '1/2/11/13/'),
(14, 'Бытовая техника', '1/14/'),
(15, 'Техника для кухни', '1/14/15/'),
(16, 'Плиты и печи', '1/14/15/16/'),
(17, 'Холодильное оборудование', '1/14/15/17/'),
(18, 'Техника для дома', '1/14/18/'),
(19, 'Стирка и сушка', '1/14/18/19/'),
(20, 'Стиральные машины', '1/14/18/19/20/'),
(21, 'Сушильные машины', '1/14/18/19/21/'),
(22, 'Уборка', '1/14/18/22/'),
(23, 'Напольные пылесосы', '1/14/18/22/23/'),
(24, 'Роботы пылесосы', '1/14/18/22/24/');

SELECT setval('path_enum_id_seq', COALESCE((SELECT MAX(id) FROM path_enum), 0));