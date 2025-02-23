
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
	id SERIAL PRIMARY KEY,
	login VARCHAR(50) NOT NULL,
	password VARCHAR(255) NOT NULL,
	registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	CHECK(TRIM(login) <> '')
);

DROP TABLE IF EXISTS accounts CASCADE;

CREATE TABLE accounts (
	user_id INT REFERENCES users(id) ON DELETE CASCADE,
	family VARCHAR(50) NOT NULL,
	name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
	birth_date DATE NOT NULL,
    PRIMARY KEY(user_id),
	CHECK(TRIM(family) <> ''),
    CHECK(TRIM(name) <> ''),
    CHECK(TRIM(patronymic) <> ''),
    CHECK(birth_date >= (CURRENT_DATE - INTERVAL '120 years') AND birth_date < CURRENT_TIMESTAMP)
);