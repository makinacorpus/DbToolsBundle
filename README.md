# DbToolsBundle
a set of Symfony Console Commands to interact with your database

![Logo](./docs/content/images/logo.svg)

- `db-tools:backup`: Backup your database and deals with old backups cleanup
- `db-tools:restore`: Restore your database from previous backups
- `db-tools:anonymize`: Launch setted up anonymization on your database
- `db-tools:gdprify` (comming soon): Restore, anonymize & re-export a given dump
- `db-tools:stats` (comming soon): Display usefull statistics about your database

Currently supported database vendors: PostgreSQL, MariaDB/MySQL

## Installation

DbToolsBundle requires PHP 8.1 or higher and Symfony 6.0 or higher. Run the following command to install it in your application:

```sh
composer require makinacorpus/db-tools-bundle
```

## Documentation

Read [DbToolsBundle documentation](https://db-tools-bundle.readthedocs.io/) on Read the Docs.

## Licence

This software is published under the [MIT License](./LICENCE.md).
