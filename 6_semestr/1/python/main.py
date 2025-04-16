from procedures import register_user
from models import User, Session
from datetime import datetime, timedelta
from passlib.context import CryptContext
import re


def is_valid_fio(name, is_family):
    """Проверка, что строка соответствует допустимым значениям."""
    
    # Недопустимые символы
    forbidden_chars = (
        "0123456789"
        "!\"#$%&*+/:;<=>?@[\\]^_`{|}~"
    )
    
    # Проверка на недопустимые символы
    if any(c in forbidden_chars for c in name):
        return False

    #  Проверка первого/последнего символа для фамилии
    if is_family:
        if name[0] in  {'.', '-', "'", ' ', ','} or name[-1] in {'.', '-', "'", ' ', ','}:
            return False
        if name[0] == ')' or name[-1] == '(':
            return False
        if name == '.' or name == '-' or name == "'" or name == ' ' or name == ',':
            return False
        if name == ')' or name == '(':
            return False
    
    # Проверка первого/последнего символа для имени/отчества
    else:
        if name[0] in {'-', "'", ' ', ','} or name[-1] in {'-', "'", ' ', ','}:
            return False
        if name[0] == '.':
            return False
        if name[0] == ')' or name[-1] == '(':
            return False
        if name in {'-', "'", ' ', ',', '.', ')', '('}:
            return False
    
    # Проверка на два и более подряд специальных символов
    special_chars = {'.', '-', "'", ' ', ',', '(', ')'}
    prev_char = None
    for char in name:
        if char in special_chars and prev_char in special_chars:
            return False
        prev_char = char
    
    # Проверка на недопустимые последовательности символов
    restricted_sequences = ['.', '-', "'", ',', '(', ')']
    for i in range(len(name)-1):
        if name[i] in restricted_sequences and name[i+1] in restricted_sequences:
            return False
    
    # Проверка на непарные скобки
    if ('(' in name and ')' not in name) or (')' in name and '(' not in name):
        return False
    
    return True

def is_valid_family(family):
    return is_valid_fio(family, is_family = True)

def is_valid_name(name):
    return is_valid_fio(name, is_family = False)

def is_valid_patronymic(patronymic):
    return is_valid_fio(patronymic, is_family=False)

# Old
def is_valid_birth_date(birth_date):
    """Проверка, что дата рождения корректна и человеку не больше 150 лет."""
    today = datetime.today()
    
    # Рассчитываем минимальную допустимую дату рождения (150 лет)
    min_birth_date = today.replace(year=today.year - 150)
    
    # Корректируем, если текущая дата - 29 февраля, а год не високосный
    if today.month == 2 and today.day == 29 and not is_leap(today.year):
        min_birth_date = today.replace(year=today.year - 150, month=3, day=1)
    
    return min_birth_date <= birth_date <= today

def is_leap(year):
    """Проверка, является ли год високосным"""
    return year % 4 == 0 and (year % 100 != 0 or year % 400 == 0)

def is_login_unique(login):
    """Проверка, что логин уникален."""
    session = Session()
    user = session.query(User).filter(User.login.ilike(login)).first()
    session.close()
    return user is None

def format_name(name):
    """Первая буква заглавная, остальные строчные."""
    return name.capitalize()
    
def get_valid_birth_date():
    while True:
        try:
            year = int(input("Год рождения (ГГГГ): "))
            month = int(input("Месяц рождения (1-12): "))
            day = int(input("День рождения (1-31): "))

            birth_date = datetime(year, month, day)
            
            if not is_valid_birth_date(birth_date):
                print("Дата рождения некорректна. Попробуйте снова.")
                continue
                
            return birth_date
        except ValueError:
            print("Некорректная дата. Пожалуйста, введите числа для дня, месяца и года.")

def print_all_users():
    session = Session()
    users = session.query(User).all()
    
    all_user_data = []
    for user in users:
        user_data = [
            str(user.id),
            user.login,
            user.password,
            user.registration_date.strftime("%d-%m-%Y"),
            user.account.family if user.account else "-",
            user.account.name if user.account else "-",
            user.account.patronymic if user.account and user.account.patronymic else "-",
            user.account.birth_date.strftime("%d-%m-%Y") if user.account else "-"
        ]
        all_user_data.append(user_data)
    
    # Если нет пользователей, выходим
    if not all_user_data:
        print("Нет данных о пользователях.")
        session.close()
        return
    
    # Определяем ширину каждого столбца
    headers = ["ID", "Логин", "Пароль", "Дата регистрации", "Фамилия", "Имя", "Отчество", "Дата рождения"]
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
        choice = input("Выберите действие: ").strip()

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
                    print("Логин уже существует. Выберите другой.")
                    continue
                break

            while True:
                password = input("Пароль: ")
                confirm = input("Подтвердите пароль: ")
                
                if password != confirm:
                    print("Пароли не совпадают. Попробуйте снова.")
                    continue
                    
                if len(password) < 6:
                    print("Пароль должен содержать минимум 6 символов.")
                    continue
                    
                if len(password) > 64:
                    print("Пароль не должен превышать 64 символа.")
                    continue
                break

            while True:
                family = input("Фамилия: ").strip()
                if not family:
                    print("Фамилия не может быть пустой. Попробуйте снова.")
                    continue
                if len(family) > 64:
                    print("Фамилия не может быть длиннее 64 символов. Попробуйте снова.")
                    continue
                if not is_valid_family(family):
                    print("Фамилия некорректна. Попробуйте снова.")
                    continue
                family = format_name(family)
                break

            while True:
                name = input("Имя: ").strip()
                if not name:
                    print("Имя не может быть пустым. Попробуйте снова.")
                    continue
                if len(name) > 64:
                    print("Имя не может быть длиннее 64 символов. Попробуйте снова.")
                    continue
                if not is_valid_name(name):
                    print("Имя некорректно. Попробуйте снова.")
                    continue
                name = format_name(name)
                break

            while True:
                patronymic = input("Отчество: ").strip()
                if patronymic:
                    if len(patronymic) > 64:
                        print("Отчество не может быть длиннее 64 символов. Попробуйте снова.")
                        continue
                    if not is_valid_patronymic(patronymic):
                        print("Отчество некорректно. Попробуйте снова.")
                        continue
                    patronymic = format_name(patronymic)
                break

            while True:
                try:
                    day = int(input("День рождения: "))
                    month = int(input("Месяц рождения: "))
                    year = int(input("Год рождения: "))

                    birth_date = datetime(year, month, day)
                    
                    if not is_valid_birth_date(birth_date):
                        print("Дата рождения некорректна. Попробуйте снова.")
                        continue
                        
                    break
                except ValueError:
                    print("Некорректная дата. Пожалуйста, введите числа для дня, месяца и года.")

            try:
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