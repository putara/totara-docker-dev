FROM php:7.3-fpm-buster

ARG TIME_ZONE=Pacific/Auckland

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
        apt-transport-https \
        libgd3 \
        libgd-dev \
        libfreetype6-dev \
        libjpeg-dev \
        libmcrypt-dev \
        libpng-dev \
        libxml2-dev \
        libicu-dev \
        libpq-dev \
        gnupg2 \
        nano \
        wget \
        openssl \
        locales \
        tzdata \
        git \
        libzip-dev \
        libmemcached-dev \
        zip \
        netcat \
        bc \
        ghostscript \
        graphviz \
        aspell \
        libldap2-dev \
    && docker-php-ext-configure ldap --with-libdir=lib/$(uname -m)-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) xmlrpc \
        zip \
        intl \
        soap \
        opcache \
        pdo_pgsql \
        pdo_mysql \
        pgsql \
        mysqli \
        exif \
        ldap \
    && docker-php-ext-configure gd \
            --with-freetype-dir=/usr/include/ \
            --with-png-dir=/usr/include/ \
            --with-jpeg-dir=/usr/include/ \
            --with-gd \
    && docker-php-ext-install -j$(nproc) gd \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install -o -f igbinary \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable igbinary

RUN pecl install -o -f memcached \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable memcached

# we need en_US locales for MSSQL connection to work
# we need en_AU locales for behat to work
RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i -e 's/# en_AU.UTF-8 UTF-8/en_AU.UTF-8 UTF-8/' /etc/locale.gen && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_US.UTF-8

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US.UTF-8
ENV LC_ALL en_US.UTF-8

RUN ln -fs /usr/share/zoneinfo/${TIME_ZONE} /etc/localtime \
    && dpkg-reconfigure --frontend noninteractive tzdata

COPY php.ini /usr/local/etc/php/
COPY fpm.conf /usr/local/etc/php-fpm.d/zz-totara.conf

RUN echo 'source ~/.bash_aliases' >> ~/.bashrc && \
    groupadd -g 1000 totara && \
    useradd -g 1000 -u 1000 totara
