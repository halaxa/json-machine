# Use phusion/baseimage as base image. To make your builds reproducible, make
# sure you lock down to a specific version, not to `latest`!
# See https://github.com/phusion/baseimage-docker/blob/master/Changelog.md for
# a list of version numbers.
FROM phusion/baseimage:focal-1.0.0

# Use baseimage-docker's init system.
CMD ["/sbin/my_init"]

# ...put your own build instructions here...
RUN apt update && apt-get install -y \
    autoconf \
    automake \
    bison \
    build-essential \
    flex \
    gdb \
    git \
    libpcre3-dev \
    libsqlite3-dev \
    libtool \
    libxml2-dev \
    make \
    php-cli \
    php-dev \
    php-mbstring \
    php-xml \
    pkgconf \
    re2c \
    valgrind \
    wget \
    ;

# Clean up APT when done.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN wget https://github.com/zephir-lang/zephir/releases/download/0.15.2/zephir.phar \
    && mv zephir.phar /bin/zephir \
    && chmod +x /bin/zephir

RUN wget http://pear.php.net/go-pear.phar && php go-pear.phar \
    && pecl install zephir_parser \
    && echo "extension=zephir_parser.so" >> "/etc/php/7.4/cli/php.ini" \
    && echo "extension=/json-machine/ext/jsonmachine/ext/modules/jsonmachine.so" >> "/etc/php/7.4/cli/php.ini"

RUN useradd --create-home --shell /bin/bash user
USER user
WORKDIR /home/user
