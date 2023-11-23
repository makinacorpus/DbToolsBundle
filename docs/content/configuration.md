# Bundle configuration

The *DbToolsBundle* let you configure some of its behaviours. As with any classic Symfony Bundle,
all will take place in the `config/packages/db_tools.yaml` file.

:::tip
A complete example of this file can be found in the bundle sources in: `vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml`
:::

## Backup configuration

Some options are available to customize how the `db-tools:backup` command works.

### Storage directory

The `storage_directory` parameter let you choose where to put the generated dumps

Default value is `'%kernel.project_dir%/var/private/db_tools'`.

### Excluded tables

The `excluded_tables` parameter let you configure tables to exclude from backups. You will need to give a
configuration per doctrine connection.

Default value is `null`: no table are excluded.

Here is an example for exclude `table1` and `table2` for the `default` doctrine connection:

```yml
# config/packages/db_tools.yaml

db_tools:

    #...

    excluded_tables:
        - default: ['table1', 'table2']

    #...
```

:::tip
Note that you can override this configuration while running the `db-tools:backup` command using
the `--exclude` option.
:::

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

## Binaries

`db-tools:backup` and `db-tools:restore` need your system/environment to provide some extra binaries
to be able to work. These binaries depend on the database vendor you use, you will need:
* for PostgreSQL: `pg_dump` and `pg_restore`
* for MariaDB/MySQL: `mysqldump` and `mysql`

You can verify that binaries for your DBAL connection(s) are correctly found by the *DbToolsBundle* launching:

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

      backupper_binaries:
          pgsql: 'usr/bin/pg_dump' # default 'pg_dump'
          pdo_pgsql: 'usr/bin/pg_dump' # default 'pg_dump'
          pdo_mysql: 'usr/bin/mysqldump' # default 'mysqldump'
      restorer_binaries:
          pgsql: 'usr/bin/pg_restore' # default 'pg_restore'
          pdo_pgsql: 'usr/bin/pg_restore' # default 'pg_restore'
          pdo_mysql: 'usr/bin/mysql' # default 'mysql'

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

## Anonymizer paths

By default, the *DbToolsBundle* will look for *anonymizers* in 2 directories

* `%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer`
* `%kernel.project_dir%/src/Anonymizer`

If you want to put custom anonymizers in another directory or if you want to load
a pack of anonymizers from en external library, you can modify/add paths:


```yml
# config/packages/db_tools.yaml

 anonymizer_paths:
        - '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymization/Anonymizer'
        - '%kernel.project_dir%/src/Anonymizer'
        - '%kernel.project_dir%/vendor/myAnonymizerProvider/anonymizers/src'
```

## Anonymization

Per default, the **DbToolsBundle** will only look for anonymization configurations from PHP attributes on Doctrine Entities.

But the **DbToolsBundle** does not necessary need Doctrine ORM to anonymize your data, it can do it just with a DBAL connection.
In this case (or if your prefere YAML over attributes): you can configure the DbToolsBundle to look for anonymization
configurations in a YAML file:

```yml
# config/packages/db_tools.yaml

db_tools:
    # ...

    # Anonymization configuration.
    anonymization:
        # If you want to load configuration from a yaml:
        # 1/ If you want to configure anonymization only for the default
        # DBAL connection, declare it like this:
        yaml: '%kernel.project_dir%/config/anonymizations.yaml'
        # 2/ If you use multiple connection, declare each configuration like this:
        #yaml:
            #- connection_one: '%kernel.project_dir%/config/anonymizations/connection_one.yaml'
            #- connection_two: '%kernel.project_dir%/config/anonymizations/connection_two.yaml'

  #...
```

:::tip
For more information about anonymization, refere to the [Anonymization section](./anonymization/essentials).
:::
