#!/bin/bash

# Use this to run functional tests with real database.
# You need docker on your box for it work.

RED='\033[0;31m'
NC='\033[0m' # No Color

REBUILD=${REBUILD-"0"}
SLEEP=${SLEEP-"1"}

title() {
    printf "${RED}\n-------------------------------- ${NC}"
    printf "${RED}$1${NC}"
    printf "${RED} --------------------------------\n\n${NC}"
}

title "Welcome to DbToolsTest script"

printf "In order to shut it down after tests, manually run:\n"
printf "    ${RED}docker compose -p db_tools_bundle_test down${NC}\n"
printf "In order to rebuild your containers, manually run:\n"
printf "    ${RED}docker compose -p db_tools_bundle_test build${NC}\n"

if [ "${REBUILD}" -eq "1" ]; then
    title "Rebuilding containers"
    docker compose -p db_tools_bundle_test build;
fi

title "Recreating containers"
docker compose -p db_tools_bundle_test up -d --force-recreate --remove-orphans

if [ "${SLEEP}" -eq "1" ]; then
    title "Waiting for docker 10 seconds"
    sleep 10;
fi

title "Composer install dependencies"
docker compose -p db_tools_bundle_test exec phpunit composer install

title "Running tests with MySQL 5.7"
docker compose -p db_tools_bundle_test exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mysql57 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER="root" \
    -e DBAL_USER=root \
    phpunit vendor/bin/phpunit $@

title "Running tests with MySQL 8"
docker compose -p db_tools_bundle_test exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mysql80 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=root \
    -e DBAL_USER=root \
    phpunit vendor/bin/phpunit $@

title "Running tests with MariaDB 11"
docker compose -p db_tools_bundle_test exec \
    -e DBAL_DRIVER=pdo_mysql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=mariadb11 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=3306 \
    -e DBAL_ROOT_PASSWORD="password" \
    -e DBAL_ROOT_USER="root" \
    -e DBAL_USER=root \
    phpunit vendor/bin/phpunit $@

title "Running tests with PostgreSQL 10"
docker compose -p db_tools_bundle_test exec \
    -e DBAL_DRIVER=pdo_pgsql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=postgresql10 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=5432 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=postgres \
    -e DBAL_USER=postgres \
    phpunit vendor/bin/phpunit $@

title "Running tests with PostgreSQL 16"
docker compose -p db_tools_bundle_test exec \
    -e DBAL_DRIVER=pdo_pgsql \
    -e DBAL_DBNAME=test_db \
    -e DBAL_HOST=postgresql16 \
    -e DBAL_PASSWORD=password \
    -e DBAL_PORT=5432 \
    -e DBAL_ROOT_PASSWORD=password \
    -e DBAL_ROOT_USER=postgres \
    -e DBAL_USER=postgres \
    phpunit vendor/bin/phpunit $@
