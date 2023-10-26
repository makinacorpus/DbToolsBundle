#!/bin/bash

# Use this to run functional tests with real database.
# You need docker on your box for it work.

echo "Running docker compose up and waiting for 10 seconds."
echo "In order to shut it down after tests, manually run: "
echo "    docker compose -p db_tools_bundle_test down"
docker compose -p db_tools_bundle_test up -d --force-recreate --remove-orphans
sleep 10

echo "Downloading composer dependencies"
docker compose exec php_fpm composer install

echo "Running tests with MySQL 5.7"
docker compose exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mysql57 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER="root" \
    -e DBAL_USER=root \
    php_fpm vendor/bin/phpunit

echo "Running tests with MySQL 8"
docker compose exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mysql80 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=root \
    -e DBAL_USER=root \
    php_fpm vendor/bin/phpunit

echo "Running tests with MariaDB 11"
docker compose exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mariadb11 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD="password" \
    -e DBAL_ROOT_USER="root" \
    -e DBAL_USER=root \
    php_fpm vendor/bin/phpunit

echo "Running tests with PostgreSQL 10"
docker compose exec \
    -e DBAL_DRIVER=pdo_pgsql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=postgresql10 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=5432 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=postgres \
    -e DBAL_USER=postgres \
    php_fpm vendor/bin/phpunit

echo "Running tests with PostgreSQL 16"
docker compose exec \
    -e DBAL_DRIVER=pdo_pgsql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=postgresql16 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=5432 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=postgres \
    -e DBAL_USER=postgres \
    php_fpm vendor/bin/phpunit
