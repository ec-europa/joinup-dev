FROM fpfis/httpd-php
MAINTAINER Joinup

ARG DAEMON_UID
ARG DAEMON_GID

ENV DAEMON_UID "$DAEMON_UID"
ENV DAEMON_GID "$DAEMON_GID"

RUN apk --no-cache add shadow && \
    usermod -u ${DAEMON_UID} ${DAEMON_USER} && \
    groupmod -g ${DAEMON_GID} ${DAEMON_GROUP}

ADD php_settings.ini /usr/local/etc/php/conf.d/95-php_settings.ini
ADD ssmtp.conf /etc/ssmtp/ssmtp.conf
