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
    libsqlite3-dev

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN useradd --create-home --shell /bin/bash user
USER user
WORKDIR /home/user

# https://www.zend.com/setting-up-your-php-build-environment
RUN git clone https://github.com/php/php-src.git

RUN cd php-src \
    && git checkout php-8.0.9 \
    && ./buildconf --force \
    && ./configure --enable-debug \
        --prefix=$HOME/php-bin/DEBUG \
        --with-config-file-path=$HOME/php-bin/DEBUG/etc \
    && make -j4 \
    && make install

ENV PATH="/home/user/php-bin/DEBUG/bin:${PATH}"

RUN mkdir php-bin/DEBUG/etc
COPY php.ini php-bin/DEBUG/etc/php.ini
