DROP SEQUENCE IF EXISTS user_id_seq CASCADE;

CREATE SEQUENCE user_id_seq;

DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
	id INT DEFAULT nextval('user_id_seq') PRIMARY KEY,
	login VARCHAR(32) NOT NULL,
	password VARCHAR(64) NOT NULL,
	registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	CHECK(TRIM(login) <> '')
);

INSERT INTO users(id, login, password, registration_date) VALUES
	(1, 'user1', 'password1', '2024-01-10'),
	(2, 'user2', 'password2', '2000-02-10'),
	(3, 'user3', 'password3', '2015-03-10')


SELECT setval('user_id_seq', (SELECT MAX(id) FROM users));


DROP SEQUENCE IF EXISTS account_id_seq CASCADE;

CREATE SEQUENCE account_id_seq;

DROP TABLE IF EXISTS accounts CASCADE;

CREATE TABLE accounts (
	user_id INT DEFAULT nextval('user_id_seq') PRIMARY KEY,
	family VARCHAR(64) NOT NULL,
	name VARCHAR(64) NOT NULL,
    patronymic VARCHAR(64) NOT NULL,
	birth_date DATE,
	CHECK(TRIM(family) <> ''),
    CHECK(TRIM(name) <> ''),
    CHECK(TRIM(patronymic) <> ''),
    CHECK(birth_date > '1900-01-01' AND birth_date < CURRENT_TIMESTAMP),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO accounts(user_id, family, name, patronymic, birth_date) VALUES
    (1, family1, name1, patronymic1, '2004-10-18'),
    (2, family2, name2, patronymic2, '2000-12-22'),
    (3, family3, name3, patronymic3, '2000-12-12')

SELECT setval('account_id_seq', (SELECT MAX(user_id) FROM accounts));
