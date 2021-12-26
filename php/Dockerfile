FROM php:7-fpm

RUN apt-get update
RUN apt-get install libicu-dev locales -y
RUN docker-php-ext-install intl
RUN echo -e "en_US ISO-8859-1\nen_US.ISO-8859-15 ISO-8859-15\nen_US.UTF-8 UTF-8\npt_PT ISO-8859-1\npt_PT.UTF-8 UTF-8" > /etc/locale.gen
RUN locale-gen
