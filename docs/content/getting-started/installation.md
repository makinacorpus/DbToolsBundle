# Installation

Add the *DbToolsBundle* to your Symfony project with [composer](https://getcomposer.org):

```sh
composer require makinacoprus/db-tools-bundle
```
Then, activate the bundle:

```php
// config/bundles.php
<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],

    //...

    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],

    // ...

    MakinaCorpus\DbToolsBundle\DbToolsBundle::class => ['all' => true], // [!code ++]
];
```

And copy provided default configuration from vendor:

```sh
cd your_project_dir
cp vendor/makinacorpus/db-tools-bundle/config/packages/db_tools.yaml config/packages/.
```

Feel free to read this configuration file, it will learn you basics about this bundle.

**That's it, *DbToolsBundle* is now ready to be used.**

But before starting to use it, just check the *DbToolsBundle* can find backup and restore binaries for
your(s) doctrine connection(s):

```sh
php bin/console db-tools:check
```

:::tip
If this command returns errors, get to the [binaries configuration section](/introduction/configuration#binaries)
to understand how to solve them.
:::
