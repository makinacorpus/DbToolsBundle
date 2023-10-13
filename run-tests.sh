#!/bin/bash

# Use this to run functional tests with real database.
# You need docker on your box for it work.

echo "Running docker compose up and waiting for 10 seconds."
echo "In order to shut it donw after tests, manually run: "
echo "    docker compose -p db_tools_bundle_test down"
docker compose -p db_tools_bundle_test up -d --force-recreate --remove-orphans
sleep 10

echo "Running tests with MySQL 5.7"
DBAL_DRIVER=pdo_mysql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9001 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="root" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with MySQL 8"
DBAL_DRIVER=pdo_mysql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9002 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="root" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with MariaDB 11"
DBAL_DRIVER=pdo_mysql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9003 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="root" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with PostgreSQL 10"
DBAL_DRIVER=pdo_pgsql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9004 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="postgres" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with PostgreSQL 16"
DBAL_DRIVER=pdo_pgsql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9005 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="postgres" \
    DBAL_USER=root \
    vendor/bin/phpunit
