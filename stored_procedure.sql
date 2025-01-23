--Хранимая процедура для создания пользователя
CREATE OR REPLACE PROCEDURE UserCreate(
    IN p_username VARCHAR(32),
    IN p_email VARCHAR(64),
    IN p_password VARCHAR(64)
)
LANGUAGE plpgsql
AS $$
BEGIN
    IF TRIM(p_username) = '' THEN
        RAISE EXCEPTION 'Username cannot be empty';
    END IF;

    IF TRIM(p_email) = '' THEN
        RAISE EXCEPTION 'Email cannot be empty';
    END IF;

    IF LENGTH(p_password) < 6 THEN
        RAISE EXCEPTION 'Password must be at least 6 characters long';
    END IF;

    -- Вставка пользователя
    INSERT INTO users (username, email, password) VALUES (p_username, p_email, p_password);
    
    RAISE NOTICE 'User created: %', p_username;
EXCEPTION
    WHEN unique_violation THEN
        RAISE EXCEPTION 'A user with the email % already exists.', p_email;
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error creating user: %', SQLERRM;
END;
$$
;

--Хранимая процедура для получения всех пользователей
CREATE OR REPLACE PROCEDURE UserRetrieveAll()
LANGUAGE plpgsql
AS $$
DECLARE
    record users%ROWTYPE;
BEGIN
    FOR record IN SELECT * FROM users LOOP
        RAISE NOTICE 'User: ID = %, Username = %, Email = %', record.user_id, record.username, record.email;
    END LOOP;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error retrieving users: %', SQLERRM;
END;
$$
;
--Хранимая процедура для получения пользователя по ID
CREATE OR REPLACE PROCEDURE UserRetrieve(
    IN p_user_id BIGINT
)
LANGUAGE plpgsql
AS $$
DECLARE
    user_record RECORD;
BEGIN
    -- Валидация входного параметра
    IF p_user_id IS NULL THEN
        RAISE EXCEPTION 'User ID cannot be null';
    END IF;

    SELECT * INTO user_record FROM users WHERE user_id = p_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'User with ID % not found', p_user_id;
    END IF;

    RAISE NOTICE 'User found: ID = %, Username = %, Email = %', user_record.user_id, user_record.username, user_record.email;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error retrieving user: %', SQLERRM;
END;
$$
;
--Хранимая процедура для обновления пользователя
CREATE OR REPLACE PROCEDURE UserUpdate(
    IN p_user_id INT,
    IN p_username VARCHAR(32),
    IN p_email VARCHAR(64),
    IN p_password VARCHAR(64)
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Валидация входных параметров
    IF TRIM(p_username) = '' THEN
        RAISE EXCEPTION 'Username cannot be empty';
    END IF;

    IF TRIM(p_email) = '' THEN
        RAISE EXCEPTION 'Email cannot be empty';
    END IF;

    IF LENGTH(p_password) < 6 THEN
        RAISE EXCEPTION 'Password must be at least 6 characters long';
    END IF;

    UPDATE users 
    SET username = p_username, email = p_email, password = p_password 
    WHERE user_id = p_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'User with ID % not found for update', p_user_id;
    END IF;

    RAISE NOTICE 'User updated: ID = %', p_user_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error updating user: %', SQLERRM;
END;
$$
;

--Хранимая процедура для удаления пользователя
CREATE OR REPLACE PROCEDURE UserDelete(
    IN p_user_id INT
)
LANGUAGE plpgsql
AS $$
BEGIN
    DELETE FROM users WHERE user_id = p_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'User with ID % not found for deletion', p_user_id;
    END IF;

    RAISE NOTICE 'User with ID % deleted successfully', p_user_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error deleting user: %', SQLERRM;
END;
$$
;
--Хранимая процедура для массового удаления пользователей
CREATE OR REPLACE PROCEDURE UserDeleteMany(
    IN user_ids INT[]
)
LANGUAGE plpgsql
AS $$
BEGIN
   DELETE FROM users WHERE user_id = ANY(user_ids);
   
   -- Проверяем, были ли удалены пользователи (если не найдено ни одного)
   IF NOT FOUND THEN 
       RAISE NOTICE 'No users found for deletion.';
   ELSE 
       RAISE NOTICE 'Users deleted successfully'; 
   END IF; 
EXCEPTION 
   WHEN OTHERS THEN 
       RAISE EXCEPTION 'Error deleting users: %', SQLERRM; 
END; 
$$
;


/*
-- Создание пользователя:
CALL UserCreate('new_user', 'new_email@gmail.com', 'password123');

-- Получение всех пользователей:
CALL UserRetrieveAll();

-- Получение пользователя по ID:
CALL UserRetrieve(2);

-- Обновление пользователя:
CALL UserUpdate(2, 'updated_user', 'updated_email@gmail.com', 'newpassword');

-- Удаление пользователя:
CALL UserDelete(2);

-- Массовое удаление пользователей:
CALL UserDeleteMany(ARRAY[1, 2, 3]);
*/


--Функция для создания пользователя
CREATE OR REPLACE FUNCTION CreateUser(
    p_username VARCHAR(32),
    p_email VARCHAR(64),
    p_password VARCHAR(64)
)
RETURNS VOID AS $$
BEGIN
    IF TRIM(p_username) = '' THEN
        RAISE EXCEPTION 'Username cannot be empty';
    END IF;

    IF TRIM(p_email) = '' THEN
        RAISE EXCEPTION 'Email cannot be empty';
    END IF;

    IF LENGTH(p_password) < 6 THEN
        RAISE EXCEPTION 'Password must be at least 6 characters long';
    END IF;

    -- Вставка пользователя
    INSERT INTO users (username, email, password) VALUES (p_username, p_email, p_password);
    
    RAISE NOTICE 'User created: %', p_username;
EXCEPTION
    WHEN unique_violation THEN
        RAISE EXCEPTION 'A user with the email % already exists.', p_email;
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error creating user: %', SQLERRM;
END;
$$
 LANGUAGE plpgsql;

--Функция для получения всех пользователей
CREATE OR REPLACE FUNCTION RetrieveAllUsers()
RETURNS TABLE(user_id INT, username VARCHAR, email VARCHAR) AS $$
BEGIN
    -- Указываем таблицу явно
    RETURN QUERY SELECT u.user_id, u.username, u.email FROM users u;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error retrieving users: %', SQLERRM;
END;
$$
 LANGUAGE plpgsql;

--Функция для получения пользователя по ID
CREATE OR REPLACE FUNCTION RetrieveUser(
    p_user_id BIGINT
)
RETURNS TABLE(user_id INT, username VARCHAR, email VARCHAR) AS $$
BEGIN
    -- Указываем таблицу явно
    RETURN QUERY SELECT u.user_id, u.username, u.email FROM users u WHERE u.user_id = p_user_id;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error retrieving user: %', SQLERRM;
END;
$$
 LANGUAGE plpgsql;

--Функция для обновления пользователя
 CREATE OR REPLACE FUNCTION UpdateUser(
    p_user_id INT,
    p_username VARCHAR(32),
    p_email VARCHAR(64),
    p_password VARCHAR(64)
)
RETURNS VOID AS $$
BEGIN
    -- Валидация входных параметров
    IF TRIM(p_username) = '' THEN
        RAISE EXCEPTION 'Username cannot be empty';
    END IF;

    IF TRIM(p_email) = '' THEN
        RAISE EXCEPTION 'Email cannot be empty';
    END IF;

    IF LENGTH(p_password) < 6 THEN
        RAISE EXCEPTION 'Password must be at least 6 characters long';
    END IF;

    UPDATE users 
    SET username = p_username, email = p_email, password = p_password 
    WHERE user_id = p_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'User with ID % not found for update', p_user_id;
    END IF;

    RAISE NOTICE 'User updated: ID = %', p_user_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error updating user: %', SQLERRM;
END;
$$
 LANGUAGE plpgsql;

--Функция для удаления пользователя
 CREATE OR REPLACE FUNCTION DeleteUser(
    p_user_id INT
)
RETURNS VOID AS $$
BEGIN
    DELETE FROM users WHERE user_id = p_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'User with ID % not found for deletion', p_user_id;
    END IF;

    RAISE NOTICE 'User with ID % deleted successfully', p_user_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error deleting user: %', SQLERRM;
END;
$$
 LANGUAGE plpgsql;


--Функция для массового удаления пользователей
 CREATE OR REPLACE FUNCTION DeleteManyUsers(
    user_ids INT[]
)
RETURNS VOID AS $$
BEGIN
   DELETE FROM users WHERE user_id = ANY(user_ids);
   
   -- Проверяем, были ли удалены пользователи (если не найдено ни одного)
   IF NOT FOUND THEN 
       RAISE NOTICE 'No users found for deletion.';
   ELSE 
       RAISE NOTICE 'Users deleted successfully'; 
   END IF; 
EXCEPTION 
   WHEN OTHERS THEN 
       RAISE EXCEPTION 'Error deleting users: %', SQLERRM; 
END; 
$$
 LANGUAGE plpgsql;



 /*
-- Создание пользователя:
SELECT CreateUser('new_user', 'new_email@gmail.com', 'password123');

-- Получение всех пользователей:
SELECT * FROM RetrieveAllUsers();

-- Получение пользователя по ID:
SELECT * FROM RetrieveUser(2);  -- Замените 2 на нужный ID

-- Обновление пользователя:
SELECT UpdateUser(2, 'updated_user', 'updated_email@gmail.com', 'newpassword');

-- Удаление пользователя:
SELECT DeleteUser(2);

-- Массовое удаление пользователей:
SELECT DeleteManyUsers(ARRAY[1, 2, 3]);  -- Замените [1, 2, 3] на нужные ID пользователей для удаления.

 */
