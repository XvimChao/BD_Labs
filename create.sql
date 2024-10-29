

CREATE TABLE anime_pages(
    id INT PRIMARY KEY  NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);
INSERT INTO anime_pages(id, title, description) VALUES
    (1, "Naruto", "Description1"),
    (2, "Naruto Uzumaki", "Description2"),
    (3, "Bleach", "Description3"),
    (4, "One Piece", "Description4"),
    (5, "Roronoa Zoro", "Description5"),
    (6, "Monkey D. Luffy", "Description6"),
    (7, "Jujutsu Kaisen", "Description7"),
    (8, "Gojo Satoru", "Description8"),
    (9, "Gon Frix", "Description9"),
    (10, "Killua Zoldic", "Description10");
    

CREATE TABLE anime (
    anime_id INT,
	anime_id SERIAL PRIMARY KEY NOT NULL, -- SERIAL генерация уникальных значений
	release_date DATE NOT NULL,
    episode_count INT NOT NULL,
);
INSERT INTO anime(id, release_date, episode_count) VALUES
    (1, 21.09.1999, 720),
    (2, 21.09.1999, 720),
    (3, 21.09.1999, 720),
    (4, 21.09.1999, 720),
    (5, 21.09.1999, 720),
    (6, 21.09.1999, 720),
    (7, 21.09.1999, 720),
    (8, 21.09.1999, 720),
    (9, 21.09.1999, 720),
    (10, 21.09.1999, 720);

CREATE TABLE characters (
	id SERIAL PRIMARY KEY,
	age INT,
	biography TEXT
);

INSERT INTO characters (id, age, biography) VALUES
    (1, 33, "Биография"),
    (2, 33, "Биография"),
    (3, 33, "Биография"),
    (4, 33, "Биография"),
    (5, 33, "Биография"),
    (6, 33, "Биография"),
    (7, 33, "Биография"),
    (8, 33, "Биография"),
    (9, 33, "Биография"),
    (10, 33, "Биография"); 

CREATE TABLE genres (
	id SERIAL PRIMARY KEY NOT NULL,
	title VARCHAR(255) NOT NULL, 
	description TEXT NOT NULL
);

INSERT INTO genres(id, title, description) VALUES
    (1, "Комендия", "Описание"),
    (2, "Комендия", "Описание"),
    (3, "Комендия", "Описание"),
    (4, "Комендия", "Описание"),
    (5, "Комендия", "Описание"),
    (6, "Комендия", "Описание"),
    (7, "Комендия", "Описание"),
    (8, "Комендия", "Описание"),
    (9, "Комендия", "Описание"),
    (10, "Комендия", "Описание");

CREATE TABLE anime_genres(
	anime_id    INT    NOT NULL,
	genres_id    INT    NOT NULL,
	FOREIGN KEY (anime_id)   REFERENCES   anime(id),
	FOREIGN KEY (genres_id)  REFERENCES   genres(id);
);

CREATE TABLE anime_characters(
	anime_id INT,
	characters_id INT,
    PRIMARY KEY (anime_id, character_id),
    FOREIGN KEY (anime_id) REFERENCES anime(id),
    FOREIGN KEY (characters_id) REFERENCES characters(id),
);

CREATE TABLE users (
	id SERIAL PRIMARY KEY,
	username VARCHAR(32),
	email VARCHAR(64),
	password VARCHAR(64),
	created_at DATE
);

INSERT INTO users(id, username, email, password, created_at) VALUES
    (1, "user1", "primer1@gmail.com", "password1", 1.10.2024),
    (2, "user2", "primer2@gmail.com", "password2", 2.10.2000),
    (3, "user3", "primer3@gmail.com", "password3", 3.10.2015),
    (4, "user4", "primer4@gmail.com", "password4", 4.10.2024),
    (5, "user5", "primer5@gmail.com", "password5", 5.10.2024),
    (6, "user6", "primer6@gmail.com", "password6", 6.10.2020),
    (7, "user7", "primer7@gmail.com", "password7", 7.2.2024),
    (8, "user8", "primer8@gmail.com", "password8", 8.10.2020),
    (9, "user9", "primer9@gmail.com", "password9", 9.1.2023),
    (10, "user10", "primer10@gmail.com", "password10", 10.1.2024);

CREATE TABLE reviews (
	id SERIAL PRIMARY KEY,
	anime_pages_id INT,
	users_id INT,
	comment text,
    created_at DATE,
	FOREIGN KEY (anime_pages_id) REFERENCES anime_pages(id),
    FOREIGN KEY (users_id) REFERENCES users(id),
);

INSERT INTO reviews(id, anime_page_id, user_id, comment, created_at) VALUES
    (1, 1, 1, "commetn1", 24.10.2024),
    (2, 2, 1, "commetn1", 24.10.2024),
    (3, 1, 1, "commetn1", 24.10.2024),
    (4, 1, 1, "commetn1", 24.10.2024),
    (5, 1, 1, "commetn1", 24.10.2024),
    (6, 1, 1, "commetn1", 24.10.2024),
    (7, 1, 1, "commetn1", 24.10.2024),
    (8, 1, 1, "commetn1", 24.10.2024),
    (9, 1, 1, "commetn1", 24.10.2024),
    (10, 1, 1, "commetn1", 24.10.2024),
CREATE TABLE 