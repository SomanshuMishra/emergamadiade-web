from passlib.hash import sha256_crypt
import json

from flask_cors import CORS
from flask import Response
from flask import request
from flask import Flask
import requests

from models import Feeds
from models import Errors
from models import db
from app import app
from sqlalchemy import and_

CORS(app)

@app.route('/', methods=["GET"])
def index():
    return 'Home'

@app.route('/feeds/all/<subreddit>/<rlimit>', methods=['GET'])
def get_all_feeds(subreddit, rlimit=500):
    feed_list = []

    if not subreddit:
        return {'message': 'Invalid parameter passed', 'parameter': 'subreddit'}, 400

    feeds = Feeds.query.filter_by(reddit_subreddit=subreddit).order_by(Feeds.reddit_created_utc.desc()).limit(rlimit).all()

    print(feeds)

    for entry in feeds: 
        feed_list.append(entry.as_dict())

    return {'assets': feed_list}


@app.route('/feeds/all/<subreddit>/<timestamp>/<rlimit>', methods=['GET'])
def get_all_feeds_with_timestamp(subreddit, timestamp, rlimit=500):
    feed_list = []

    if not subreddit:
        return {'message': 'Invalid parameter passed', 'parameter': 'subreddit'}, 400

    if not timestamp:
        return {'message': 'Invalid parameter passed', 'parameter': 'timestamp'}, 400

    feeds = Feeds.query.filter_by(reddit_subreddit=subreddit).filter(Feeds.reddit_created_utc < timestamp).order_by(Feeds.reddit_created_utc.desc()).limit(rlimit).all()

    for entry in feeds:
        feed_list.append(entry.as_dict())

    return {'assets': feed_list}


@app.route('/feeds/filter/<subreddit>', methods=['POST'])
def filter_feeds(subreddit):
    json_request = request.json

    keyword = json_request.get('keyword').strip()

    feed_list = []

    if not subreddit:
        return {'message': 'Invalid parameter passed', 'parameter': 'subreddit'}, 400

    if not keyword:
        return {'message': 'Invalid parameter passed', 'parameter': 'keyword'}, 400

    feeds = Feeds.query.filter_by(reddit_subreddit=subreddit).filter(Feeds.reddit_title.ilike(f'%{keyword}%')).order_by(Feeds.reddit_created_utc.desc()).all()

    for entry in feeds:
        feed_list.append(entry.as_dict())

    return {'assets': feed_list}



@app.route('/feeds/filter/gls', methods=['GET'])
def filter_gls():
    feed_list = []
    feeds = Feeds.query.filter(Feeds.gl_counter >= 1).filter(Feeds.rl_counter == 0).all()

    for entry in feeds:
        feed_list.append(entry.as_dict())

    return {'assets': feed_list}


@app.route('/feeds/filter/subreddit_id', methods=['POST'])
def filter_link_ids():
    json_request = request.json

    if "keyword" not in json_request.keys():
        return {'message': 'Invalid parameter passed', 'parameter': 'keyword'}, 400

    keyword = json_request.get('keyword').strip()

    feed_list = []

    if not keyword:
        return {'message': 'Invalid parameter passed', 'parameter': 'keyword'}, 400

    feeds = Feeds.query.filter(Feeds.reddit_link_id.ilike(f'%{keyword}%')).order_by(Feeds.reddit_created_utc.desc()).all()

    for entry in feeds:
        feed_list.append(entry.as_dict())

    return {'assets': feed_list}


@app.route('/feeds/<subreddit>', methods=['PUT'])
def add_feeds(subreddit):
    json_request = request.json

    reddit_link_id = json_request.get('reddit_link_id')
    reddit_link = json_request.get('reddit_link')
    reddit_title = json_request.get('reddit_title')
    reddit_created_utc = json_request.get('reddit_created_utc')
    thumbnail_link = json_request.get('thumbnail_link')
    imgur_iframe = json_request.get('imgur_iframe')
    w2c_link = json_request.get('w2c_link')
    gl_counter = json_request.get('gl_counter')

    #red_light counter
    rl_counter = json_request.get('rl_counter')
    

    exists = Feeds.query.filter_by(reddit_link_id=reddit_link_id).first()

    if exists:
        try:
            if exists.reddit_title != reddit_title:
                exists.reddit_title = reddit_title

            if exists.thumbnail_link != thumbnail_link:
                exists.thumbnail_link = thumbnail_link

            if exists.imgur_iframe != imgur_iframe:
                exists.imgur_iframe = imgur_iframe

            if exists.w2c_link != w2c_link:
                exists.w2c_link = w2c_link

            if exists.gl_counter != gl_counter:
                exists.gl_counter != gl_counter
            
            # Red_light counter
            
            if exists.rl_counter != rl_counter:
                exists.rl_counter != rl_counter


            db.session.commit()
            return {'message': 'Feed already exists. Tried to update info.'}, 409
        except Exception as err:
            db.session.rollback()
            return {'message': 'Internal server error upon inserting on errors table', 'error': err}, 500
        #Remove deleted posts

    try:
        feed = Feeds(
            reddit_link_id=reddit_link_id,
            reddit_link=reddit_link,
            reddit_title=reddit_title,
            reddit_created_utc=reddit_created_utc,
            reddit_subreddit=subreddit,
            imgur_iframe=imgur_iframe,
            thumbnail_link=thumbnail_link,
            w2c_link=w2c_link,
            gl_counter=gl_counter,

            # Red_light counter
            rl_counter=rl_counter
        )

        db.session.add(feed)

        db.session.commit()

        return {'message': 'Feed data inserted successfully'}

    except (TypeError) as terr:
        error = Errors(
            reddit_link_id=reddit_link_id,
            reason='Invalid JSON Structure',
            status_code=500
        )

        db.session.add(error)

        db.session.commit()

        return {'message': 'Internal server error', 'error': terr, 'json': {'reddit_link_id': reddit_link_id, 'reddit_link': reddit_link,
                'reddit_title': reddit_title, 'reddit_created_utc': reddit_created_utc, 'reddit_subreddit': subreddit,
                'imgur_iframe': imgur_iframe, 'thumbnail_link': thumbnail_link, 'w2c_link': w2c_link}}, 500

    except Exception as err:
        db.session.rollback()
        return {'message': 'Internal server error', 'error': err}, 500

# Green light and Red light counter.
@app.route('/feeds/<subreddit>', methods=['PUT'])
def func():
    pass    
