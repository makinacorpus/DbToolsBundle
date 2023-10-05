# Configuration

The *DbToolsBundle* let you configure some of its behaviours. All of these
parameters as to be setted up in the `config/packages/db_tools.yaml` file.

## Backup configuration

Some options are available to customize how the `db-tools:backup` works.

### Storage directory

The `storage_directory` parameter let you choose where to put the generated dumps

Default value is `'%kernel.project_dir%/var/private/db_tools'`.

### Excluded tables

The `excluded_tables` parameter let you configure tables to exclude from backups. Give a configuration a
per doctrine connection

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
the `--excluded-tables` option.
:::

### Backup expiration age

The `backup_expiration_age` parameter let you choose when a backup is considered as obsolete.

Default value is `'3 months ago'`.

Use [PHP relative date/time formats](https://www.php.net/manual/en/datetime.formats.relative.php) for this value.

Here is an exemple value:

```yml
# config/packages/db_tools.yaml

db_tools:

  #...

  backup_expiration_age: '1 week ago'

  #...
```

## Binaries

`db-tools:backup` and `db-tools:restore` need your system/environment to provide some extra binaries to be able to work.
These binaries depend on the database vendor you use, you will need:
* for PostgreSQL: `pg_dump` and `pg_restore`
* for MariaDB/MySQL: `mysqldump` and `mysql`

If the `db-tools:check` command returns you some errors:
 * Your binaries are present on your system but the DbToolsBundle can't find them: you will need to specify path for these binaries
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
 * Or, your binaries are not present on your system: you will need to install them



:::tip
If your app lives in the [official PHP docker image](https://hub.docker.com/_/php/),
you can install correct binaries adding these lines to your Dockerfile,

for PostgreSQL:

```
RUN apt-get update && \
    apt-get install -y --no-install-recommends postgresql postgresql-client libpq-dev \
     -yqq && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install -j$(nproc) pgsql pdo_pgsql pdo && \
    docker-php-ext-enable pdo_pgsql
```

for MariaDB/MySQL:

```
RUN apt-get update && \
    apt-get install -y --no-install-recommends default-mysql-client
     -yqq && \
    docker-php-ext-install -j$(nproc) pdo mysqli pdo_mysql && \
    docker-php-ext-enable pdo_mysql && \
```
:::

## Anonymization

How the **DbToolsBundle** anonymize your database can be configured in this file.

Here is a complete example:

```yml
# config/packages/db_tools.yaml

db_tools:

  #...

  anonymization:
    default: # There is one configuration per doctrine connection
      # Configuration to anonymize a table named `user`
      user:
        # Some Anonymizer does not require any option, you can use them like this
        prenom: MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\PrenomAnonymizer
        nom: MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\NomAnonymizer
        # Some does require options, specify them like this
        age:
          anonymizer: MakinaCorpus\DbToolsBundle\Anonymizer\Common\IntegerAnonymizer
          options: {min: 0, max: 99}
        # Some has optionnal options, specify them
        email:
          anonymizer: MakinaCorpus\DbToolsBundle\Anonymizer\Common\EmailAnonymizer
          options: {domain: 'toto.com'}
        # Or not
        email: MakinaCorpus\DbToolsBundle\Anonymizer\Common\EmailAnonymizer
        level:
          anonymizer: MakinaCorpus\DbToolsBundle\Anonymizer\Common\StringAnonymizer
          options: {sample: ['none', 'bad', 'good', 'expert']}
        # Given you have columns `street`, `zip_code`, `city` and `country`,
        # this configuration will fill these column with real, coherent address
        # from a ~300 elements sample.
        address:
          target: table
          anonymizer: MakinaCorpus\DbToolsBundle\Anonymizer\Common\AddressAnonymizer
          options:
            street_address: 'street'
            # secondary_address:
            postal_code: 'zip_code'
            locality: 'city'
            # region:
            country: 'country'

  #...
```

:::tip
For more information about anonymization, refere to the [Anonymization section](/anonymization/general-concepts).
:::
