FROM php:7.1.24-apache-jessie

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
    apt-get install -y --no-install-recommends ssh libjpeg-dev libpng-dev libpq-dev mysql-client gnupg \
    wget curl nano unzip patch git rsync make ssmtp; \
	docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr; \
	docker-php-ext-install -j "$(nproc)" gd opcache pdo_mysql pdo_pgsql zip

RUN pecl install xdebug; \
    docker-php-ext-enable xdebug;

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

RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/95-xdebug.ini
RUN echo "xdebug.remote_autostart=0" >> /usr/local/etc/php/conf.d/95-xdebug.ini
RUN echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/95-xdebug.ini
RUN echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/95-xdebug.ini
RUN echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/95-xdebug.ini

WORKDIR /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
