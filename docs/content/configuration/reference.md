# Configuration reference

Configuration options will vary depending on which flavor you want to use.

Select below your target:

<FlavorSwitcher />

This toolset can be run in various contextes:

  - as a Symfony bundle via the Symfony project console,
  - as a standalone console tool,
  - with its Docker image.

In all cases, it requires a configuration file. When running throught the
Symfony project console, configuration file is not required since it will
auto-configure by reading your Symfony site configuration.

:::tip
When configuring in Symfony you must add an extra `db_tools` top-level
section in order to avoid conflicts with other bundles. When configuring
for the standalone console tool or for the Docker image, this extra top-level section must be
omitted.
:::

:::warning
When working with the standalone console tool or with the Docker image, all relative path are
relative to the `workdir` option. If none provided, then path are
relative to the configuration file directory the path is defined
within.
:::

<div class="toc-inline">

  [[toc]]

</div>

<style>
.toc-inline .table-of-contents ul {
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  padding-left: 0;
}
.toc-inline .table-of-contents li {
  margin: 2px 4px;
  padding: 1px 4px;
  border-radius: 4px;
  display: inline-block;
  background: var(--vp-code-bg);

}
</style>

## `anonymization`

Write the anonymization configuration directly in the main configuration file.

Keys under the first dimension are connections names, then follows the structure
expected in anonymization configuration files.

@@@ symfony
```yaml
# When you have a single connection, and a single file:
db_tools:
    anonymization:
        connection_one:
            table1:
                column1:
                    anonymizer: anonymizer_name
                    # ... anonymizer specific options...
                column2:
                    # ...
            table2:
                # ...
        connection_two:
            # ...
```

:::tip
Connection names are Doctrine bundle connection names. If you have a single
one with Symfony default configuration, its name is `default`.
:::
@@@
@@@ standalone docker
```yaml
anonymization:
    connection_one:
        table1:
            column1:
                anonymizer: anonymizer_name
                # ... anonymizer specific options...
            column2:
                # ...
        table2:
            # ...
    connection_two:
        # ...
```

:::tip
Whenever you have a single unamed connection, its name will be `default`.
:::
@@@

:::tip
For more information about anonymization structure, refer to the [Anonymization section](../anonymization/essentials).
:::

## `anonymization_files`

Files that contains anonymization configuration.

@@@ symfony
```yaml
# When you have a single connection, and a single file:
db_tools:
    anonymization_files: '%kernel.project_dir%/config/anonymizations.yaml'

# Or with multiple connections:
db_tools:
    anonymization_files:
        connection_one: '%kernel.project_dir%/config/anonymizations/connection_one.yaml'
        connection_two: '%kernel.project_dir%/config/anonymizations/connection_two.yaml'

# Each connection may have multiple files:
db_tools:
    anonymization_files:
        connection_one:
            - '%kernel.project_dir%/config/anonymizations/connection_one_1.yaml'
            - '%kernel.project_dir%/config/anonymizations/connection_one_2.yaml'
        # ...
```

:::tip
Connection names are Doctrine bundle connection names. If you have a single
one with Symfony default configuration, its name is `default`.
:::

:::tip
File paths must be absolute, use Symfony parameters to refer the project root.
:::
@@@
@@@ standalone docker
```yaml
# When you have a single connection, and a single file:
anonymization_files: './anonymizations.yaml'

# Or with multiple connections:
anonymization_files:
    connection_one: './anonymizations/connection_one.yaml'
    connection_two: './anonymizations/connection_two.yaml'

# Each connection may have multiple files:
anonymization_files:
    connection_one:
        - './anonymizations/connection_one_1.yaml'
        - './anonymizations/connection_one_2.yaml'
    # ...
```

:::tip
Whenever you have a single unamed connection, its name will be `default`.
:::

:::tip
File paths can be relative, any relative path will be relative to this
configuration file directory.
:::
@@@

:::tip
For more information about anonymization and configuration file structure,
refer to the [Anonymization section](../anonymization/essentials).
:::


## `anonymizer_paths`

PHP source folders in which custom anonymizer implementations will be looked-up.

This allows you to write custom implementations and use it.

Path are local filesystem arbitrary paths, and you are allowed to set any path.
A recursive filesystem iterator will look in those folders for classes extending
the `MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer`
class and register them as anonymizers.

@@@ symfony
Be aware that DbToolsBundle will always take a look at the default folder
dedicated to your custom anonymizers: `%kernel.project_dir%/src/Anonymizer`,
so you don't have to repeat it.

```yaml
db_tools:
    anonymizer_paths:
        - '%kernel.project_dir%/src/Database/Anonymizer'
```

:::tip
File paths must be absolute, use Symfony parameters to refer the project root.
:::
@@@
@@@ standalone docker
```yaml
anonymizer_paths:
    - './src/Anonymizer'
```

:::tip
File paths can be relative, any relative path will be relative to this
configuration file directory.
:::
@@@

## `backup_binary`

Path to backup command in filesystem.

Defaults are the well known executable names without absolute file path, which
should work in most Linux distributions.

@@@ symfony
```yaml
db_tools:
    backup_binary: /usr/bin/pg_dump
```
@@@
@@@ standalone docker
```yaml
backup_binary: /usr/bin/pg_dump
```
@@@

:::warning
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured
on a per-connection basis under the `connections.CONNECTION.backup_binary` name.

If you have more than one connection using different database vendor, it is
strongly advised to override at the connection level.
:::


## `backup_excluded_tables`

Tables excluded from backup.

Example:

@@@ symfony
```yaml
db_tools:
    backup_excluded_tables: ['table1', 'table2']
```
@@@
@@@ standalone docker
```yaml
backup_excluded_tables: ['table1', 'table2']
```
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.backup_excluded_tables` name.
:::


## `backup_expiration_age`

Backup file expiration time after which they get deleted when running
the `backup` or `clean` command.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative

Example:

@@@ symfony
```yaml
db_tools:
    backup_expiration_age: '6 months ago'
```
@@@
@@@ standalone docker
```yaml
backup_expiration_age: '6 months ago'
```
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.backup_expiration_age` name.
:::


## `backup_options`

Allows you to add specific command line options to the backup command.

If you do not define some default options, here or by using the "--extra-options" option when
invoking the command, the following ones will be used according to the database vendor:
 - MariaDB: `--no-tablespaces`
 - MySQL: `--no-tablespaces`
 - PostgreSQL: `-Z 5 --lock-wait-timeout=120`
 - SQLite: `-bail`

By specifying options, the default ones will be dropped.

@@@ symfony
```yaml
db_tools:
    backup_options: '-Z 5 --lock-wait-timeout=120'
```
@@@
@@@ standalone docker
```yaml
backup_options: '-Z 5 --lock-wait-timeout=120'
```
@@@

:::warning
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.backup_options` name.

If you have more than one connection using different database vendor, it is strongly
advised to override at the connection level.
:::


## `backup_timeout`

Backup process timeout in seconds.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative
or accepts a number of seconds as an integer value.

Example:

@@@ symfony
```yaml
# As a date interval string.
db_tools:
    backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    backup_timeout: 67
```
@@@
@@@ standalone docker
```yaml
# As a date interval string.
backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
backup_timeout: 67
```
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.backup_timeout` name.
:::


## `connections`

All reachable connection list, with their an URL connection string.

In standalone mode, connections are handled by `makinacorpus/query-builder`.

When using a Symfony bundle, all connections from the Doctrine bundle using
Doctrine DBAL will be automatically registered, you need this section only if
you need to add connection specific options.

@@@ symfony
```yaml
db_tools:
    connections:
        connection_one:
            # Complete list of accepted parameters follows.
            backup_binary: /usr/local/bin/vendor-one-dump
            backup_excluded_tables: ['table_one', 'table_two']
            backup_expiration_age: '1 month ago'
            backup_options: --no-table-lock
            backup_timeout: 2000
            restore_binary: /usr/local/bin/vendor-one-restore
            restore_options: --disable-triggers --other-option
            restore_timeout: 5000
            storage_directory: /path/to/storage
            storage_filename_strategy: datetime
```
@@@
@@@ standalone docker
```yaml
# With connection specific options.
connections:
    connection_one:
        # Connection URL for connecting.
        # Please refer to makinacorpus/db-query-builder or  documentation for more information.
        # Any URL built for doctrine/dbal usage should work.
        # URL is the sole mandatory parameter.
        # Complete list of accepted parameters follows.
        url: "pgsql://username:password@hostname:port/database?version=16.0&other_option=..."
        backup_binary: /usr/local/bin/vendor-one-dump
        backup_excluded_tables: ['table_one', 'table_two']
        backup_expiration_age: '1 month ago'
        backup_options: --no-table-lock
        backup_timeout: 2000
        restore_binary: /usr/local/bin/vendor-one-restore
        restore_options: --disable-triggers --other-option
        restore_timeout: 5000
        storage_directory: /path/to/storage
        storage_filename_strategy: datetime
    connection_two:
        #...

# With all default options, only database DSN.
connections:
    connection_two: "mysql://username:password@hostname:port/database?version=8.1&other_option=..."
    connection_two: #...

# With a single connection.
# Connection name will be "default".
connections: "pgsql://username:password@hostname:port/database?version=16.0&other_option=..."
```

:::warning
If you configure this parameter with a single URL string with no connection name,
the connection name will be `default`.
:::
@@@

:::tip
All parameters for each connection are exactly the same as the top-level parameters
documented in this file.
:::


## `default_connection`

Default connection name when connection is unspecified in the command line.

If none set, the first one in list will be used instead.

When using a Symfony bundle, the Doctrine bundle default connection is set to
be the default if this option is not specified.

@@@ symfony
```yaml
db_tools:
    default_connection: connection_one
```
@@@
@@@ standalone docker
```yaml
default_connection: connection_one
```
@@@


## `restore_binary`

Path to restore command in filesystem.

Defaults are the well known executable names without absolute file path, which should
work in most Linux distributions.

@@@ symfony
```yaml
db_tools:
    restore_binary: /usr/bin/pg_restore
```
@@@
@@@ standalone docker
```yaml
restore_binary: /usr/bin/pg_restore
```
@@@

:::warning
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.restore_binary` name.

If you have more than one connection using different database vendor, it is strongly
advised to override at the connection level.
:::


## `restore_options`

Allows you to add specific command line options to the restore command.

If you do not define some default options, here or by using the "--extra-options" option when
invoking the command, the following ones will be used according to the database vendor:
 - MariaDB: None
 - MySQL: None
 - PostgreSQL: `-j 2 --clean --if-exists --disable-triggers`
 - SQLite: None

@@@ symfony
```yaml
db_tools:
    restore_options: '-j 2 --clean --if-exists --disable-triggers'
```
@@@
@@@ standalone docker
```yaml
restore_options: '-j 2 --clean --if-exists --disable-triggers'
```
@@@

:::warning
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.restore_options` name.

If you have more than one connection using different database vendor, it is strongly
advised to override at the connection level.
:::


## `restore_timeout`

Restore process timeout in seconds.

It uses a relative date interval format as documented in https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative
or accepts a number of seconds as an integer value.

Example:

@@@ symfony
```yaml
# As a date interval string.
db_tools:
    restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    restore_timeout: 67
```
@@@
@@@ standalone docker
```yaml
# As a date interval string.
restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
restore_timeout: 67
```
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.restore_timeout` name.
:::


## `storage_directory`

Root directory of the backup storage manager. Default filename strategy will
always use this folder as a root path.

@@@ symfony
```yaml
db_tools:
    storage_directory: "%kernel.root_dir%/var/db_tools"
```
@@@
@@@ standalone docker
```yaml
storage_directory: "./var/db_tools"
```
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.storage_directory` name.
:::


## `storage.filename_strategy`

Default backup filename strategy that will generate the backup file names.

Generated backup filenames will always be relative to the connection or global
root directory. Available options are:
- `default`: let the tool decide, it is an alias to `datetime`.
- `datetime`: stores backups in split timestamp directory tree, such as: `<storage_directory>/YYYY/MM/<connection_name>-<timestamp>.<ext>`
- any class name implementing the `MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface` interface.

When used in a Symfony application, the strategy can be a service name registered in the
container. This service must implement `MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface`.
See [filename strategies documentation](../backup_restore) for more information.

Example:

@@@ symfony
```yaml
# Default value, `default` is an alias of `datetime`.
db_tools:
    storage_filename_strategy: default

# Explicit default.
db_tools:
    storage_filename_strategy: datetime

# Using a service name.
db_tools:
    storage_filename_strategy: app.my_filename_strategy

# Using a class name.
db_tools:
    storage_filename_strategy: App\DbTools\Storage\MyCustomFilenameStrategy
```
@@@
@@@ standalone docker
:::warning
There is as of now no way to implement a custom filename strategy when using
*DbToolsBundle* as a standalone CLI tool or with the Docker image.

If you need this feature, please let us know by [creating an issue](https://github.com/makinacorpus/DbToolsBundle/issues).
:::
@@@

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.storage_filename_strategy` name.
:::


## `workdir`

Default path in which all relative file system path found in the same config
path will be relative to.

If none set, directory in which the configuration file is will be used instead.

@@@ standalone docker
```yaml
workdir: /some/project/path/config
```
@@@

:::tip
This options is specific for standalone usage.
:::
