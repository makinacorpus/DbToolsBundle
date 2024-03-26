[![Coding standards](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/coding-standards.yml) [![Static Analysis](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/static-analysis.yml) [![Documentation build](https://github.com/makinacorpus/DbToolsBundle/actions/workflows/docs-build.yml/badge.svg)](https://github.com/makinacorpus/DbToolsBundle/actions/workflows/docs-build.yml) [![Continuous Integration](https://github.com/makinacorpus/DbToolsBundle/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/makinacorpus/DbToolsBundle/actions/workflows/continuous-integration.yml)


# DbToolsBundle
A set of Symfony Console Commands to interact with your database

<p align="center" style="margin: auto">
    <a href="https://dbtoolsbundle.readthedocs.io/" target="_blank">
        <img style="height:160px;" src="/docs/content/public/logo.svg">
    </a>
</p>

* **[Backup](https://dbtoolsbundle.readthedocs.io/en/stable/backup_restore.html#backup-command)**: Backup your database and manage your dumps with a simple command.
* **[Restore](https://dbtoolsbundle.readthedocs.io/en/stable/backup_restore.html#restore-command)**: Easily restore a previous dump of your database.
* **[Anonymize](https://dbtoolsbundle.readthedocs.io/en/stable/anonymization/essentials.html)**: Set up database anonymization with PHP attributes on Doctrine Entities or with a YAML configuration file.
* [Set up a **GDPR-friendly** workflow](https://dbtoolsbundle.readthedocs.io/en/stable/anonymization/workflow.html): Make it easier to follow GDPR best practices when importing production dump to other environments.

## Installation

DbToolsBundle requires PHP 8.1 or higher and Symfony 6.0 or higher. Run the following command to install it in your application:

```sh
composer require makinacorpus/db-tools-bundle
```

## Documentation

Read [DbToolsBundle documentation](https://dbtoolsbundle.readthedocs.io/) on Read the Docs.

## Contributing

The DbToolsBundle is an Open Source project, if you want to help check out the [contribute page on the documentation](https://dbtoolsbundle.readthedocs.io/en/stable/contribute/contribute.html).

## Licence

This software is published under the [MIT License](./LICENCE.md).
