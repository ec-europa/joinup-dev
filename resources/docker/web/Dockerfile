FROM php:7.3.29-apache-stretch

ARG composer_version="1.7.2"
ARG drush_version="8.1.17"
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data

ENV DOCUMENT_ROOT /var/www/html/web
RUN sed -ri -e 's!/var/www/html!${DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Based on https://github.com/docker-library/drupal and fpfis/httpd-php.
# install the PHP extensions we need
RUN set -ex; \
	apt-get update; \
    apt-get install -y --no-install-recommends iproute2 ssh libzip-dev libjpeg-dev libpng-dev libpq-dev mysql-client \
    net-tools gnupg wget curl nano unzip patch git rsync make ssmtp telnet lsof iputils-ping; \
	docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr; \
	docker-php-ext-install -j "$(nproc)" gd opcache pdo_mysql pdo_pgsql zip

RUN pecl install xdebug-3.0.4;
 # enabling in the docker-compose.yml file
 # && docker-php-ext-enable xdebug

RUN wget https://github.com/composer/composer/releases/download/${composer_version}/composer.phar -O /usr/bin/composer
RUN wget https://github.com/drush-ops/drush/releases/download/${drush_version}/drush.phar -O /usr/bin/drush
RUN ln -s /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/bin/composer /usr/bin/drush

ENV PATH=${PATH}:/root/.composer/vendor/bin
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get clean; \
    rm -rf /var/lib/apt/lists/*; \
    rm -rf /tmp/*; \
    rm -Rf /root/.composer/cache;

ADD php_settings.ini /usr/local/etc/php/conf.d/95-php_settings.ini
ADD ssmtp.conf /etc/ssmtp/ssmtp.conf

WORKDIR /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
