CREATE OR REPLACE FUNCTION register_user(
    p_login VARCHAR,
    p_password VARCHAR,
    p_family VARCHAR,
    p_name VARCHAR,
    p_patronymic VARCHAR,
    p_birth_date DATE
) 
RETURNS VOID AS $$
BEGIN
    INSERT INTO users (login, password) VALUES (p_login, crypt(p_password, gen_salt('bf')));
    INSERT INTO accounts (user_id, family, name, patronymic, birth_date)
    VALUES (currval(pg_get_serial_sequence('users', 'id')), p_family, p_name, p_patronymic, p_birth_date);
END;
$$ LANGUAGE plpgsql;