#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# PHP version.
PHPVER="8-5"
# Extra parameters passed to all docker command lines.
EXTRA_DOCKER_ENV=""

# Parse arguments.
while getopts ":xp:l" opt; do
    case "${opt}" in
        x)
            EXTRA_DOCKER_ENV="${EXTRA_DOCKER_ENV} -e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=1"
            ;;
        l)
            LOWEST=1
            ;;
        p)
            p=${OPTARG}
            case "${OPTARG}" in
                "8.1")
                    PHPVER="8-1"
                    ;;
                "8.2")
                    PHPVER="8-2"
                    ;;
                "8.3")
                    PHPVER="8-3"
                    ;;
                "8.4")
                    PHPVER="8-4"
                    ;;
                "8.5")
                    PHPVER="8-5"
                    ;;
                *)
                    PHPVER="8-5"
                    ;;
            esac
            ;;
        *)
            usage
            ;;
    esac
done
shift $((OPTIND-1))

# phpunit container variant to use.
PHPUNIT_CONTAINER="phpunit-${PHPVER}"

section_title() {
    printf "${RED}\n-------------------------------- ${NC}"
    printf "${RED}$1${NC}"
    printf "${RED} --------------------------------\n${NC}"
}

# Run docker compose for project
do_docker_compose() {
    docker compose -p db_tools_bundle_test "$@"
}

# Build docker containers
do_build() {
    section_title "Rebuilding containers"
    do_docker_compose build;
}

# Start docker containers
do_up() {
    section_title "Up containers"
    do_docker_compose up -d --force-recreate --remove-orphans
}

# Stop docker containers
do_down() {
    section_title "Down containers"
    do_docker_compose down
}

# Clean all containers, images and volumes
do_clean() {
    section_title "Cleanup containers and images"
    do_docker_compose down --rmi all --volumes --remove-orphans
}

do_composer_update() {
    echo 'composer update'
    if [[ -z "${LOWEST}" ]]; then
        do_docker_compose exec $PHPUNIT_CONTAINER composer update
    else
        do_docker_compose exec $PHPUNIT_CONTAINER composer update --prefer-lowest
    fi
}

# Launch composer checks (for Static analysis & Code style fixer)
do_checks() {
    section_title "Composer checks"

    do_composer_update

    echo 'composer checks'
    do_docker_compose exec $PHPUNIT_CONTAINER composer checks
}

# Launch PHPUnit tests without any database vendor
do_unittest() {
    section_title "PHPUnit unit tests"
    do_docker_compose exec $PHPUNIT_CONTAINER vendor/bin/phpunit
}

do_ps() {
    do_docker_compose ps
}

do_test_mysql80() {
    section_title "Running tests with MySQL 8.0"
    # --skip-ssl for MariaDB client, --ssl-mode=disabled for MySQL client
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DBTOOLS_BACKUP_OPTIONS="--no-tablespaces --skip-ssl" \
        -e DBTOOLS_RESTORE_OPTIONS="--skip-ssl" \
        -e DATABASE_URL=mysql://root:password@mysql80:3306/test_db?serverVersion=8.0 \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_mysql84() {
    section_title "Running tests with MySQL 8.4"
    # --skip-ssl for MariaDB client, --ssl-mode=disabled for MySQL client
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DBTOOLS_BACKUP_OPTIONS="--no-tablespaces --skip-ssl" \
        -e DBTOOLS_RESTORE_OPTIONS="--skip-ssl" \
        -e DATABASE_URL=mysql://root:password@mysql84:3306/test_db?serverVersion=8.4 \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_mysql() {
    do_test_mysql80 "$@"
    do_test_mysql84 "$@"
}

do_test_mariadb11() {
    section_title "Running tests with MariaDB 11"
    # --skip-ssl for MariaDB client, --ssl-mode=disabled for MySQL client
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DBTOOLS_BACKUP_OPTIONS="--no-tablespaces --skip-ssl" \
        -e DBTOOLS_RESTORE_OPTIONS="--skip-ssl" \
        -e DATABASE_URL=mysql://root:password@mariadb11:3306/test_db?serverVersion=mariadb-11.1.3 \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_mariadb12() {
    section_title "Running tests with MariaDB 12"
    # --skip-ssl for MariaDB client, --ssl-mode=disabled for MySQL client
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DBTOOLS_BACKUP_OPTIONS="--no-tablespaces --skip-ssl" \
        -e DBTOOLS_RESTORE_OPTIONS="--skip-ssl" \
        -e DATABASE_URL=mysql://root:password@mariadb12:3306/test_db?serverVersion=mariadb-12.1.2 \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_mariadb() {
    do_test_mariadb11 "$@"
    do_test_mariadb12 "$@"
}

do_test_postgresql10() {
    section_title "Running tests with PostgreSQL 10"
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DATABASE_URL="postgresql://postgres:password@postgresql10:5432/test_db?serverVersion=10&charset=utf8" \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_postgresql16() {
    section_title "Running tests with PostgreSQL 16"
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DATABASE_URL="postgresql://postgres:password@postgresql16:5432/test_db?serverVersion=16&charset=utf8" \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_postgresql() {
    do_test_postgresql10 "$@"
    do_test_postgresql16 "$@"
}

do_test_sqlsrv2019() {
    section_title "Running tests with SQL Server 2019"
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DATABASE_URL="sqlsrv://sa:P%40ssword123@sqlsrv2019:1433/test_db?serverVersion=2019&charset=utf8&driverOptions[TrustServerCertificate]=true" \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_sqlsrv2022() {
    section_title "Running tests with SQL Server 2022"
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DATABASE_URL="sqlsrv://sa:P%40ssword123@sqlsrv2022:1433/test_db?serverVersion=2022&charset=utf8&driverOptions[TrustServerCertificate]=true" \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

do_test_sqlsrv() {
    do_test_sqlsrv2019 "$@"
    do_test_sqlsrv2022 "$@"
}

# SQLite version depends upon the PHP embeded version or linked
# library, we cannot target X or Y version.
do_test_sqlite() {
    section_title "Running tests with SQLite"
    do_docker_compose exec $EXTRA_DOCKER_ENV \
        -e DATABASE_URL="sqlite:///test_db.sqlite" \
        "$PHPUNIT_CONTAINER" vendor/bin/phpunit "$@"
}

# Run PHPunit tests for all database vendors
do_test_all() {
    do_composer_update

    do_test_mysql80 "$@"
    do_test_mysql84 "$@"
    do_test_mariadb11 "$@"
    do_test_mariadb12 "$@"
    do_test_postgresql10 "$@"
    do_test_postgresql16 "$@"
    do_test_postgresql18 "$@"
    do_test_sqlsrv2019 "$@"
    do_test_sqlsrv2022 "$@"
    do_test_sqlite "$@"
}

do_test_notice() {
    section_title "Test a specicif database vendor or version"
    printf "\nThis action will allow you to test a specific vendor or version."
    printf "\n"
    printf "\nLaunch this action with one of these available options:"
    printf "\n"
    printf "\n  - ${GREEN}mysql${NC}: Launch test for all MySQL versions"
    printf "\n  - ${GREEN}mysql80${NC}: Launch test for MySQL 8.0"
    printf "\n  - ${GREEN}mysql84${NC}: Launch test for MySQL 8.4"
    printf "\n  - ${GREEN}mariadb${NC}: Launch test for all MariaDB versions"
    printf "\n  - ${GREEN}mariadb11${NC}: Launch test for MariaDB 11"
    printf "\n  - ${GREEN}mariadb12${NC}: Launch test for MariaDB 12"
    printf "\n  - ${GREEN}postgresql${NC}: Launch test for all PostgreSQL versions"
    printf "\n  - ${GREEN}postgresql10${NC}: Launch test for PostgreSQL 10"
    printf "\n  - ${GREEN}postgresql16${NC}: Launch test for PostgreSQL 16"
    printf "\n  - ${GREEN}sqlsrv${NC}: Launch test for all SQL Server versions"
    printf "\n  - ${GREEN}sqlsrv2019${NC}: Launch test for SQL Server 2019"
    printf "\n  - ${GREEN}sqlsrv2022${NC}: Launch test for SQL Server 2022"
    printf "\n  - ${GREEN}sqlite${NC}: Launch test for SQLite"
    printf "\n\nYou can then use PHPUnit option as usual:"
    printf "\n${GREEN}./dev.sh test mysql --filter AnonymizatorFactoryTest${NC}"
    printf "\n\n"
}

do_test() {
    suit=${1-}

    if [[ -n $@ ]];then shift;fi

    case $suit in
        mysql80|mysql84|mysql|mariadb11|mariadb12|mariadb|postgresql10|postgresql16|postgresql|sqlsrv2019|sqlsrv2022|sqlsrv|sqlite) do_composer_update && do_test_$suit "$@";;
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
    printf "\n  - ${GREEN}clean${NC}: Cleanup containers and images"
    printf "\n  - ${GREEN}notice${NC}: Display this help"
    printf "\n"
    printf "\nAvailable options:"
    printf "\n  ${GREEN}-l${NC}: run ${GREEN}composer update${NC} with ${GREEN}--prefer-lowest${NC} option"
    printf "\n  ${GREEN}-x${NC}: trigger ${GREEN}xdebug${NC} when running test suites (ignored otherwise)"
    printf "\n  ${GREEN}-p 8.3${NC}: choose PHP version to run from ${GREEN}8.1${NC} to ${GREEN}8.5${NC}"
    printf "\n"
    printf "\n\n"
}

args=${@:-usage}
action=${1-}

if [[ -n $@ ]];then shift;fi

case $action in
    build|up|down|ps|checks|test_all|unittest|test|composer_update|notice|clean) do_$action "$@";;
    *) do_notice;;
esac
