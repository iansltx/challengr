FROM alpine:3.8

# Inspired by:
# https://github.com/jimbojsb/laravel-docker/blob/master/Dockerfile
# https://github.com/SlvrEagle23/docker-dev-server/blob/master/Dockerfile.prod
# https://github.com/iansltx/raphple/blob/master/Dockerfile

# install packages
RUN apk add --no-cache curl ca-certificates openssl git nginx runit \
        php php-common php-curl php-ctype php-sockets php-session php7-intl \
        php-dom php-xml php-phar php-mbstring php-pcntl php-json php-fileinfo \
        php-opcache php-pdo php-pdo_mysql php-fpm php-tokenizer php-openssl \
        php-simplexml php-xmlwriter php-gd nodejs npm libpng-dev gifsicle \
        autoconf automake build-base libtool nasm

# Install Composer
RUN curl https://getcomposer.org/composer.phar > /usr/sbin/composer && chmod +x /usr/sbin/composer
RUN npm i npm@latest -g

# Copy configs
COPY container/php.ini /etc/php7/php.ini
COPY container/nginx.conf /etc/nginx/nginx.conf
COPY container/fpm.conf /etc/php7/php-fpm.d/www.conf

# set up runit
COPY container/runsvinit /sbin/runsvinit
RUN mkdir /tmp/nginx && mkdir -p /etc/service/nginx && echo '#!/bin/sh' >> /etc/service/nginx/run && \
echo 'nginx' >> /etc/service/nginx/run && chmod +x /etc/service/nginx/run && \
mkdir -p /etc/service/fpm && echo '#!/bin/sh' >> /etc/service/fpm/run && \
echo 'php-fpm7 -FR' >> /etc/service/fpm/run && chmod +x /etc/service/fpm/run && \
chmod +x /sbin/runsvinit
ENTRYPOINT ["/sbin/runsvinit"]
EXPOSE 80

# set up app; order of operations optimized for maximum layer reuse
RUN mkdir /var/app
WORKDIR /var/app

COPY package* /var/app/
RUN npm install
COPY composer* /var/app/
RUN composer install --no-scripts --no-plugins --no-autoloader && composer clear-cache
COPY . /var/app
RUN composer -o dump-autoload && chgrp -R nginx storage && chmod -R g+w storage
RUN npm run production
