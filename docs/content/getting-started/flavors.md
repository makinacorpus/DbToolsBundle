# Flavors

*DbToolsBundle* is available in various *flavors*:

<FlavorSwitcher />

Currently, you can either use this tool as a standalone PHP library, with its *Symfony* bundle or via its *Docker* image. An experimental
integration is also available for *Laravel*.

---

You can change the current flavor from the menu in the top left-hand corner.

Most of the content (text and examples) presented on this documentation will change according
to this selected flavor. Whenever this happens, a visual hint indicates it. For example:

@@@ standalone symfony docker laravel
Some specific explanation, illustrated with some code:

```sh
# a specific code example
```
@@@

Or juste a specific <span db-tools-flavor="standalone-symfony-docker-laravel">`command line`</span> in a paragraph.

## Standalone

First and foremost, *DbToolsBundle* is a PHP library. It can easily be used on any project managed
with [composer](https://getcomposer.org).

After the [installation](/getting-started/installation), a binary will be available in
your `vendor` directory: `vendor/bin/db-tools`.

With the standalone edition, a minimum configuration is needed to tell the system where
to find the database(s) you want to manage.

## Symfony

*DbToolsBundle* was, in its first version, a tool for *Symfony* developers only. The library can still
be fully integrated into any Symfony project via its dedicated bridge.

Right after you installed the bundle, and with zero configuration:
* All *DbToolsBundle* commands will be accessible with
  the [Symfony Console](https://symfony.com/doc/current/components/console.html).
* Database connection URL will be autoconfigured based on available DBAL connections.
* *DbToolsBundle* can be setup through its bundle configuration (`config/packages/db_tools.yaml`).
* Anonymization can be set through PHP attributes on Doctrine Entities.

## Docker

*DbToolsBundle* can be used in any CI/CD using its [*Docker* image](https://hub.docker.com/r/makinacorpus/dbtoolsbundle).

This image is based on [FrankenPHP](https://frankenphp.dev/docs/docker/). Every *DbToolsBundle* commands can be run with `docker container run` utility.

All configuration can be set up mounting a config file (see [installation section](/getting-started/installation) for an example). This config file uses the exact same syntax as for the standalone flavor.

## Laravel (experimental) {#laravel}

With the arrival of its version 2, *DbToolsBundle* opens up to the world of
*Laravel*.

For now, the documentation does not include any specific example or precision
related to *Laravel*. However, the configuration of *DbToolsBundle* in *Laravel*
is very similar to its configuration in a *Symfony* project which would use YAML
files for that purpose, except that you will use PHP files instead.

To get *DbToolsBundle* running:

- add it to your *Laravel* project with [composer](https://getcomposer.org):
  ```sh
  composer require makinacorpus/db-tools-bundle
  ```
- only if you disabled the [package discovery][1] system, manually register the
  *DbToolsBundle* service provider in your `bootstrap/providers.php` file:
  ```php
  return [
    // ...
    MakinaCorpus\DbToolsBundle\Bridge\Laravel\DbToolsServiceProvider::class,
    // ...
  ];
  ```
- run the command `php artisan vendor:publish` to initialize the configuration
  file `config/db-tools.php`,
- configure the package to your needs.

The configuration file is quite well self-documented, you should not be lost
in configuring the package.

If necessary, get help from the *Symfony* documentation by following the YAML
format examples.

[1]: https://laravel.com/docs/12.x/packages#package-discovery
