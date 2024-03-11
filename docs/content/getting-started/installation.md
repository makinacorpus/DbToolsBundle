# Installation

The DbToolsBundle follows [Symfony Best Practices for Bundles](https://symfony.com/doc/current/bundles/best_practices.html),
you should not be lost if you are a regular Symfony developer.

## Requirements & Dependencies

- PHP 8.1 or higher
- Symfony 6.0 or higher
- Doctrine/DBAL, the DbToolsBundle takes advantage of available DBAL connections

Currently supported database vendors:

- PostgreSQL 10 and above
  <br><small>(previous versions from 9.5 are untested but should work)</small>
- MariaDB 10.11 and above
- MySQL 8.0 and above
- SQLite 3.0 and above
- SQL Server 2019 and above
  <br><small>(previous versions from 2015 are untested but should work)</small>

::: info
The bundle could also work with other database vendors.
Check out the [supported database vendors](../getting-started/database-vendors) page.
:::

## Installation

Add the *DbToolsBundle* to your Symfony project with [composer](https://getcomposer.org):

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
    MakinaCorpus\DbToolsBundle\DbToolsBundle::class => ['all' => true], // [!code ++]
];
```

Finally, copy the default configuration file from the vendor directory:

```sh
cd your_project_dir
cp vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml config/packages/
```

Feel free to read this configuration file, it will learn you basics about this bundle.

**That's it, *DbToolsBundle* is now ready to be used.**

But before starting to use it, check if the *DbToolsBundle* succeeds to find
backup and restore binaries for your(s) Doctrine connection(s):

```sh
php bin/console db-tools:check
```

:::tip
If this command returns some errors, go to the [binaries configuration](../configuration#binaries)
section to understand how to solve them.
:::
