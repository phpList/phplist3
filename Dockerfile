
FROM debian:buster-slim

LABEL maintainer="michiel@phplist.com" 

RUN apt-get update && apt-get upgrade -y

RUN apt-get install -y apt-utils \
    vim apache2 net-tools php-mysql \
    libapache2-mod-php php-curl php-gd \
    git cron php-imap php-xml php-zip php-mbstring

RUN useradd -d /var/www/phpList3 phplist

ARG VERSION=unknown
RUN echo VERSION=${VERSION}

RUN rm -rf /var/www/phpList3 && mkdir /var/www/phpList3
RUN rm -rf /etc/phplist && mkdir /etc/phplist

COPY docker/docker-apache-phplist.conf /etc/apache2/sites-available
COPY docker/docker-entrypoint.sh /usr/local/bin/
COPY docker/phplist-crontab /etc/cron.d/
COPY docker/docker-phplist-config-live.php /etc/phplist/

#COPY phplist-$VERSION /var/www/phpList3
COPY . /var/www/phpList3

RUN rm -f /etc/apache2/sites-enabled/000-default.conf && \
    cd /var/www/ && find . -type d -name .git -print0 | xargs -0 rm -rf && \
    find . -type d -print0 | xargs -0 chmod 755 && \
    find . -type f -print0 | xargs -0 chmod 644

RUN chown -R www-data: /var/www/phpList3

EXPOSE 80 

VOLUME ["/var/www", "/var/log/apache2"]
ENTRYPOINT ["docker-entrypoint.sh"]
