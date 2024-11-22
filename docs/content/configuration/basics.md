# Configuration basics

Configuration options will vary depending on which flavor you want to use.

Select below your target:

<FlavorSwitcher />

<div class="symfony">

*DbToolsBundle* let you configure some of its behaviors. As with any classic Symfony Bundle,
all will take place in the `config/packages/db_tools.yaml` file.

:::tip
A complete example of this file can be found in the bundle sources in: `vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml`.
:::

</div>

<div class="standalone">

*DbToolsBundle* let you configure some of its behaviors
all will take place in your configuration file, usually `db_tools.yaml`.

:::tip
**In this page, all paths are relative to the `db_tools.yaml` configuration file.**

A complete example of this file can be found in the library sources in: `vendor/makinacorpus/db-tools-bundle/config/db_tools.standalone.complete.sample.yaml`.
:::

</div>

For detailed information about configuration options, please see the
[configuration reference](configuration/reference).

:::tip
**Almost every configuration option can be configured at the connection level** for example the
backup excluded tables can either be configured top-level (for all connections):

<div class="symfony">

```yml
# config/packages/db_tools.yaml
db_tools:
    backup_excluded_tables: ['table1', 'table2']
```

Or for each connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    connections:
        connection_one:
            backup_excluded_tables: ['table1', 'table2']
        connection_two:
            backup_excluded_tables: ['table3', 'table4']
```

</div>
<div class="standalone">

```yml
# db_tools.yaml
backup_excluded_tables: ['table1', 'table2']
```

Or for each connection:

```yml
# db_tools.yaml
connections:
    connection_one:
        backup_excluded_tables: ['table1', 'table2']
    connection_two:
        backup_excluded_tables: ['table3', 'table4']
```

</div>

When working with multiple connections, any connection which does not specify the option
will inherit from the default.
:::

## Backup configuration

Some options are available to customize how the `db-tools:backup` command works.

### Storage

#### Root directory

The `db_tools.storage.root_dir` parameter let you choose where to put the generated dumps.

<div class="symfony">

Default value is `'%kernel.project_dir%/var/db_tools'`.

</div>

<div class="standalone">

Default value is `./var/db_tools'`.

</div>

#### File and directory naming strategy

Default behavior will store your backup using this strategy:
`%db_tools.storage.root_dir%/<YEAR>/<MONTH>/<CONNECTION-NAME>-<YEAR><MONTH><DAY><HOUR><MINUTES><SECOND>.<EXT>`
where `<EXT>` is the file extension depending upon the database vendor (mostly `.sql` or `.dump`).

<div class="symfony">

Custom strategy can be implemented by extending the
`MakinaCorpus\DbToolsBundle\Storage\AbstractFilenameStrategy` abstract class:

```php
namespace App\DbTools\Storage;

use MakinaCorpus\DbToolsBundle\Storage\AbstractFilenameStrategy;

class FooFilenameStrategy extends AbstractFilenameStrategy
{
    #[\Override]
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string {
        return '/some_folder/' . $connectionName . '.' . $extension;
    }
}
```

Then registered this way, on a per-connection basis:

```yaml
# config/packages/db_tools.yaml
db_tools:
    storage:
        filename_strategy:
            connection_name: App\DbTools\Storage\FooFilenameStrategy
```

Value can be a container service identifier, or directly a class name in case this
has no constructor arguments.

If you need to store your dumps outside of the `%db_tools.storage.root_dir%` directory,
then implement the `MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface` directly
and add the following method:

```php
namespace App\DbTools\Storage;

use MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface;

class FooFilenameStrategy implements FilenameStrategyInterface
{
    #[\Override]
    public function generateFilename(/* ... */): string {}

    #[\Override]
    public function getRootDir(
        string $defaultRootDir,
        string $connectionName = 'default',
    ): string {
        return '/some/path/' . $connectionName . '/foo';
    }
}
```

This will allow the restore command to find your backups.

</div>

<div class="standalone">

:::warning
There is as of now no way to implement a custom filename strategy when using *DbToolsBundle* as a standalone
CLI tool.

If you need this feature, please let us know by [filing an issue](https://github.com/makinacorpus/DbToolsBundle/issues).
:::

</div>

:::info
More filename strategies may be implemented in core in the future. If you have any
suggestions, please [open an discussion](https://github.com/makinacorpus/DbToolsBundle/issues) about it.
:::

### Excluded tables

The `backup_excluded_tables` parameter let you configure tables to exclude from backups.

Default value is `null`: no table are excluded.

<div class="symfony">

Here is an example for exclude `table1` and `table2` for all connections:

```yml
# config/packages/db_tools.yaml
db_tools:
    backup_excluded_tables: ['table1', 'table2']
```

Or set a specific table list for each connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    connections:
        connection_one:
            backup_excluded_tables: ['table1', 'table2']
        connection_two:
            backup_excluded_tables: ['table3', 'table4']
```
</div>
<div class="standalone">

Here is an example for exclude `table1` and `table2` for all connections:

```yml
# db_tools.yaml
backup_excluded_tables: ['table1', 'table2']
```

Or set a specific table list for each connection:

```yml
# db_tools.yaml
connections:
    connection_one:
        backup_excluded_tables: ['table1', 'table2']
    connection_two:
        backup_excluded_tables: ['table3', 'table4']
```
</div>

:::tip
Note that you can override this configuration while running the `db-tools:backup` command using
the `--exclude` option.
:::

### Binary options

See the [default binary options](#default-binary-options) section.

### Backup expiration age

The `backup_expiration_age` parameter let you choose when a backup is considered as obsolete.

Default value is `'3 months ago'`.

Use [PHP relative date/time formats](https://www.php.net/manual/en/datetime.formats.relative.php)
for this value.

<div class="symfony">

Here is an example that sets 1 week lifetime for backups for all connections:

```yml
# config/packages/db_tools.yaml
db_tools:
    backup_expiration_age: '1 week ago'
```

Or set a specific value list for each connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    connections:
        connection_one:
            backup_expiration_age: '1 week ago'
        connection_two:
            backup_expiration_age: '3 days ago'
```
</div>
<div class="standalone">

Here is an example that sets 1 week lifetime for backups for all connections:

```yml
# db_tools.yaml
backup_expiration_age: '1 week ago'
```

Or set a specific value list for each connection:

```yml
# db_tools.yaml
connections:
    connection_one:
        backup_expiration_age: '1 week ago'
    connection_two:
        backup_expiration_age: '3 days ago'
```
</div>

### Backup and restore timeout

The `backup_timeout` and `restore_timeout` options let you choose what is the backup and restore
processes timeout in seconds.

Default value is `600` (seconds) for backup, `1800` (seconds) for restore.

Value can be either a [\DateInterval::createFromDateString()](https://www.php.net/manual/en/dateinterval.createfromdatestring.php)
compatible string value or a number of seconds as an integer value.

<div class="symfony">

Here is an example that sets timeouts for all connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    # As a date interval string.
    backup_timeout: '6 minutes 30 seconds'
    restore_timeout: '3 minutes 15 seconds'

    # As a number of seconds integer value.
    backup_timeout: 390
    restore_timeout: 195
```

Or set a different timeout for each connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    connections:
        connection_one:
            backup_timeout: '6 minutes 30 seconds'
            restore_timeout: '3 minutes 15 seconds'
        connection_two:
            backup_timeout: 390
            restore_timeout: 195
```
</div>
<div class="standalone">

Here is an example that sets timeouts for all connection:

```yml
# db_tools.yaml

# As a date interval string.
backup_timeout: '6 minutes 30 seconds'
restore_timeout: '3 minutes 15 seconds'

# As a number of seconds integer value.
backup_timeout: 390
restore_timeout: 195
```

Or set a different timeout for each connection:

```yml
# db_tools.yaml
connections:
    connection_one:
        backup_timeout: '6 minutes 30 seconds'
        restore_timeout: '3 minutes 15 seconds'
    connection_two:
        backup_timeout: 390
        restore_timeout: 195
```
</div>

## Binaries

`db-tools:backup` and `db-tools:restore` need your system/environment to provide some extra binaries
to be able to work. These binaries depend on the database vendor you use, you will need:
* for MariaDB: `mariadb-dump` and `mariadb`
* for MySQL: `mysqldump` and `mysql`
* for PostgreSQL: `pg_dump` and `pg_restore`
* for SQLite: `sqlite3`

You can verify if those binaries are well found by *DbToolsBundle*,
for each of your connections, by launching:

<div class="symfony">

```sh
php bin/console db-tools:check
```
</div>
<div class="standalone">

```sh
php vendor/bin/db-tools database:check
```
</div>

If the `db-tools:check` command returns you some errors:
 * if your binaries are present on your system but *DbToolsBundle* can't find them you will need
   to specify path for these binaries:

  <div class="symfony">

  ```yml
  # config/packages/db_tools.yaml
  db_tools:
      backup_binary: '/usr/local/bin/pg_dump'
      restore_binary: '/usr/local/bin/pg_restore'
  ```
  </div>
  <div class="standalone">

  ```yml
  # db_tools.yaml
  backup_binary: '/usr/local/bin/pg_dump'
  restore_binary: '/usr/local/bin/pg_restore'
  ```
  </div>

 * Backup and restore binaries, as well as command line arguments and options are
   configured on a per-connection basis. If you have more than one connection,
   use the following syntax instead:

  <div class="symfony">

  ```yml
  # config/packages/db_tools.yaml
  db_tools:
      connections:
          connection_one:
              backup_binary: '/usr/local/bin/pg_dump'
              restore_binary: '/usr/local/bin/pg_restore'
          connection_two:
              backup_binary: '/usr/local/bin/mysqldump'
              restore_binary: '/usr/local/bin/mysql'
  ```
  </div>
  <div class="standalone">

  ```yml
  # db_tools.yaml
  connections:
      connection_one:
          backup_binary: '/usr/local/bin/pg_dump'
          restore_binary: '/usr/local/bin/pg_restore'
      connection_two:
          backup_binary: '/usr/local/bin/mysqldump'
          restore_binary: '/usr/local/bin/mysql'
  ```
  </div>

 * Or, if your binaries are not present on your system: you will need to install them.

:::tip
If your app lives in the [official PHP docker image](https://hub.docker.com/_/php/),
you can install correct binaries adding these lines to your Dockerfile,

for PostgreSQL:

```
RUN apt-get update && \
    apt-get install -y --no-install-recommends postgresql-client
```

for MariaDB/MySQL:

```
RUN apt-get update && \
    apt-get install -y --no-install-recommends default-mysql-client
```
:::

:::warning
Dump and restore is not supported yet for SQL Server.
:::

### Default binary options

Apart from the essential options (credentials, database name, etc.), the library
also passes a few default options to the binary depending on the operation being
performed and the invoked binary itself. You can customize those default options
by configuring your own ones per operation type and connection:

<div class="symfony">

Here is an example that sets options for all connections:

```yml
# config/packages/db_tools.yaml
db_tools:
    backup_options: '--an-option'
    restore_options: '--a-first-one --a-second-one'
```

Or set a specific value list for each connection:

```yml
# config/packages/db_tools.yaml
db_tools:
    connections:
        connection_one:
            backup_options: '--an-option'
            restor_options: '-xyz --another'
        connection_two:
            backup_options: '--a-first-one --a-second-one'
            restor_options: '-O sample-value'
```
</div>
<div class="standalone">

Here is an example that sets options for all connections:

```yml
# db_tools.yaml
backup_options: '--an-option'
restore_options: '--a-first-one --a-second-one'
```

Or set a specific value list for each connection:

```yml
# db_tools.yaml
connections:
    connection_one:
        backup_options: '--an-option'
        restor_options: '-xyz --another'
    connection_two:
        backup_options: '--a-first-one --a-second-one'
        restor_options: '-O sample-value'
```
</div>

If you do not define your own default options, the following ones will be used
according to the database vendor:

* When backing up:
  * MariaDB: `--no-tablespaces`
  * MySQL: `--no-tablespaces`
  * PostgreSQL: `-Z 5 --lock-wait-timeout=120`
  * SQLite: `-bail`
* When restoring:
  * MariaDB: None
  * MySQL: None
  * PostgreSQL: `-j 2 --clean --if-exists --disable-triggers`
  * SQLite: None

## Anonymizer paths

<div class="symfony">

By default, *DbToolsBundle* will look for custom *anonymizers* in 2 directories:

* `%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer`
* `%kernel.project_dir%/src/Anonymizer`

If you want to put custom anonymizers in another directory or if you want to load
a pack of anonymizers from an external library, you can modify/add paths:


```yml
# config/packages/db_tools.yaml
db_tools:
    anonymizer_paths:
        - '%kernel.project_dir%/src/Anonymizer'
        - '%kernel.project_dir%/vendor/myAnonymizerProvider/anonymizers/src'
        # ...
```
</div>
<div class="standalone">

By default, *DbToolsBundle* when used as a standalone CLI tool will not lookup for
custom *anonymizers*.

If you want to write custom anonymizers, in order for them to be found, you must specify
paths where the source code lies:


```yml
# db_tools.yaml
db_tools:
    anonymizer_paths:
        - './src/Anonymizer'
        - './vendor/myAnonymizerProvider/anonymizers/src'
        # ...
```
</div>

:::tip
Core provided anonymizers and anonymizers that are in packs installed using composer
will always be looked-up.
:::

:::warning
Packs must be installed using composer: *DbToolsBundle* uses composer generated metadata
about installed packages to find them.
:::

## Anonymization

<div class="symfony">

Per default, **DbToolsBundle** will only look for anonymization configurations from PHP attributes on Doctrine Entities.

But *DbToolsBundle* does not necessary need Doctrine ORM to anonymize your data, it can do it just with a DBAL connection.
In this case (or if you prefer YAML over attributes): you can configure *DbToolsBundle* to look for anonymization
configurations in a YAML file:

```yml
# config/packages/db_tools.yaml
db_tools:

    # When you have a single connection, and a single file:
    anonymization_files: '%kernel.project_dir%/config/anonymizations.yaml'

    # Or with multiple connections:
    anonymization_files:
        connection_one: '%kernel.project_dir%/config/anonymizations/connection_one.yaml'
        connection_two: '%kernel.project_dir%/config/anonymizations/connection_two.yaml'

    # Each connection may have multiple files:
    anonymization_files:
        connection_one:
            - '%kernel.project_dir%/config/anonymizations/connection_one_1.yaml'
            - '%kernel.project_dir%/config/anonymizations/connection_one_2.yaml'
        # ...
```

</div>
<div class="standalone">

You need to register your anonymization configuration for the anonymization feature to work:

```yml
# config/packages/db_tools.yaml
db_tools:

    # When you have a single connection, and a single file:
    anonymization_files: './anonymizations.yaml'

    # Or with multiple connections:
    anonymization_files:
        connection_one: './anonymizations/connection_one.yaml'
        connection_two: './anonymizations/connection_two.yaml'

    # Each connection may have multiple files:
    anonymization_files:
        connection_one:
            - './config/anonymizations/connection_one_1.yaml'
            - './config/anonymizations/connection_one_2.yaml'
        # ...
```

</div>

:::tip
For more information about anonymization and configuration file structure, refer to the [Anonymization section](./anonymization/essentials).
:::
