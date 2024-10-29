
CREATE TABLE anime_pages (
    id SERIAL PRIMARY KEY NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);

INSERT INTO anime_pages (title, description) VALUES
    ('Naruto', 'Description1'),
    ('Naruto Uzumaki', 'Description2'),
    ('Bleach', 'Description3'),
    ('One Piece', 'Description4'),
    ('Roronoa Zoro', 'Description5'),
    ('Monkey D. Luffy', 'Description6'),
    ('Jujutsu Kaisen', 'Description7'),
    ('Gojo Satoru', 'Description8'),
    ('Gon Frix', 'Description9'),
    ('Killua Zoldic', 'Description10'),
    ('Hunter x Hunter', 'Description11'),
    ('Fullmetal Alchemist: Brotherhood', 'Description12'),
    ('Steins;Gate', 'Description13'),
    ('Gintama', 'Description14'),
    ('Monster', 'Description15'),
    ('Fairy Tail', 'Description16'),
    ('Kurapika', 'Description17'),
    ('Aizen Sosuke', 'Description18'),
    ('Ichigo Kurosaki', 'Description19'),
    ('Happy', 'Description20');

CREATE TABLE anime (
    id INT PRIMARY KEY,
    release_date DATE NOT NULL,
    episode_count INT NOT NULL,
    FOREIGN KEY (id) REFERENCES anime_pages(id)
);

INSERT INTO anime (id,release_date, episode_count) VALUES
    (1,'1999-09-21', 720), -- Naruto
    (3,'2004-10-05', 396), -- Bleach
    (4,'1999-10-20', 1122), -- One Piece
    (7,'2020-10-02', 47), -- JJK
    (11,'2011-10-02', 148), -- HxH
    (12,'2009-04-05', 64), -- Fullmetal Alchemist: Brotherhood
    (13,'2011-04-06', 24), -- Steins;Gate
    (14,'2006-04-04', 367), -- Gintama
    (15,'2004-04-06', 74), -- Monster
    (16,'2009-10-12', 328); -- Fairy Tail

CREATE TABLE characters (
    id INT PRIMARY KEY,
    age INT NOT NULL,
    biography TEXT NOT NULL,
    FOREIGN KEY (id) REFERENCES anime_pages(id)
);

INSERT INTO characters (id, age, biography) VALUES
    (2,33, 'Биография'), -- Naruto
    (5,19, 'Биография'), -- Zoro
    (6,21, 'Биография'), -- Luffy
    (8,29, 'Биография'), -- Gojo
    (9,15, 'Биография'), -- Gon
    (10,15, 'Биография'), -- Killua
    (17,19, 'Биография'), -- Kurapika
    (18,1000, 'Биография'), -- Aizen Sosuke
    (19,27, 'Биография'), -- Ichigo Kurosaki
    (20,6, 'Биография'); -- Happy

CREATE TABLE genres (
	id SERIAL PRIMARY KEY NOT NULL,
	title VARCHAR(255) NOT NULL, 
	description TEXT NOT NULL
);

INSERT INTO genres (title, description) VALUES
	('Комендия', 'Описание'),
	('Романтика', 'Описание'),
	('Детектив', 'Описание'),
	('Драма', 'Описание'),
	('Повседневность', 'Описание'),
	('Боевик', 'Описание'),
	('Сёнен', 'Описание'),
	('Сёдзё', 'Описание'),
	('Сэйнэн', 'Описание'),
	('Меха', 'Описание');

CREATE TABLE anime_genres (
	anime_id INT NOT NULL,
	genres_id INT NOT NULL,
	PRIMARY KEY (anime_id, genres_id),
	FOREIGN KEY (anime_id) REFERENCES anime(id),
	FOREIGN KEY (genres_id) REFERENCES genres(id)
);

INSERT INTO anime_genres (anime_id, genres_id) VALUES
(1, 1),
(1, 4),
(1, 6),
(1, 7),
(3, 6),
(3, 7),
(4, 1),
(4, 4),
(4, 6),
(4, 7);

CREATE TABLE anime_characters (
	anime_id INT NOT NULL,
	character_id INT NOT NULL,
	PRIMARY KEY (anime_id, character_id),
	FOREIGN KEY (anime_id) REFERENCES anime(id),
	FOREIGN KEY (character_id) REFERENCES characters(id)
);

INSERT INTO anime_characters (anime_id, character_id) VALUES
(1, 2), -- Naruto -> Naruto Uzumaki
(3, 18),
(3, 19),
(4, 5),
(4, 6),
(7, 8),
(11, 9),
(11, 10),
(11, 17),
(16, 20);


CREATE TABLE users (
	id SERIAL PRIMARY KEY NOT NULL,
	username VARCHAR(32) NOT NULL,
	email VARCHAR(64) NOT NULL,
	password VARCHAR(64) NOT NULL,
	created_at DATE NOT NULL
);

INSERT INTO users (username, email, password, created_at) VALUES
	('user1', 'primer1@gmail.com', 'password1', '2024-01-10'),
	('user2', 'primer2@gmail.com', 'password2', '2000-02-10'),
	('user3', 'primer3@gmail.com', 'password3', '2015-03-10'),
	('user4', 'primer4@gmail.com', 'password4', '2024-04-10'),
	('user5', 'primer5@gmail.com', 'password5', '2024-05-10'),
	('user6', 'primer6@gmail.com', 'password6', '2020-06-10'),
	('user7', 'primer7@gmail.com', 'password7', '2024-07-02'),
	('user8', 'primer8@gmail.com', 'password8', '2020-08-10'),
	('user9', 'primer9@gmail.com', 'password9', '2023-01-09'),
	('user10', 'primer10@gmail.com','password10','2024-01-10');

CREATE TABLE reviews (
	id SERIAL PRIMARY KEY NOT NULL,
	animes_pages_id INT NOT NULL,
	users_id INT NOT NULL,
	comment TEXT NOT NULL,
	created_at DATE NOT NULL,
	FOREIGN KEY (animes_pages_id) REFERENCES anime_pages(id),
	FOREIGN KEY (users_id) REFERENCES users(id)
);

INSERT INTO reviews(animes_pages_id, users_id, comment, created_at) VALUES
	(1, 1, 'comment1', '2024-01-01'),
	(2, 1, 'comment2', '2024-02-01'),
	(3, 2, 'comment3', '2024-03-01'),
	(4, 2, 'comment4', '2024-04-01'),
	(5, 2, 'comment5', '2024-05-01'),
	(6, 5, 'comment6', '2024-06-01'),
	(7, 6, 'comment7','2024-07-01'),
	(8, 9, 'comment8','2024-08-01'),
	(9, 10,'comment9','2024-09-01'),
	(10, 10,'comment10','2024-10-01');