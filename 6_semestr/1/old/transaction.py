import psycopg2
from psycopg2 import sql

def register_user(conn, login, password, family, name, patronymic, birth_date):
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT register_user(%s, %s, %s, %s, %s, %s)",
                           (login, password, family, name, patronymic, birth_date))
            conn.commit()
            print("User registered successfully.")
    except Exception as e:
        print(f"Error during user registration: {e}")
        conn.rollback()

def get_all_users(conn):
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM users")
            return cursor.fetchall()
    except Exception as e:
        print(f"Error fetching users: {e}")
        return []

# Пример использования
conn = psycopg2.connect(
    dbname="bd_hsl9",
    user="bd_hsl9_user",
    password="3cU6xyUqpiR6UrSnaRelGS3erErjzIHO",
    host="dpg-cute50vnoe9s73990f8g-a.oregon-postgres.render.com",
    port="5432"
)

# Регистрация нового пользователя
register_user(conn, 'new_user', 'secure_password', 'Иванов', 'Иван', 'Иванович', '1990-01-01')

# Вывод всех зарегистрированных пользователей
users = get_all_users(conn)
for user in users:
    print(user)

conn.close()