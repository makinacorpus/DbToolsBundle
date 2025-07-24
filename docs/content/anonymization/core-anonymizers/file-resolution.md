## File name resolution

In various places you can configure relative file names in order to load data,
here is how relative file names are resolved.
**All relative file names will be considered relative to a given _base path_.**

The default base path is always stable but depends upon your selected flavor.

@todo examples

@@@ symfony

When parsing Symfony configuration, base path will always be the project
directory, known as `%kernel.project_dir%` variable in Symfony configuration.
This is the directory where your `composer.json` file.

@todo examples

@@@
@@@ laravel

When parsing Laravel configuration, base path will always be the project
directory, as returned by the `base_path()` Laravel function.

@todo examples

@@@
@@@ standalone docker

When parsing configuration in the standalone CLI version or in docker context,
base path will be currently being parsed Yaml file.

:::tip
If you set the `workdir` option in your configuration file, then it will
override the file directory and use it as the base path.

@todo link to `workdir` documentation
:::
@@@
