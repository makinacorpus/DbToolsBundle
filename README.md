[![Coding standards](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/coding-standards.yml) [![Static Analysis](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/makinacorpus/db-tools-bundle/actions/workflows/static-analysis.yml)  [![Documentation Status](https://readthedocs.org/projects/dbtoolsbundle/badge/?version=stable)](https://dbtoolsbundle.readthedocs.io/en/stable/?badge=stable)

# DbToolsBundle
A set of Symfony Console Commands to interact with your database

---
<p align="center" style="margin: auto">
<strong>⚠️⚠️⚠️ Work in progress ⚠️⚠️⚠️</strong>
</p>

---

<p align="center" style="margin: auto">
    <img style="height:160px;" src="/docs/content/public/logo.svg">
</p>

* **Backup**: Backup your database and manage your dumps with a simple command.
* **Restore**: Easily restore a previous dump of your database.
* **Anonymize**: Set up database anonymization with PHP attributes on Doctrine Entities or with a YAML configuration file.
* Set up a **GRDP-friendly** workflow: Make it easier to follow GDPR best practices when importing production dump to other environments.

## Installation

DbToolsBundle requires PHP 8.1 or higher and Symfony 6.0 or higher. Run the following command to install it in your application:

```sh
composer require makinacorpus/db-tools-bundle
```

## Documentation

Read [DbToolsBundle documentation](https://dbtoolsbundle.readthedocs.io/) on Read the Docs.

## Licence

This software is published under the [MIT License](./LICENCE.md).
