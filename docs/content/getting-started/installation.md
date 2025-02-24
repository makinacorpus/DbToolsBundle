# Installation

Installation method will depend on which [flavor](./flavors.md) you want to use:

<FlavorSwitcher />

@@@ symfony

*DbToolsBundle* follows [Symfony Best Practices for Bundles](https://symfony.com/doc/current/bundles/best_practices.html),
you should not be lost if you are a regular Symfony developer.

@@@

## Requirements & Dependencies

@@@ standalone
- PHP 8.1 or higher
@@@
@@@ symfony
- PHP 8.1 or higher
- Symfony 6.0 or higher
- Doctrine/DBAL, *DbToolsBundle* takes advantage of available DBAL connections
@@@
<!--- NB: There is no specific requirements for Docker flavor -->

Currently supported database vendors:

- PostgreSQL 10 and above
  <br><small>(previous versions from 9.5 are untested but should work)</small>
- MariaDB 10.11 and above
- MySQL 5.7, 8.0 and above
- SQLite 3.0 and above

::: info
The library could also work with other database vendors.
Check out the [supported database vendors](../getting-started/database-vendors) page.
:::

## Installation

@@@ standalone
Add *DbToolsBundle* to your PHP project with [composer](https://getcomposer.org):

```sh
composer require makinacorpus/db-tools-bundle
```

Then, copy the default configuration file from the vendor directory:

```sh
cd your_project_dir
cp vendor/makinacorpus/db-tools-bundle/config/db_tools.standalone.sample.yaml db_tools.config.yaml
```

Update this file to your needs. The only required parameter is `connections` in which you
must provided an [URL connection string](../configuration/reference#connections).
@@@
@@@ symfony
Add *DbToolsBundle* to your Symfony project with [composer](https://getcomposer.org):

```sh
composer require makinacorpus/db-tools-bundle
```

Then, activate the bundle:

```php
// config/bundles.php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    //...
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    // ...
    MakinaCorpus\DbToolsBundle\Bridge\Symfony\DbToolsBundle::class => ['all' => true], // [!code ++]
];
```

Finally, copy the default configuration file from the vendor directory:

```sh
cd your_project_dir
cp vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml config/packages/
```

Feel free to read this configuration file, it will learn you basics about this bundle.
@@@
@@@ docker
You can add the [*DbToolsBundle* image](https://hub.docker.com/r/makinacorpus/dbtoolsbundle) to your docker compose stack like in this example:

```yaml
version: '3.8'

services:
  postgres:
    image: postgres:15
    environment:
      POSTGRES_PASSWORD: password
      POSTGRES_DB: db
      POSTGRES_USER: db
    ports:
      - 5439:5432
    volumes:
      - pgdata:/var/lib/postgresql/data
    networks:
      - site

  dbtools: // [!code ++]
    image: makinacorpus/dbtoolsbundle:stable // [!code ++]
    networks: // [!code ++]
      - site // [!code ++]
    volumes: // [!code ++]
      - ./db_tools.config.yaml:/var/www/db_tools.config.yaml // [!code ++]
      - ./db_tools:/var/www/var/db_tools // [!code ++]

networks:
  site:

volumes:
  pgdata:
```

Then, copy the default configuration file from the vendor directory:

```sh
cd your_project_dir
cp vendor/makinacorpus/db-tools-bundle/config/db_tools.standalone.sample.yaml db_tools.config.yaml
```

Update this file to your needs. The only required parameter is `connections` in which you
must provided a [connection string](../configuration/reference#connections).

In our example, the connection string will be `pgsql://db:password@postgres:5432/db?version=15.0`.
@@@

**That's it, *DbToolsBundle* is now ready to be used.**

But before starting to use it, check if it succeeds to find back up and
restoration binaries for your database connection(s):

@@@ standalone
```sh
vendor/bin/db-tools check
```
@@@
@@@ symfony
```sh
php bin/console db-tools:check
```
@@@
@@@ docker
```sh
docker compose run dbtools check
```
@@@

:::tip
If this command returns some errors, go to the [binaries configuration](../configuration/basics#binaries)
section to understand how to solve them.
:::

@@@ symfony
:::warning
While installing the bundle through composer, the standalone binary will also be installed in
the `vendor/bin/` directory.

**You must not use this binary but the Symfony Console commands.**

The binary will try to look for a config in `db_tools.config.yaml` while the Symfony Console commands
will use the bundle configuration (which autoconfigures the database connections).
:::
@@@
