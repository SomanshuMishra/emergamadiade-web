from flask import Flask

import settings

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = settings.DB_CONN
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = 'False'

import mainn

# @app.route('/', methods=["GET"])
# def index():
#     return 'Home'


if __name__ == "__main__":
    app.run(debug=True)