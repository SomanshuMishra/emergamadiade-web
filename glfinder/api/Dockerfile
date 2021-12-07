FROM python:3.8

COPY requirements.txt /app/
WORKDIR /app
RUN pip3 install -r requirements.txt

COPY . /app

EXPOSE 8080

CMD flask run --host=0.0.0.0 --port=8080
