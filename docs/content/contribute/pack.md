# Create and share your own pack of anonymizers

You created a bunch of anonymizers and want to reuse them easily? May be you
also want to share them with the community?

The DbToolsBundle help you to do so with [a github template repository](https://github.com/DbToolsBundle/pack-template).

After [you created a fresh repository from this template](https://github.com/new?template_name=pack-template&template_owner=DbToolsBundle),
follow these steps to set up your pack:

[[toc]]

## 1. Adapt the template to your repository

Now you have your repository. Let's say its name is `my-vendor/pack-awesome`.

First, you will need to adapt the provided `composer.json`:

```json
{
    "name": "db-tools-bundle/pack-template",// [!code --]
    "name": "my-vendor/pack-awesome",// [!code ++]
    "description": "An example pack of anonymizers for the DbToolsBundle",// [!code --]
    "description": "An awesome pack for anonymizing many things!",// [!code ++]
    "type": "db-tools-bundle-pack",
    "license": "MIT",
    "authors": [
        { // [!code --]
            "name": "Makina Corpus", // [!code --]
            "homepage": "http://makina-corpus.com" // [!code --]
        } // [!code --]
    ],
    "minimum-stability": "stable",
    "prefer-stable": true,
    "require": {
        "php": ">=8.1",
        "makinacorpus/db-tools-bundle": "^0.3"
    },
    "require-dev": {
        "friendsofphp/php-cs-fixer": "^3.34",
        "phpstan/phpstan": "^1.10",
        "phpunit/phpunit": "^10.3",
        "symfony/framework-bundle": "^6.0",
        "symfony/validator": "^6.3"
    },
    "autoload": {
        "psr-4": {
            "DbToolsBundle\\PackExample\\" : "src/"// [!code --]
            "DbToolsBundle\\PackAwesome\\" : "src/"// [!code ++]
        }
    },
    "autoload-dev": {
        "psr-4": {
            "DbToolsBundle\\PackExample\\Tests\\": "tests/"// [!code --]
            "DbToolsBundle\\PackAwesome\\Tests\\": "tests/"// [!code ++]
        }
    },
    "config": {
        "sort-packages": true
    },
    "scripts": {
        "phpcs": "./vendor/bin/php-cs-fixer fix --verbose --allow-risky=yes",
        "phpstan": "./vendor/bin/phpstan --memory-limit=1G",
        "checks": [
            "@phpcs",
            "@phpstan"
        ],
        "dry-checks": [
            "@phpcs --dry-run",
            "@phpstan"
        ]
    }
}
```

Then, after reading it, delete examples:

```sh
rm src/Anonymizer/MyAnonymizer.php
rm tests/Functional/Anonymizer/MyAnonymizerTest.php
```

After that, bootstrap your `README` with the given example:

```sh
rm README.md
mv README.md.example README.md
```

And adapt it:

```md
# DbToolsBundle - Pack [your pack name]// [!code --]
# DbToolsBundle - Awesome pack// [!code ++]
[a short description]// [!code --]
An awesome pack for anonymizing many things!// [!code ++]

This pack provides:

* `my-pack:anonymizer-1`: a short description of this anonymizer // [!code --]
* `my-pack:anonymizer-2`: a short description of this anonymizer // [!code --]
* `my-pack:anonymizer-3`: a short description of this anonymizer // [!code --]
Fill this later // [!code ++]

## Installation

Run the following command to add this pack to your application:

\```sh
composer require db-tools-bundle/pack-[your pack]// [!code --]
composer require my-vendor/pack-awesome// [!code ++]
\```

Learn more about how to use this package reading [the DbToolsBundle documentation](https://dbtoolsbundle.readthedocs.io/) on Read the Docs.

## Licence

This software is published under the [MIT License](./LICENCE.md).

```

## 2. Develop your anonymizers

Now you are ready to add your own anonymizers. Put them in `src/Anonymizer`.

:::tip
Learn more about how to develop them reading the [Custom Anonymizers section](../anonymization/custom-anonymizers).
:::

## 3. Test your anonymizers

After you built your anonymizers, don't forget to test them. We recommend doing at least one functionnal test per anonymizer.
To inspire you doing these tests, read [existing tests in the DbToolsBundle](https://github.com/makinacorpus/DbToolsBundle/tree/main/tests/Functional/Anonymizer/Core)
or in official packs.

To help you launchning these tests, use provided `dev.sh` script, see [Development guide section](./guide) to learn how to use it.


## 5. Share your pack on packagist

If you want to share it and make it easily installable, share your package on [Packagist](https://packagist.org/).

## 6. Make it an official pack

If:

* you find your package good enough,
* it is well tested,
* you think it is generic enough to interest a large number of people,

Then, you should consider to add it to the official packs list.

Doing so, your package will be more visible (it will be added to this documentation) but it will also be automatically tested
by a CI every week from the [packs-status repository](https://github.com/DbToolsBundle/packs-status).

This CI will:

* Check Coding standards (with PHP CS Fixer)
* Launch a Static Analysis (with PHPStan)
* Launch PHPUnit tests on different database vendors and PHP versions
* Will, on every fail, create an issue on your pack repository to warn you

To ask your package to be part of official list, [open an issue on the packs-status repository](https://github.com/DbToolsBundle/packs-status/issues).

:::info
Note that to have your pack becoming an official one, you will need to transfere the repository to the [DbToolsBundle Organization](https://github.com/DbToolsBundle).
:::
