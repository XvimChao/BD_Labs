from procedures import register_user
from models import User, Session
from datetime import datetime

def print_all_users():
    session = Session()
    users = session.query(User).all()
    for user in users:
        print(f"ID: {user.id}, Логин: {user.login}, Дата регистрации: {user.registration_date}, Фамилия: {user.account.family}, Имя: {user.account.name}, Отчество: {user.account.patronymic}, Дата рождения: {user.account.birth_date}")
        

def main():
    while True:
        print("\n1. Зарегистрировать пользователя")
        print("2. Показать всех пользователей")
        print("3. Выйти")
        choice = input("Выберите действие: ")

        if choice == "1":
            login = input("Логин: ")
            password = input("Пароль: ")
            family = input("Фамилия: ")
            name = input("Имя: ")
            patronymic = input("Отчество: ")
            birth_date = input("Дата рождения (ГГГГ-ММ-ДД): ")

            try:
                birth_date = datetime.strptime(birth_date, "%Y-%m-%d")
                register_user(login, password, datetime.now(), family, name, patronymic, birth_date)
                print("Пользователь успешно зарегистрирован!")
            except Exception as e:
                print(f"Ошибка: {e}")

        elif choice == "2":
            print_all_users()

        elif choice == "3":
            break

        else:
            print("Неверный выбор. Попробуйте снова.")

if __name__ == "__main__":
    main()