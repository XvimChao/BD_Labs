# BD_Labs
# Start server:
    .\pg_ctl -D ../new/ -l logfile start
# 6 semestr
# 1 lab:
    python -m venv venv  
    venv\Scripts\activate
    pip install sqlalchemy alembic psycopg2-binary psycopg2 passlib
    alembic upgrade head
    python main.py
# 2 lab:
    Тема: ranobelib(сайт для чтения ранобэ)