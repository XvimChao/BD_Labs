from procedures import register_user
from models import User, Session
from datetime import datetime, timedelta
from passlib.context import CryptContext
import getpass
import re  # Для проверки букв в имени, фамилии и отчестве

def is_valid_name(name):
    """Проверка, что строка содержит только буквы."""
    return bool(re.match("^[A-Za-zА-Яа-я]+$", name))

def is_valid_birth_date(birth_date):
    """Проверка, что дата рождения корректна и человеку не больше 150 лет."""
    today = datetime.today()
    min_birth_date = today - timedelta(days=150 * 365)  # 150 лет назад
    return min_birth_date <= birth_date <= today

def is_login_unique(login):
    """Проверка, что логин уникален."""
    session = Session()
    user = session.query(User).filter(User.login.ilike(login)).first()
    session.close()
    return user is None

def format_name(name):
    """Первая буква заглавная, остальные строчные."""
    return name.capitalize()

def get_secure_password():
    """Безопасный ввод пароля с подтверждением."""
    while True:
        password = getpass.getpass("Пароль: ")
        confirm = getpass.getpass("Подтвердите пароль: ")
        
        if password != confirm:
            print("Пароли не совпадают. Попробуйте снова.")
            continue
            
        if len(password) < 6:
            print("Пароль должен содержать минимум 6 символов.")
            continue
            
        if len(password) > 64:
            print("Пароль не должен превышать 64 символа.")
            continue
            
        return password

    
# Настройка хэширования паролей
pwd_context = CryptContext(
    schemes=["pbkdf2_sha256"],
    default="pbkdf2_sha256",
    pbkdf2_sha256__default_rounds=30000
)

def hash_password(password: str) -> str:
    """Хэширование пароля перед сохранением в БД"""
    return pwd_context.hash(password)

def verify_password(plain_password: str, hashed_password: str) -> bool:
    """Проверяет пароль против хэша."""
    return pwd_context.verify(plain_password, hashed_password)


def print_all_users():
    session = Session()
    users = session.query(User).all()
    
    # Собираем все данные пользователей в список списков
    all_user_data = []
    for user in users:
        user_data = [
            str(user.id),
            user.login,
            user.registration_date.strftime("%Y-%m-%d"),
            user.account.family if user.account else "N/A",
            user.account.name if user.account else "N/A",
            user.account.patronymic if user.account and user.account.patronymic else "N/A",
            user.account.birth_date.strftime("%Y-%m-%d") if user.account else "N/A"
        ]
        all_user_data.append(user_data)
    
    # Если нет пользователей, выходим
    if not all_user_data:
        print("Нет данных о пользователях.")
        session.close()
        return
    
    # Определяем ширину каждого столбца
    headers = ["ID", "Логин", "Дата регистрации", "Фамилия", "Имя", "Отчество", "Дата рождения"]
    num_columns = len(headers)
    
    # Инициализируем список с максимальными длинами (начинаем с длин заголовков)
    column_widths = [len(header) for header in headers]
    
    # Обновляем максимальные длины на основе данных пользователей
    for row in all_user_data:
        for i in range(num_columns):
            column_widths[i] = max(column_widths[i], len(row[i]))
    
    # Форматируем строку с заголовками
    header_format = "  ".join([f"{{:<{width}}}" for width in column_widths])
    print(header_format.format(*headers))
    
    for user_data in all_user_data:
        print(header_format.format(*user_data))
    
    session.close()

def main():
    while True:
        print("\n1. Зарегистрировать пользователя")
        print("2. Показать всех пользователей")
        print("3. Выйти")
        choice = input("Выберите действие: ")

        if choice == "1":
            while True:
                login = input("Логин: ").strip().replace(" ", "")
                if not login:
                    print("Логин не может быть пустым. Попробуйте снова.")
                    continue
                if len(login) > 32:
                    print("Логин не может быть длиннее 32 символов. Попробуйте снова.")
                    continue
                if not is_login_unique(login):
                    print("Логин уже существует. Пожалуйста, выберите другой логин.")
                    continue
                break

           
            password = get_secure_password()
            hashed_password = hash_password(password)
                

            while True:
                family = input("Фамилия: ").strip()
                if not family:
                    print("Фамилия не может быть пустой. Попробуйте снова.")
                    continue
                if len(family) > 64:
                    print("Фамилия не может быть длиннее 64 символов. Попробуйте снова.")
                    continue
                if not is_valid_name(family):
                    print("Фамилия должна содержать только буквы. Попробуйте снова.")
                    continue
                family = format_name(family)
                break

            while True:
                name = input("Имя: ").strip()
                if not name:
                    print("Имя не может быть пустым. Попробуйте снова.")
                    continue
                if len(name) < 2 or len(name) > 64:
                    print("Имя должно содержать от 2 до 64 символов. Попробуйте снова.")
                    continue
                if not is_valid_name(name):
                    print("Имя должно содержать только буквы. Попробуйте снова.")
                    continue
                name = format_name(name)
                break

            while True:
                patronymic = input("Отчество: ").strip()
                if patronymic:
                    if len(patronymic) < 3 or len(patronymic) > 64:
                        print("Отчество должно содержать от 3 до 64 символов. Попробуйте снова.")
                        continue
                    if not is_valid_name(patronymic):
                        print("Отчество должно содержать только буквы. Попробуйте снова.")
                        continue
                    patronymic = format_name(patronymic)
                break

            while True:
                birth_date = input("Дата рождения (ДД-ММ-ГГГГ): ").strip()
                try:
                    birth_date = datetime.strptime(birth_date, "%d-%m-%Y")
                    if not is_valid_birth_date(birth_date):
                        print("Дата рождения некорректна.Попробуйте снова.")
                        continue
                    break
                except ValueError:
                    print("Неверный формат даты. Используйте формат ДД-ММ-ГГГГ. Попробуйте снова.")

            try:
                register_user(login, hashed_password, datetime.now(), family, name, patronymic, birth_date)
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