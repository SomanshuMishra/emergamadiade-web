import os
from dotenv import load_dotenv
from urllib.parse import quote_plus as urlquote

load_dotenv()

# DB_HOST = os.environ.get('POSTGRES_HOSTNAME')
# DB_PORT = os.environ.get('POSTGRES_PORT')
# DB_USER = os.environ.get('POSTGRES_USERNAME')
# DB_PASS = os.environ.get('POSTGRES_PASSWORD')
DB_HOST = 'localhost'
DB_PORT =  '5432'
DB_USER = 'postgres'
DB_PASS = '123456'
print("Pass is")
print(DB_PASS)
# DB_NAME = os.environ.get('POSTGRES_DB')
DB_NAME = 'emerger'
DB_CONN = f'postgresql://{DB_USER}:%s@{DB_HOST}:{DB_PORT}/{DB_NAME}' % urlquote(str(DB_PASS))
print("Connnn is")
print(DB_CONN)