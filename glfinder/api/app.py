from flask import Flask

import settings

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = settings.DB_CONN
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = 'False'