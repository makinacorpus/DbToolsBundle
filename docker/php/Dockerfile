FROM php:8.1-fpm-bookworm

# Basic requirements
RUN apt-get update
RUN apt-get install -yqq --no-install-recommends default-mysql-client acl iproute2 zip zlib1g-dev libzip-dev \
    libxml2-dev libpng-dev libghc-curl-dev libldb-dev libldap2-dev gnupg2 libpq-dev

# Instaling postgresql-client-16
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc| gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg && \
    sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list' && \
    apt-get update && apt-get install -y postgresql-16

# PHP required extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install -j$(nproc) pgsql pdo_pgsql pdo mysqli pdo_mysql zip xml gd curl bcmath
RUN docker-php-ext-enable pdo_pgsql pdo_mysql sodium

# SQL Server support
ENV ACCEPT_EULA=Y
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - 
RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list 
RUN apt-get update 
RUN apt-get -y --no-install-recommends install msodbcsql18 unixodbc-dev 
RUN pecl install sqlsrv
RUN pecl install pdo_sqlsrv
RUN docker-php-ext-enable sqlsrv pdo_sqlsrv

# Cleanup.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
