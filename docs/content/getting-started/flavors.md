# Flavors

*DbToolsBundle* is available in various *flavors*:

<FlavorSwitcher />

Currently, you can either use it as a *standalone* PHP library or with its *Symfony* bundle. Two
additional flavors should be soon availabled : *Laravel* and *Docker*.

::: tip
You can choose your favorite flavor from the menu in the top left-hand corner.

Most of the content (text and examples) presented on this documentation will change according
to this selected flavor.
:::

## Standalone

First and foremost, *DbToolsBundle* is a PHP library. It can easily be used on any project managed
with [composer](https://getcomposer.org).

After the [installation](/getting-started/installation), a binary will be available in
your `vendor` directory : `vendor/bin/db-tools`.

With the standalone edition, a minimum configuration is needed to tell the system where
to find the database(s) you want to manage.

## Symfony

*DbToolsBundle* was, in its first version, a tools for Symfony developpers only. The library can still
be fully integrated into any Symfony project via its dedicated bridge.

After you installed the bundle:
* All the *DbToolsBundle* commands will be accessible with
  the [Symfony Console](https://symfony.com/doc/current/components/console.html).
* Database connection URL will be autoconfigured based on available DBAL connections.
* *DbToolsBundle* can be setup through its bundle configuration (`config/packages/db_tools.yaml`).
* Anonymization can be set through PHP attributes on Doctrine Entities.
