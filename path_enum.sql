CREATE TABLE path_enum (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL
);

INSERT INTO path_enum (title, path) VALUES 
('Товары', '1/'),
('Электроника', '1/2/'),
('Телефоны', '1/2/3/'),
('Смартфоны', '1/2/3/4/'),
('Apple iPhone', '1/2/3/4/5/'),
('Samsung Galaxy', '1/2/3/4/6/'),
('Huawei P30', '1/2/3/4/7/'),
('Ноутбуки', '1/2/8/'),
('Игровые ноутбуки', '1/2/8/9/'),
('ASUS ROG', '1/2/8/9/10/'),
('Lenovo Legion', '1/2/8/9/11/'),
('Бытовая техника', '1/12/'),
('Холодильники', '1/12/13/'),
('LG Refrigerator', '1/12/13/14/'),
('Samsung Refrigerator', '1/12/13/15/'),
('Кухонные приборы', '1/12/16/'),
('Микроволновые печи', '1/12/16/17/'),
('LG Microwave', '1/12/16/17/18/');

