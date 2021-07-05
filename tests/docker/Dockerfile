FROM php:7.1-alpine

RUN apk --no-cache add \
    autoconf \
    build-base \
    curl \
    git \
    subversion \
    openssh \
    openssl \
    mercurial \
    tini \
    bash \
    zlib-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libbz2 \
    libstdc++ \
    libxslt-dev \
    openldap-dev \
    make \
    unzip \
    wget && \
    docker-php-ext-install bcmath mcrypt zip mbstring pcntl xsl && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install gd && \
    apk del build-base && \
    rm -rf /var/cache/apk/*

RUN set -eux; \
  apk add --no-cache --virtual .build-deps \
    libzip-dev \
    zlib-dev \
  ; \
  docker-php-ext-install -j "$(nproc)" \
    zip \
  ; \
  runDeps="$( \
    scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
      | tr ',' '\n' \
      | sort -u \
      | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
  apk add --no-cache --virtual .composer-phpext-rundeps $runDeps; \
  apk del .build-deps

RUN printf "# composer php cli ini settings\n\
date.timezone=UTC\n\
memory_limit=-1\n\
" > $PHP_INI_DIR/php-cli.ini

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

COPY docker-entrypoint.sh /docker-entrypoint.sh

RUN chmod +x /docker-entrypoint.sh

WORKDIR /app

ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["composer"]
