
CREATE SEQUENCE anime_pages_id_seq;

CREATE TABLE anime_pages (
    id INT DEFAULT nextval('anime_pages_id_seq') PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
	CHECK (TRIM(title) <> ''),
	CHECK (TRIM(description) <> '')
);

INSERT INTO anime_pages (id, title, description) VALUES
    (1,'Naruto', 'Description1'),
    (2,'Naruto Uzumaki', 'Description2'),
    (3,'Bleach', 'Description3'),
    (4,'One Piece', 'Description4'),
    (5,'Roronoa Zoro', 'Description5'),
    (6,'Monkey D. Luffy', 'Description6'),
    (7,'Jujutsu Kaisen', 'Description7'),
    (8,'Gojo Satoru', 'Description8'),
    (9,'Gon Frix', 'Description9'),
    (10,'Killua Zoldic', 'Description10'),
    (11,'Hunter x Hunter', 'Description11'),
    (12,'Fullmetal Alchemist: Brotherhood', 'Description12'),
    (13,'Steins;Gate', 'Description13'),
    (14,'Gintama', 'Description14'),
    (15,'Monster', 'Description15'),
    (16,'Fairy Tail', 'Description16'),
    (17,'Kurapika', 'Description17'),
    (18,'Aizen Sosuke', 'Description18'),
    (19,'Ichigo Kurosaki', 'Description19'),
    (20,'Happy', 'Description20');

SELECT setval('anime_pages_id_seq', COALESCE((SELECT MAX(id)  FROM anime_pages), 0));

CREATE TABLE anime (
    id INT PRIMARY KEY NOT NULL,
    release_date DATE CHECK (release_date >= '1900-01-01' AND release_date <= CURRENT_DATE),
    episode_count INT CHECK (episode_count >= 0 AND episode_count < 15000),
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
    age INT CHECK(age > 0),
    biography TEXT NOT NULL,
    FOREIGN KEY (id) REFERENCES anime_pages(id),
	CHECK (TRIM(biography) <> '')
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

CREATE SEQUENCE genres_id_seq START WITH 11;
CREATE TABLE genres (
	id INT DEFAULT nextval('genres_id_seq') PRIMARY KEY,
	title VARCHAR(255) NOT NULL, 
	description TEXT NOT NULL,
	CHECK (TRIM(title) <> ''),
	CHECK (TRIM(description) <> '')
);

INSERT INTO genres (id, title, description) VALUES
	(1,'Комендия', 'Описание'),
	(2,'Романтика', 'Описание'),
	(3,'Детектив', 'Описание'),
	(4,'Драма', 'Описание'),
	(5,'Повседневность', 'Описание'),
	(6,'Боевик', 'Описание'),
	(7,'Сёнен', 'Описание'),
	(8,'Сёдзё', 'Описание'),
	(9,'Сэйнэн', 'Описание'),
	(10,'Меха', 'Описание');

SELECT setval('genres_id_seq', COALESCE((SELECT MAX(id)  FROM genres), 0));

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

CREATE SEQUENCE users_id_seq START WITH 11;
CREATE TABLE users (
	id INT DEFAULT nextval('users_id_seq') PRIMARY KEY,
	username VARCHAR(32) UNIQUE NOT NULL,
	email VARCHAR(64) UNIQUE NOT NULL,
	password VARCHAR(64) NOT NULL,
	created_at DATE CHECK (created_at >= '1990-01-01' AND created_at <= CURRENT_DATE),
	CHECK (TRIM(username) <> ''),
	CHECK (TRIM(email) <> ''),
	
);

INSERT INTO users (id, username, email, password, created_at) VALUES
	(1,'user1', 'primer1@gmail.com', 'password1', '2024-01-10'),
	(2,'user2', 'primer2@gmail.com', 'password2', '2000-02-10'),
	(3,'user3', 'primer3@gmail.com', 'password3', '2015-03-10'),
	(4,'user4', 'primer4@gmail.com', 'password4', '2024-04-10'),
	(5,'user5', 'primer5@gmail.com', 'password5', '2024-05-10'),
	(6,'user6', 'primer6@gmail.com', 'password6', '2020-06-10'),
	(7,'user7', 'primer7@gmail.com', 'password7', '2024-07-02'),
	(8,'user8', 'primer8@gmail.com', 'password8', '2020-08-10'),
	(9,'user9', 'primer9@gmail.com', 'password9', '2023-01-09'),
	(10,'user10', 'primer10@gmail.com','password10','2024-01-10');

SELECT setval('users_id_seq', COALESCE((SELECT MAX(id)  FROM users), 0));

CREATE SEQUENCE reviews_id_seq START WITH 11;
CREATE TABLE reviews (
	id INT DEFAULT nextval('reviews_id_seq') PRIMARY KEY,
	animes_pages_id INT NOT NULL,
	users_id INT NOT NULL,
	comment TEXT NOT NULL,
	created_at DATE CHECK (created_at >= '1990-01-01' AND created_at <= CURRENT_DATE),
	FOREIGN KEY (animes_pages_id) REFERENCES anime_pages(id),
	FOREIGN KEY (users_id) REFERENCES users(id),
	CHECK (TRIM(comment) <> '')
);

INSERT INTO reviews(id, animes_pages_id, users_id, comment, created_at) VALUES
	(1, 1, 1, 'comment1', '2024-01-01'),
	(2, 2, 1, 'comment2', '2024-02-01'),
	(3, 5, 2, 'comment3', '2024-03-01'),
	(4, 5, 2, 'comment4', '2024-04-01'),
	(5, 5, 2, 'comment5', '2024-05-01'),
	(6, 6, 5, 'comment6', '2024-06-01'),
	(7, 7, 6, 'comment7','2024-07-01'),
	(8, 8, 9, 'comment8','2024-08-01'),
	(9, 8, 10,'comment9','2024-09-01'),
	(10, 10, 10,'comment10','2024-10-01');

SELECT setval('reviews_id_seq', COALESCE((SELECT MAX(id)  FROM reviews), 0));