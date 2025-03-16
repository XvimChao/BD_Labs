from sqlalchemy import Column, Integer, String, Date, ForeignKey
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship, sessionmaker
from sqlalchemy import create_engine

# Подключение к базе данных
DATABASE_URL = "postgresql://bd_hsl9_user:3cU6xyUqpiR6UrSnaRelGS3erErjzIHO@dpg-cute50vnoe9s73990f8g-a.oregon-postgres.render.com/bd_hsl9"
engine = create_engine(DATABASE_URL)

# Создание сессии
Session = sessionmaker(bind=engine)

Base = declarative_base()

class User(Base):
    __tablename__ = 'users'

    id = Column(Integer, primary_key=True)
    login = Column(String, unique=True, nullable=False)
    password = Column(String, nullable=False)
    registration_date = Column(Date, nullable=False)

    # Связь 1 к 1 с таблицей accounts
    account = relationship("Account", back_populates="user", uselist=False)

class Account(Base):
    __tablename__ = 'accounts'

    user_id = Column(Integer, ForeignKey('users.id'), primary_key=True)
    family = Column(String, nullable=False)
    name = Column(String, nullable=False)
    patronymic = Column(String)
    birth_date = Column(Date, nullable=False)

    # Связь 1 к 1 с таблицей users
    user = relationship("User", back_populates="account")