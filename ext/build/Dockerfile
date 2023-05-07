# Use phusion/baseimage as base image. To make your builds reproducible, make
# sure you lock down to a specific version, not to `latest`!
# See https://github.com/phusion/baseimage-docker/blob/master/Changelog.md for
# a list of version numbers.
FROM phusion/baseimage:focal-1.0.0

# Use baseimage-docker's init system.
CMD ["/sbin/my_init"]

# ...put your own build instructions here...
RUN apt update && apt-get install -y \
    build-essential \
    autoconf \
    automake \
    bison \
    flex \
    re2c \
    gdb \
    libtool \
    make \
    pkgconf \
    valgrind \
    git \
    libxml2-dev \
    libsqlite3-dev \
    wget \
    libssl-dev

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN wget https://getcomposer.org/download/2.2.18/composer.phar -O /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

WORKDIR /usr/src
RUN git clone https://github.com/php/php-src.git
ARG debug
RUN cd php-src \
    && git checkout php-8.0.9 \
    && ./buildconf --force \
    && ./configure $debug --enable-mbstring --disable-mbregex --with-openssl \
    && make -j`nproc` \
    && make install
RUN rm -rf php-src
COPY php.ini /usr/local/lib/php.ini
