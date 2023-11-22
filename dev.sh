#!/bin/bash

RED='\033[0;31m'
NC='\033[0m' # No Color

section_title() {
    printf "${RED}\n-------------------------------- ${NC}"
    printf "${RED}$1${NC}"
    printf "${RED} --------------------------------\n\n${NC}"
}

#  - build: Build docker containers
do_build() {
    section_title "Rebuilding containers"
    docker compose -p db_tools_bundle_test build;
}

#  - up: Start docker containers
do_up() {
    section_title "Up containers"
    docker compose -p db_tools_bundle_test up -d --force-recreate --remove-orphans
}

#  - down: Stop docker containers
do_down() {
    section_title "Down containers"
    docker compose -p db_tools_bundle_test down
}

#  - checks: Launch composer checks (for Static analysis & Code style fixer)
do_checks() {
    section_title "Composer checks"
    echo 'composer install'
    docker compose -p db_tools_bundle_test exec phpunit composer install
    echo 'composer checks'
    docker compose -p db_tools_bundle_test exec phpunit composer checks
}

#  - test: Run PHPunit tests for all database vendors
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
        phpunit vendor/bin/phpunit $@
}

#  - notice: Display this help
do_notice() {
    section_title "DbToolsTest dev scripts"

    echo 'Welcome to DbToolsBundle dev script !'
    echo
    echo 'This script will help you to contribute to the DbToolsBundle.'
    echo
    echo 'It will allow you to :'
    echo '  - build, up and down a complete docker stack with all database vendors'
    echo '    and versions that the DbToolsBundle supports'
    echo '  - run Code Style Fixer (with PHP CS Fixer) and launch Static Analysis (with PHPStan)'
    echo '  - run PHPunit tests for all database vendors'
    echo
    echo 'Launch the script with one of these available actions:'

    # Show autodoc help
    awk '{ if ($0 ~ /^#[^!]/) { \
                gsub(/^#/, "", $0); print $0 } }' "$0"
    echo " "
}

args=${@:-usage}
action=${1-}

if [[ -n $@ ]];then shift;fi

case $action in
    build|up|down|checks|test|notice) do_$action "$@";;
    *) do_notice;;
esac