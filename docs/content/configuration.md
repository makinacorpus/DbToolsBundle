# Bundle configuration

The *DbToolsBundle* let you configure some of its behaviors. As with any classic Symfony Bundle,
all will take place in the `config/packages/db_tools.yaml` file.

:::tip
A complete example of this file can be found in the bundle sources in: `vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml`
:::

For detailed information about configuration options, please see the
[configuration reference](configuration/reference).

## Backup configuration

Some options are available to customize how the `db-tools:backup` command works.

### Storage

#### Root directory

The `db_tools.storage.root_dir` parameter let you choose where to put the generated dumps.

Default value is `'%kernel.project_dir%/var/db_tools'`.

#### File and directory naming strategy

Default behavior will store your backup using this strategy:
`%db_tools.storage.root_dir%/<YEAR>/<MONTH>/<CONNECTION-NAME>-<YEAR><MONTH><DAY><HOUR><MINUTES><SECOND>.<EXT>`
where `<EXT>` is the file extension depending upon the database backend (mostly `.sql` or `.dump`).

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

### Excluded tables

The `backup_excluded_tables` parameter let you configure tables to exclude from backups. You will need to give a
configuration per doctrine connection.

Default value is `null`: no table are excluded.

Here is an example for exclude `table1` and `table2` for the `default` doctrine connection:

```yml
# config/packages/db_tools.yaml

db_tools:

    #...

    backup_excluded_tables:
        default: ['table1', 'table2']

    #...
```

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

```yml
# config/packages/db_tools.yaml

db_tools:

    #...

    backup_expiration_age: '1 week ago'

    #...
```

### Backup and restore timeout

The `backup_timeout` and `restore_timeout` options let you choose what is the backup and restore
processes timeout in seconds.

Default value is `600` (seconds) for backup, `1800` (seconds) for restore.

Value can be either a [\DateInterval::createFromDateString()](https://www.php.net/manual/en/dateinterval.createfromdatestring.php)
compatible string value or a number of seconds as an integer value.

```yml
# config/packages/db_tools.yaml

db_tools:

    #...

    # As a date interval string.
    backup_timeout: '6 minutes 30 seconds'
    restore_timeout: '3 minutes 15 seconds'

    # As a number of seconds integer value.
    backup_timeout: 390
    restore_timeout: 195

    #...
```

## Binaries

`db-tools:backup` and `db-tools:restore` need your system/environment to provide some extra binaries
to be able to work. These binaries depend on the database vendor you use, you will need:
* for MariaDB: `mariadb-dump` and `mariadb`
* for MySQL: `mysqldump` and `mysql`
* for PostgreSQL: `pg_dump` and `pg_restore`
* for SQLite: `sqlite3`

You can verify if those binaries are well found by the *DbToolsBundle*,
for each of your DBAL connections, by launching:

```sh
php console db-tools:check
```

If the `db-tools:check` command returns you some errors:
 * if your binaries are present on your system but the *DbToolsBundle* can't find them: you will need
   to specify path for these binaries:

  ```yml
  # config/packages/db_tools.yaml

  db_tools:

      #...

      # Specify here paths to binaries, only if the system can't find them by himself
      # platform are 'mysql', 'postgresql', 'sqlite'
      backup_binaries:
          mariadb: '/usr/bin/mariadb-dump'
          mysql: '/usr/bin/mysqldump'
          postgresql: '/usr/bin/pg_dump'
          sqlite: '/usr/bin/sqlite3'
      restore_binaries:
          mariadb: '/usr/bin/mariadb'
          mysql: '/usr/bin/mysql'
          postgresql: '/usr/bin/pg_restore'
          sqlite: '/usr/bin/sqlite3'

      #...
  ```
 * Or, if your binaries are not present on your system: you will need to install them



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

Apart from the essential options (credentials, database name, etc.), the bundle
also passes a few default options to the binary depending on the operation being
performed and the invoked binary itself. You can customize those default options
by configuring your own ones per operation type and DBAL connection:

```yaml
# config/packages/db_tools.yaml
db_tools:
    # ...

    backup_options:
        default: '--an-option'
        another_connection: '-xyz --another'
    restore_options:
        default: '--a-first-one --a-second-one'
        another_connection: '-O sample-value'
```

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

By default, the *DbToolsBundle* will look for *anonymizers* in 2 directories

* `%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer`
* `%kernel.project_dir%/src/Anonymizer`

If you want to put custom anonymizers in another directory or if you want to load
a pack of anonymizers from an external library, you can modify/add paths:


```yml
# config/packages/db_tools.yaml

db_tools:
    # ...

    anonymizer_paths:
        - '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymization/Anonymizer'
        - '%kernel.project_dir%/src/Anonymizer'
        - '%kernel.project_dir%/vendor/myAnonymizerProvider/anonymizers/src'

    # ...
```

## Anonymization

Per default, the **DbToolsBundle** will only look for anonymization configurations from PHP attributes on Doctrine Entities.

But the **DbToolsBundle** does not necessary need Doctrine ORM to anonymize your data, it can do it just with a DBAL connection.
In this case (or if you prefer YAML over attributes): you can configure the DbToolsBundle to look for anonymization
configurations in a YAML file:

```yml
# config/packages/db_tools.yaml

db_tools:
    # ...

    anonymization:
        # If you want to load configuration from a yaml:
        # 1/ If you want to configure anonymization only for the default
        # DBAL connection, declare it like this:
        yaml: '%kernel.project_dir%/config/anonymizations.yaml'
        # 2/ If you use multiple connections, declare each configuration like this:
        #yaml:
            #- connection_one: '%kernel.project_dir%/config/anonymizations/connection_one.yaml'
            #- connection_two: '%kernel.project_dir%/config/anonymizations/connection_two.yaml'

  #...
```

:::tip
For more information about anonymization, refer to the [Anonymization section](./anonymization/essentials).
:::
