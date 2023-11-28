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

do_composer_update() {
    echo 'composer update'
    docker compose -p db_tools_bundle_test exec phpunit composer update
}

# Launch composer checks (for Static analysis & Code style fixer)
do_checks() {
    section_title "Composer checks"

    do_composer_update

    echo 'composer checks'
    docker compose -p db_tools_bundle_test exec phpunit composer checks
}

# Launch PHPUnit tests without any database vendor
do_unittest() {
    section_title "PHPUnit unit tests"
    docker compose -p db_tools_bundle_test exec phpunit vendor/bin/phpunit
}

do_test_mysql57() {
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
}

do_test_mysql80() {
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
}

do_test_mariadb11() {
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
        -e DATABASE_URL=mysql://root:password@mariadb11:3306/test_db?serverVersion=mariadb-11.1.3 \
        phpunit vendor/bin/phpunit $@
}

do_test_mysql() {
    do_test_mysql57
    do_test_mysql80
    do_test_mariadb11
}

do_test_postgresql10() {
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
}

do_test_postgresql16() {
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
}

do_test_postgresql() {
    do_test_postgresql10
    do_test_postgresql16
}

do_test_sqlsrv2019() {
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

do_test_sqlsrv() {
    do_test_sqlsrv2019
}

# SQLite version depends upon the PHP embeded version or linked
# library, we cannot target X or Y version.
do_test_sqlite() {
    section_title "Running tests with SQLite"
    docker compose -p db_tools_bundle_test exec \
        -e DBAL_DRIVER=pdo_sqlite \
        -e DBAL_DBNAME=test_db \
        -e DBAL_HOST=127.0.0.1 \
        -e DATABASE_URL="pdo-sqlite:///:memory:" \
        phpunit vendor/bin/phpunit $@
}

# Run PHPunit tests for all database vendors
do_test_all() {
    do_composer_update

    # @todo Temporary deactivated MySQL 5.7 due to a bug.
    # do_test_mysql57
    do_test_mysql80
    do_test_mariadb11
    do_test_postgresql10
    do_test_postgresql16
    do_test_sqlsrv2019
    do_test_sqlite
}

do_test_notice() {
    section_title "Test a specicif database vendor or version"
    printf "\nThis action will allow you to test a specific vendor or version."
    printf "\n"
    printf "\nLaunch this action with one of these available options:"
    printf "\n"
    printf "\n  - ${GREEN}mysql${NC}: Launch test for MySQL 5.7, MySQL 8.0 & MariaDB 11"
    printf "\n  - ${GREEN}mysql57${NC}: Launch test for MySQL 5.7"
    printf "\n  - ${GREEN}mysql80${NC}: Launch test for MySQL 8.0"
    printf "\n  - ${GREEN}mariadb11${NC}: Launch test for MariaDB 11"
    printf "\n  - ${GREEN}postgresql${NC}: Launch test for PostgreSQL 10 & 16"
    printf "\n  - ${GREEN}postgresql10${NC}: Launch test for PostgreSQL 10"
    printf "\n  - ${GREEN}postgresql16${NC}: Launch test for PostgreSQL 16"
    printf "\n  - ${GREEN}sqlsrv${NC}: Launch test for SQL Server 2019"
    printf "\n  - ${GREEN}sqlsrv2019${NC}: Launch test for SQL Server 2019"
    printf "\n  - ${GREEN}sqlite${NC}: Launch test for SQLite"
    printf "\n\nYou can then use PHPUnit option as usual:"
    printf "\n${GREEN}./dev.sh test mysql --filter AnonymizatorFactoryTest${NC}"
    printf "\n\n"
}

do_test() {
    suit=${1-}

    if [[ -n $@ ]];then shift;fi

    case $suit in
        mysql57|mysql80|mariadb11|mysql|postgresql10|postgresql16|postgresql|sqlsrv2019|sqlsrv|sqlite) do_composer_update && do_test_$suit "$@";;
        *) do_test_notice;;
    esac
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
    printf "\n  - run PHPUnit tests for all database vendors"
    printf "\n\n--\n"
    printf "\nLaunch the script with one of these available actions:"
    printf "\n"
    printf "\n  - ${GREEN}build${NC}: Build docker containers"
    printf "\n  - ${GREEN}up${NC}: Start docker containers"
    printf "\n  - ${GREEN}down${NC}: Stop docker containers"
    printf "\n  - ${GREEN}checks${NC}: Launch composer checks (for Static analysis & Code style fixer)"
    printf "\n  - ${GREEN}test_all${NC}: Run PHPUnit tests for all database vendors."
    printf "\n              PHPUnit options can be used as usual:"
    printf "\n              ${GREEN}./dev.sh test_all --filter AnonymizatorFactoryTest${NC}"
    printf "\n  - ${GREEN}test${NC}: Run PHPUnit tests for a specific database vendors or version"
    printf "\n  - ${GREEN}unittest${NC}: Run PHPUnit tests without any database vendor"
    printf "\n  - ${GREEN}notice${NC}: Display this help"
    printf "\n\n"
}

args=${@:-usage}
action=${1-}

if [[ -n $@ ]];then shift;fi

case $action in
    build|up|down|checks|test_all|unittest|test|notice) do_$action "$@";;
    *) do_notice;;
esac