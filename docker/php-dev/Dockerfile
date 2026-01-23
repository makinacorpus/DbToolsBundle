# Dockerfile for PHP 8.1 and greater.
ARG COMPOSER_VERSION=latest
ARG PHP_VERSION=8.1

FROM composer:${COMPOSER_VERSION} AS composer-builder

FROM php:${PHP_VERSION}-fpm-trixie

# Basic requirements
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    <<'EOT'
    rm -f /etc/apt/apt.conf.d/docker-clean || true
    echo 'Binary::apt::APT::Keep-Downloaded-Packages "true";' >/etc/apt/apt.conf.d/docker-keep-cache
    echo 'APT::Install-Recommends "false";' >/etc/apt/apt.conf.d/docker-no-install-recommends
    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get install -y --no-install-recommends \
        -o Dpkg::Options::='--force-confdef' \
        -o Dpkg::Options::='--force-confold' \
        acl \
        default-mysql-client \
        gnupg2 \
        iproute2 \
        libghc-curl-dev \
        libldap2-dev \
        libldb-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        sqlite3 \
        zip \
        zlib1g-dev
    apt-get clean
    apt autoclean -y
    apt autoremove -y
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
EOT

# For now, we keep the v16 version, because 18 will add a "transaction_timeout"
# in all its dump, which will make very unhappy v10 and v16. Since we test all
# versions using the same client library, we have no other choice than keep
# the lowest common denominator, for now, until we fix this in a reasonable
# way.
# Postgresql-client-16
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    <<'EOT'
    export DEBIAN_FRONTEND=noninteractive
    curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
    sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt trixie-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
    apt-get update
    apt-get install -y --no-install-recommends \
        -o Dpkg::Options::='--force-confdef' \
        -o Dpkg::Options::='--force-confold' \
        postgresql-16
    apt-get clean
    apt autoclean -y
    apt autoremove -y
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
EOT

# PHP extensions and Xdebug
RUN <<'EOT'
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
    docker-php-ext-install -j$(nproc) pgsql pdo_pgsql pdo mysqli pdo_mysql zip xml gd curl bcmath
    docker-php-ext-enable pdo_pgsql pdo_mysql sodium
    pecl install xdebug && docker-php-ext-enable xdebug
EOT

# SQL Server support
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    <<'EOT'
    export DEBIAN_FRONTEND=noninteractive
    export ACCEPT_EULA=Y
    curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
    curl https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list
    apt-get update
    apt-get install -y --no-install-recommends \
        -o Dpkg::Options::='--force-confdef' \
        -o Dpkg::Options::='--force-confold' \
        msodbcsql18 \
        unixodbc-dev
    pecl install sqlsrv
    pecl install pdo_sqlsrv
    docker-php-ext-enable sqlsrv pdo_sqlsrv
    apt-get clean
    apt autoclean -y
    apt autoremove -y
EOT

COPY --from=composer-builder /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
