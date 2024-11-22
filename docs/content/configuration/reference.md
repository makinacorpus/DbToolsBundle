# Configuration reference

Configuration options will vary depending on which flavor you want to use.

Select below your target:

<FlavorSwitcher />

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

## Index

[`anonymizer_paths`](#anonymizer-paths) |
[`backup_binary`](#backup-binary) |
[`backup_excluded_tables`](#backup-excluded-tables) |
[`backup_expiration_age`](#backup-expiration-age) |
[`backup_options`](#backup-options) |
[`backup_timeout`](#backup-timeout) |
[`connections` (standalone)](#connections) |
[`default_connection`](#default-connection) |
[`restore_binary`](#restore-binary) |
[`restore_options`](#restore-options) |
[`restore_timeout`](#restore-timeout) |
[`storage_directory`](#storage-directory) |
[`storage_filename_strategy`](#storage-filename-strategy) |
[`workdir`](#workdir)

## `anonymizer_paths`

PHP source folders in which custom anonymizer implementations will be looked-up.

This allows you to write custom implementations and use it.

Path are local filesystem arbitrary paths, and you are allowed to set any path.
A recursive file system iterator will lookup in those folders and find classes
that extend the `MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer`
class within, then register those as anonymizers.

<div class="symfony">

```yaml
db_tools:
    anonymizer_paths:
        - '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer'
        - '%kernel.project_dir%/src/Anonymization/Anonymizer'
```
</div>
<div class="standalone">

```yaml
anonymizer_paths:
    - './vendor/makinacorpus/db-tools-bundle/src/Anonymizer'
    - './src/Anonymization/Anonymizer'
```

</div>


## `backup_binary`

Path to backup command in filesystem.

Defaults are the well known executable names without absolute file path, which should
work in most Linux distributions.


<div class="symfony">

```yaml
db_tools:
    backup_binary: /usr/bin/pg_dump
```
</div>
<div class="standalone">

```yaml
backup_binary: /usr/bin/pg_dump
```

</div>

:::warning
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.backup_binary` name.

If you have more than one connection using different database vendor, it is strongly
advised to override at the connection level.
:::


## `backup_excluded_tables`

Tables excluded from backup.

Example:

<div class="symfony">

```yaml
db_tools:
    backup_excluded_tables: ['table1', 'table2']
```
</div>
<div class="standalone">

```yaml
backup_excluded_tables: ['table1', 'table2']
```

</div>

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

<div class="symfony">

```yaml
db_tools:
    backup_expiration_age: '6 months ago'
```
</div>
<div class="standalone">

```yaml
backup_expiration_age: '6 months ago'
```

</div>

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

<div class="symfony">

```yaml
db_tools:
    backup_options: '-Z 5 --lock-wait-timeout=120'
```
</div>
<div class="standalone">

```yaml
backup_options: '-Z 5 --lock-wait-timeout=120'
```

</div>

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

<div class="symfony">

```yaml
# As a date interval string.
db_tools:
    backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    backup_timeout: 67
```
</div>
<div class="standalone">

```yaml
# As a date interval string.
backup_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
backup_timeout: 67
```

</div>

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

<div class="symfony">

```yaml
# With connection specific options.
db_tools:
    connections:
        connection_one:
            # ... Apply here connection-specific options.
            # @todo Add here all available options.
```
</div>
<div class="standalone">

```yaml
# With connection specific options.
connections:
    connection_one:
        url: "pgsql://username:password@hostname:port?version=16.0&other_option=..."
        # ... Apply here connection-specific options.
        # @todo Add here all available options.
    connection_two: #...

# With all default options, only database DSN.
connections:
    connection_two: "mysql://username:password@hostname:port?version=8.1&other_option=..."
    connection_two: #...

# With a single connection.
# Connection name will be "default".
connections: "pgsql://username:password@hostname:port?version=16.0&other_option=..."
```

</div>


## `default_connection`

Default connection name when connection is unspecified in the command line.

If none set, the first one in list will be used instead.

When using a Symfony bundle, the Doctrine bundle default connection is set to
be the default if this option is not specified.

<div class="symfony">

```yaml
db_tools:
    default_connection: connection_one
```
</div>
<div class="standalone">

```yaml
default_connection: connection_one
```

</div>


## `restore_binary`

Path to restore command in filesystem.

Defaults are the well known executable names without absolute file path, which should
work in most Linux distributions.

<div class="symfony">

```yaml
db_tools:
    restore_binary: /usr/bin/pg_restore
```
</div>
<div class="standalone">

```yaml
restore_binary: /usr/bin/pg_restore
```

</div>

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

<div class="symfony">

```yaml
db_tools:
    restore_options: '-j 2 --clean --if-exists --disable-triggers'
```
</div>
<div class="standalone">

```yaml
restore_options: '-j 2 --clean --if-exists --disable-triggers'
```

</div>

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

<div class="symfony">

```yaml
# As a date interval string.
db_tools:
    restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
db_tools:
    restore_timeout: 67
```
</div>
<div class="standalone">

```yaml
# As a date interval string.
restore_timeout: '2 minutes and 7 seconds'

# As a number of seconds.
restore_timeout: 67
```

</div>

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.restore_timeout` name.
:::


## `storage_directory`

Root directory of the backup storage manager. Default filename strategy will
always use this folder as a root path.

<div class="symfony">

```yaml
db_tools:
    storage_directory: "%kernel.root_dir%/var/db_tools"
```
</div>
<div class="standalone">

```yaml
storage_directory: "./var/db_tools"
```

</div>

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

<div class="symfony">

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
</div>
<div class="standalone">

```yaml
# Default value, `default` is an alias of `datetime`.
storage_filename_strategy: default

# Explicit default.
storage_filename_strategy: datetime

# Using a service name.
storage_filename_strategy: app.my_filename_strategy

# Using a class name.
storage_filename_strategy: App\DbTools\Storage\MyCustomFilenameStrategy
```

</div>

:::tip
This top level parameter applies to all connections per default.
If you need a different value per connection, the setting can also be configured on a
per connection basis under the `connections.CONNECTION.storage_filename_strategy` name.
:::


## `workdir`

Default path in which all relative file system path found in the same config
path will be relative to.

If none set, directory in which the configuration file is will be used instead.

<div class="standalone">

```yaml
workdir: /some/project/path/config
```

</div>

:::tip
This options is specific for standalone usage.
:::
