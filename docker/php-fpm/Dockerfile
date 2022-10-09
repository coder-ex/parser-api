FROM php:8.1-fpm

# Install tools required for build stage
RUN apt-get update && apt-get install -fyqq \
    mc bash curl wget rsync ca-certificates openssl ssh git tzdata openntpd \
    fontconfig libc6 unzip libxrender1 libfreetype6-dev libjpeg62-turbo-dev \
    libpng-dev gnupg binutils-gold autoconf \
    sendmail \
    nmap net-tools tcpdump sudo nano \
    g++ gcc gnupg libgcc1 linux-headers-amd64 make python

# Install nodejs v14 + npm (Using Debian, as root)
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mkdir -p /usr/local/bin
RUN mv composer.phar /usr/local/bin/composer

# Install the PHP bcmath extension
RUN apt-get install -y \
    && docker-php-ext-install bcmath

# Install shmop
RUN apt-get install -y \
    && docker-php-ext-configure shmop --enable-shmop \
    && docker-php-ext-install -j$(nproc) shmop \
    && docker-php-ext-enable shmop

# Install opcache
RUN apt-get install -y \
    && docker-php-ext-install -j$(nproc) opcache \
    && docker-php-ext-enable opcache

# Install iconv
RUN apt-get install -y \
    && docker-php-ext-install -j$(nproc) iconv

# Install libraries for compiling GD, then build it
RUN apt-get install -y libfreetype6 libfreetype6-dev libpng16-16 libpng-dev libjpeg-dev libjpeg62-turbo libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get remove -y libfreetype6-dev libpng-dev libjpeg62-turbo-dev

# Install additional PHP libraries
RUN apt-get install -y libonig-dev libpq-dev \
    && docker-php-ext-install -j$(nproc) mbstring pdo pdo_pgsql pdo_mysql mysqli bcmath

# Install libraries for Imagick support
# - https://stackoverflow.com/questions/47258610/docker-php-7-1-imagick
# - https://ourcodeworld.com/articles/read/645/how-to-install-imagick-for-php-7-in-ubuntu-16-04
RUN apt-get clean && apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick && rm -rf /var/lib/apt/lists/*

# Add ZIP archives support
RUN apt-get update && apt-get install -y libzip-dev zip \
    && docker-php-ext-install -j$(nproc) zip \
    && apt-get remove -fyqq libzip-dev

# Install xdebug
RUN pecl install xdebug \
     && docker-php-ext-enable xdebug

# Install octane swoole
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Create usermod && groupmod
RUN usermod -u 1000 www-data \
    && groupmod -g 1000 www-data \
    && usermod -aG sudo,root www-data

RUN echo "root:r12345" | chpasswd \
    && echo "www-data:u12345" | chpasswd

# Copy existing application directory permissions
RUN chown www-data:www-data /var/www
COPY --chown=www-data:www-data . /var/www/html

COPY ./php.ini /usr/local/etc/php/php.ini
COPY ./docker-php-ext-opcache.ini /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
COPY ./docker-php-ext-swoole.ini /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini
COPY ./docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN mkdir /source
WORKDIR /var/www/html
COPY ./ /var/www/html

# Change current user to www-data
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
