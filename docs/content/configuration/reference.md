# Configuration reference

## Introduction

This toolset can be run in various contextes:

  - as a Symfony bundle via the Symfony project console,
  - as a standalone console tool.

In all cases, it requires a configuration file. When running throught the
Symfony project console, configuration file is not required since it will
auto-configure by reading your Symfony site configuration.

:::tip
When configuring in Symfony you must add an extra `db_tools` top-level
section in order to avoid conflicts with other bundles. When configuring
for the standalone console tool, this extra top-level section must be
omitted.
:::

:::warning
When working with the standalone console tool, all relative path are
relative to the `workdir` option. If none provided, then path are
relative to the configuration file directory the path is defined
within.
:::

## All options

[`anonymization.tables` (standalone)](#anonymization-tables) |
[`anonymization.yaml`](#anonymization-yaml) |
[`anonymizer_paths`](#anonymizer-paths) |
[`backup_expiration_age`](#backup-expiration-age) |
[`backup_timeout`](#backup-timeout) |
[`backupper_binaries`](#backupper-binaries) |
[`backupper_options`](#backupper-options) |
[`connections` (standalone)](#connections) |
[`default_connection` (standalone)](#default-connection) |
[`excluded_tables`](#excluded-tables) |
[`restore_timeout`](#restore-timeout) |
[`restorer_binaries`](#restorer-binaries) |
[`restorer_options`](#restorer-options) |
[`storage.filename_strategy`](#storage-filename-strategy) |
[`workdir` (standalone)](#workdir)

## Common options

### `storage.root_dir`

Root directory of the backup storage manager. Default filename strategy will
always use this folder as a root path.

:::code-group
```yaml [Symfony]
db_tools:
    storage:
        root_dir: "%kernel.root_dir%/var/db_tools"
```

```yaml [Standalone]
storage:
    root_dir: "./var/db_tools"
```
:::

### `storage.filename_strategy`

Key value pairs, keys are connection names, values can be either:
- `default`: let the tool decide, it is an alias to `datetime`.
- `datetime`: stores backups in split timestamp directory tree, such as: `<root_dir>/YYYY/MM/<connection_name>-<timestamp>.<ext>`

When used in a Symfony application, the strategy can be a service name registered in the
container. This service must implement `MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface`.
See [filename strategies documentation](../backup_restore) for more information.

Example:

:::code-group
```yaml [Symfony]
db_tools:
    storage:
        filename_strategy:
            connection_one: datetime
            connection_two: default
            connection_three: app.my_filename_strategy
            connection_four: App\DbTools\Storage\MyCustomFilenameStrategy
```

```yaml [Standalone]
storage:
    filename_strategy:
        connection_one: datetime
        connection_two: default
        connection_four: App\DbTools\Storage\MyCustomFilenameStrategy
```
:::

### `backup_expiration_age`

Backup file expiration time after which they get deleted when running
the `backup` or `clean` command.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative

Example:

:::code-group
```yaml [Symfony]
db_tools:
    backup_expiration_age: '6 months ago'
```

```yaml [Standalone]
backup_expiration_age: '6 months ago'
```
:::

### `backup_timeout`

Backup process timeout in seconds.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative
or accepts a number of seconds as an integer value.

Example:

:::code-group
```yaml [Symfony]
# As a date interval string.
db_tools:
    backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    backup_timeout: 67
```

```yaml [Standalone]
# As a date interval string.
backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
backup_timeout: 67
```
:::

### `excluded_tables`

Tables excluded from backup.

Example:

:::code-group
```yaml [Symfony]
db_tools:
    excluded_tables:
        connection_one: ['table1', 'table2']
        connection_two: ['table1', 'table2']
```

```yaml [Symfony alt.]
db_tools:
    # If you have a single connection.
    excluded_tables: ['table1', 'table2']
```

```yaml [Standalone]
excluded_tables:
    connection_one: ['table1', 'table2']
    connection_two: ['table1', 'table2']
```

```yaml [Standalone alt.]
    # If you have a single connection.
    excluded_tables: ['table1', 'table2']
```
:::


### `backupper_binaries`

Path to backup command in filesystem.

Defaults are the well known executable names without absolute file path, which should
work in most Linux distributions.


:::code-group
```yaml [Symfony]
db_tools:
    backupper_binaries:
        mariadb: /usr/bin/mariadb-dump
        mysql: /usr/bin/mysqldump
        postgresql: /usr/bin/pg_dump
        sqlite: /usr/bin/sqlite3
```

```yaml [Standalone]
backupper_binaries:
    mariadb: /usr/bin/mariadb-dump
    mysql: /usr/bin/mysqldump
    postgresql: /usr/bin/pg_dump
    sqlite: /usr/bin/sqlite3
```
:::

### `restorer_binaries`

Path to restore command in filesystem.

Defaults are the well known executable names without absolute file path, which should
work in most Linux distributions.

:::code-group
```yaml [Symfony]
db_tools:
    restorer_binaries:
        mariadb: /usr/bin/mariadb
        mysql: /usr/bin/mysql
        postgresql: /usr/bin/pg_restore
        sqlite: /usr/bin/sqlite3
```

```yaml [Standalone]
restorer_binaries:
    mariadb: /usr/bin/mariadb
    mysql: /usr/bin/mysql
    postgresql: /usr/bin/pg_restore
    sqlite: /usr/bin/sqlite3
```
:::

### `backupper_options`

Allows you to add specific command line options to the backup command, one for each connection.

If you do not define some default options, here or by using the "--extra-options" option when
invoking the command, the following ones will be used according to the database vendor:
 - MariaDB: `--no-tablespaces`
 - MySQL: `--no-tablespaces`
 - PostgreSQL: `-Z 5 --lock-wait-timeout=120`
 - SQLite: `-bail`

By specifying options, the default ones will be dropped.

:::code-group
```yaml [Symfony]
db_tools:
    backupper_options:
        connection_one: '-Z 5 --lock-wait-timeout=120'
        connection_two: '--no-tablespaces'
```

```yaml [Standalone]
backupper_options:
    connection_one: '-Z 5 --lock-wait-timeout=120'
    connection_two: '--no-tablespaces'
```
:::

### `restore_timeout`

Restore process timeout in seconds.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative
or accepts a number of seconds as an integer value.

Example:

:::code-group
```yaml [Symfony]
# As a date interval string.
db_tools:
    restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    restore_timeout: 67
```

```yaml [Standalone]
# As a date interval string.
restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
restore_timeout: 67
```
:::

### `restorer_options`

Allows you to add specific command line options to the restore command, one for each connection.

If you do not define some default options, here or by using the "--extra-options" option when
invoking the command, the following ones will be used according to the database vendor:
 - MariaDB: None
 - MySQL: None
 - PostgreSQL: `-j 2 --clean --if-exists --disable-triggers`
 - SQLite: None

:::code-group
```yaml [Symfony]
db_tools:
    backupper_options:
        connection_one: '-j 2 --clean --if-exists --disable-triggers'
        connection_two: '--no-tablespaces'
```

```yaml [Standalone]
backupper_options:
    connection_one: '-j 2 --clean --if-exists --disable-triggers'
    connection_two: '--some-other-option
```
:::

### `anonymizer_paths`

PHP source folders in which custom anonymizer implementations will be looked-up.

This allows you to write custom implementations and use it.

Path are local filesystem arbitrary paths, and you are allowed to set any path.
A recursive file system iterator will lookup in those folders and find classes
that extend the `MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer`
class within, then register those as anonymizers.

:::code-group
```yaml [Symfony]
db_tools:
    anonymizer_paths:
        - '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer'
        - '%kernel.project_dir%/src/Anonymization/Anonymizer'
```

```yaml [Standalone]
    anonymizer_paths:
        - './vendor/makinacorpus/db-tools-bundle/src/Anonymizer'
        - './src/Anonymization/Anonymizer'
```
:::

### `anonymization.yaml`

List of YAML configuration file that contains table and their columns to
anonymize.

For configuration format please refer the [anonymizers documentation](../anonymization/core-anonymizers).

:::code-group
```yaml [Symfony]
db_tools:
    anonymizer:
        yaml:
            - connection_one: '%kernel.project_dir%/config/anonymizations/connection_one.yaml'
            - connection_two: '%kernel.project_dir%/config/anonymizations/connection_two.yaml'
```

```yaml [Symfony alt.]
db_tools:
    anonymizer:
        # If you have a single connection.
        yaml: '%kernel.project_dir%/config/anonymizations.yaml'
```

```yaml [Standalone]
anonymizer:
    yaml:
        - connection_one: './db_tools.connection_one.anonymization.yaml'
        - connection_two: './db_tools.connection_two.anonymization.yaml'
```

```yaml [Standalone alt.]
anonymizer:
    yaml: './db_tools.anonymization.yaml'
```
:::

## Symfony bundle specific options

None yet, all options can be used in the standalone console version as well.

## Standalone specific options

### `workdir`

Default path in which all relative file system path found in the same config
path will be relative to.

If none set, directory in which the configuration file is will be used instead.

:::code-group
```yaml [Standalone]
workdir: /some/project/path/config
```
:::

### `connections`

All reachable connection list, with their an URL connection string.

In standalone mode, connections are handled by `makinacorpus/query-builder`.

```yaml [Standalone]
connections:
    connection_one: "pgsql://username:password@hostname:port?version=16.0&other_option=..."
    connection_two: "mysql://username:password@hostname:port?version=8.1&other_option=..."

# Connection name will be "default"
connections: "pgsql://username:password@hostname:port?version=16.0&other_option=..."
```

### `default_connection`

Default connection name when connection is unspecified in the command line.

If none set, the first one in list will be used instead.

```yaml [Standalone]
default_connection: connection_one
```

### `anonymization.tables`

You can write anonymization configuration directly in the configuration file when
using the standalone mode. This prevent configuration file profileration.

Configuration file can be dumped from the Symfony bundle, then used with the
standalone connection.

```yaml [Standalone]
anonymization:
    tables:
        connection_one:
            table_name:
                column_name:
                    anonymizer: anonymizer_name
                    # ... other options...
```
