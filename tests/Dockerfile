#FROM php:8.2-cli   ## this throws a lot of Deprecation errors
FROM php:7.4-cli   

LABEL maintainer="michiel@phplist.com"

RUN apt-get update && apt-get install -y \
        libzip-dev \
        unzip jq curl \
        git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

# Install Composer 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /behat

COPY composer.json ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install

COPY run-tests.sh /behat
RUN chmod 755 /behat/run-tests.sh

CMD ["/behat/run-tests.sh"]
