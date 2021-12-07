FROM mattrayner/lamp:latest-1804

RUN apt-get update
RUN apt-get install cron -y
COPY feeder /etc/cron.d/feeder
COPY post_gather.php /app
RUN chmod 0644 /etc/cron.d/feeder
RUN crontab /etc/cron.d/feeder
RUN touch /var/log/cron.log
CMD ["cron", "-f"]