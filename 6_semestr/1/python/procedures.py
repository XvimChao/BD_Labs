from sqlalchemy.orm import sessionmaker
from models import User, Account, Session
from datetime import datetime

def register_user(login, password, registration_date, family, name, patronymic, birth_date):
    session = Session()
    try:
        # Начало транзакции
        session.begin()

        # Создание пользователя
        user = User(login=login, password=password, registration_date=registration_date)
        session.add(user)
        session.flush()  # Получаем ID пользователя

        # Создание аккаунта
        account = Account(user_id=user.id, family=family, name=name, patronymic=patronymic, birth_date=birth_date)
        session.add(account)

        # Завершение транзакции
        session.commit()
    except Exception as e:
        # Откат транзакции в случае ошибки
        session.rollback()
        raise e
    finally:
        # Закрытие сессии
        session.close()