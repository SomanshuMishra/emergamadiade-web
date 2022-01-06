from flask_sqlalchemy import SQLAlchemy
from sqlalchemy.dialects import mysql
from flask_migrate import Migrate

from app import app

db = SQLAlchemy(app)
migrate = Migrate(app, db)
db.create_all()


def serialize(obj, not_allowed_fields=False):
    data = {}

    for c in obj.__table__.columns:
        if not not_allowed_fields:
            data[c.name] = getattr(obj, c.name)
            continue

        if c.name not in not_allowed_fields:
            data[c.name] = getattr(obj, c.name)

    return data


class Feeds(db.Model):
    __tablename__ = 'feeds'

    id = db.Column(db.Integer, primary_key=True)
    reddit_link_id = db.Column(db.String(12), unique=True, nullable=False)
    reddit_link = db.Column(db.String(128), unique=False, nullable=False)
    reddit_title = db.Column(db.String(256), unique=False, nullable=False)
    reddit_created_utc = db.Column(db.Integer, unique=False, nullable=False)
    reddit_subreddit = db.Column(db.String(24), unique=False, nullable=False)
    imgur_iframe = db.Column(db.String(512), unique=False, nullable=True)
    thumbnail_link = db.Column(db.String(512), unique=False, nullable=True)
    w2c_link = db.Column(db.String(512), unique=False, nullable=True)
    gl_counter = db.Column(db.Integer, default=1, unique=False, nullable=True)
    #Adding Redlight column to the DB
    rl_counter = db.Column(db.Integer, default=0, unique=False, nullable=True)




    def as_dict(self):
        return serialize(self, ['password'])

    def __repr__(self):
        return '<User %r>' % self.id

class Errors(db.Model):
    __tablename__ = 'errors'

    id = db.Column(db.Integer, primary_key=True)
    reddit_link_id = db.Column(db.String(12), unique=False, nullable=False)
    reason = db.Column(db.String(24), unique=False, nullable=False)
    status_code = db.Column(db.Integer, unique=False, nullable=False)
