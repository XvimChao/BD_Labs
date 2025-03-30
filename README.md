# BD_Labs

# 6 semestr
# 1 lab:
    python -m venv venv  
    venv\Scripts\activate
    pip install sqlalchemy alembic psycopg2-binary
    alembic upgrade head
    python main.py