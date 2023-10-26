FROM php:8.1-fpm-bookworm

RUN apt-get update && \
    apt-get install -y --no-install-recommends default-mysql-client acl iproute2 zip zlib1g-dev libzip-dev \
    libxml2-dev libpng-dev libghc-curl-dev libldb-dev libldap2-dev gnupg2 libpq-dev \
     -yqq && \
    pecl install apcu && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install -j$(nproc) pgsql pdo_pgsql pdo mysqli pdo_mysql zip xml gd curl bcmath && \
    docker-php-ext-enable apcu pdo_pgsql pdo_mysql sodium && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* &&\
    pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug

# Instaling postgresql-client-16
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc| gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
RUN sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
RUN apt-get update && apt-get install -y postgresql-client-16

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint

# ENTRYPOINT [ "init.sh" ]

WORKDIR /var/www

EXPOSE 9000
