#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

section_title() {
    printf "${RED}\n-------------------------------- ${NC}"
    printf "${RED}$1${NC}"
    printf "${RED} --------------------------------\n${NC}"
}

# Build docker containers
do_build() {
    section_title "Rebuilding containers"
    docker compose -p db_tools_bundle_test build;
}

# Start docker containers
do_up() {
    section_title "Up containers"
    docker compose -p db_tools_bundle_test up -d --force-recreate --remove-orphans
}

# Stop docker containers
do_down() {
    section_title "Down containers"
    docker compose -p db_tools_bundle_test down
}

# Launch composer checks (for Static analysis & Code style fixer)
do_checks() {
    section_title "Composer checks"
    echo 'composer install'
    docker compose -p db_tools_bundle_test exec phpunit composer install
    echo 'composer checks'
    docker compose -p db_tools_bundle_test exec phpunit composer checks
}

# Run PHPunit tests for all database vendors
do_test() {
    section_title "Composer install dependencies"
    docker compose -p db_tools_bundle_test exec phpunit composer install

    section_title "Running tests with MySQL 5.7"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_mysql \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=mysql57 \
        -e DBAL_PASSWORD=password \
        -e DBAL_PORT=3306 \
        -e DBAL_ROOT_PASSWORD=password \
        -e DBAL_ROOT_USER="root" \
        -e DBAL_USER=root \
        -e DATABASE_URL=mysql://root:password@mysql57:3306/test_db?serverVersion=5.7 \
        phpunit vendor/bin/phpunit $@

    section_title "Running tests with MySQL 8"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_mysql \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=mysql80 \
        -e DBAL_PASSWORD=password \
        -e DBAL_PORT=3306 \
        -e DBAL_ROOT_PASSWORD=password \
        -e DBAL_ROOT_USER=root \
        -e DBAL_USER=root \
        -e DATABASE_URL=mysql://root:password@mysql80:3306/test_db?serverVersion=8 \
        phpunit vendor/bin/phpunit $@

    section_title "Running tests with MariaDB 11"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_mysql \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=mariadb11 \
        -e DBAL_PASSWORD=password \
        -e DBAL_PORT=3306 \
        -e DBAL_ROOT_PASSWORD="password" \
        -e DBAL_ROOT_USER="root" \
        -e DBAL_USER=root \
        -e DATABASE_URL=mysql://root:password@mariadb11:3306/test_db?serverVersion=11.1.3-MariaDB \
        phpunit vendor/bin/phpunit $@

    section_title "Running tests with PostgreSQL 10"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_pgsql \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=postgresql10 \
        -e DBAL_PASSWORD=password \
        -e DBAL_PORT=5432 \
        -e DBAL_ROOT_PASSWORD=password \
        -e DBAL_ROOT_USER=postgres \
        -e DBAL_USER=postgres \
        -e DATABASE_URL="postgresql://postgres:password@postgresql10:5432/test_db?serverVersion=10&charset=utf8" \
        phpunit vendor/bin/phpunit $@

    section_title "Running tests with PostgreSQL 16"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_pgsql \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=postgresql16 \
        -e DBAL_PASSWORD=password \
        -e DBAL_PORT=5432 \
        -e DBAL_ROOT_PASSWORD=password \
        -e DBAL_ROOT_USER=postgres \
        -e DBAL_USER=postgres \
        -e DATABASE_URL="postgresql://postgres:password@postgresql16:5432/test_db?serverVersion=16&charset=utf8" \
        phpunit vendor/bin/phpunit $@

    section_title "Running tests with SQL Server 2019"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_sqlsrv \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=sqlsrv2019 \
        -e DBAL_PASSWORD=P@ssword123 \
        -e DBAL_PORT=1433 \
        -e DBAL_ROOT_PASSWORD=P@ssword123 \
        -e DBAL_ROOT_USER=sa \
        -e DBAL_USER=sa \
        -e DATABASE_URL="pdo-sqlsrv://sa:P%40ssword123@sqlsrv2019:1433/test_db?serverVersion=2019&charset=utf8&driverOptions[TrustServerCertificate]=true" \
        phpunit vendor/bin/phpunit $@
}

# Display help
do_notice() {
    section_title "DbToolsTest dev scripts"

    printf "\nWelcome to DbToolsBundle dev script !"
    printf "\n"
    printf "\nThis script will help you to contribute to the DbToolsBundle."
    printf "\n"
    printf "\nIt will allow you to :"
    printf "\n"
    printf "\n  - build, up and down a complete docker stack with all database vendors"
    printf "\n    and versions that the DbToolsBundle supports"
    printf "\n  - run Code Style Fixer (with PHP CS Fixer) and launch Static Analysis (with PHPStan)"
    printf "\n  - run PHPunit tests for all database vendors"
    printf "\n\n--\n"
    printf "\nLaunch the script with one of these available actions:"
    printf "\n"
    printf "\n  - ${GREEN}build${NC}: Build docker containers"
    printf "\n  - ${GREEN}up${NC}: Start docker containers"
    printf "\n  - ${GREEN}down${NC}: Stop docker containers"
    printf "\n  - ${GREEN}checks${NC}: Launch composer checks (for Static analysis & Code style fixer)"
    printf "\n  - ${GREEN}test${NC}: Run PHPunit tests for all database vendors"
    printf "\n  - ${GREEN}notice${NC}: Display this help"
    printf "\n\n"
}

args=${@:-usage}
action=${1-}

if [[ -n $@ ]];then shift;fi

case $action in
    build|up|down|checks|test|notice) do_$action "$@";;
    *) do_notice;;
esac